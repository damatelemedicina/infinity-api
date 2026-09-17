<?php

namespace App\Services\Orthanc;

use App\Http\Controllers\ExameController;
use App\Models\Cliente;
use App\Utils\Dicom;
use App\Utils\OrthancInstitutionName;
use Illuminate\Support\Facades\Log;

/**
 * Importa instâncias DICOM do Orthanc pra tabela `exames`.
 *
 * Estende ExameController de propósito: insertDCM()/insertExame()/addDCMBytesInList()/
 * processDCMGroups() já têm toda a lógica testada de leitura de tags, agrupamento por
 * tipo e criação de exame usada pelos outros canais de upload (DAMA Desktop, upload
 * manual). Essa classe só adiciona a parte que esses fluxos não têm — descobrir o
 * Cliente/tenant a partir de uma instância do Orthanc, sem sessão logada e sem
 * chave_transmissao informada por quem chama — e depois repassa pro pipeline existente.
 *
 * setPropertiesByInstitutionName() foi ampliado de private pra protected no
 * ExameController só pra permitir isso; nenhum outro comportamento mudou.
 */
class OrthancExameImporter extends ExameController
{
    /**
     * Importa uma única instância DICOM isolada — um exame por chamada, sem
     * agrupar com as demais. Mantido por compatibilidade / testes diretos; o
     * comando de sincronismo (OrthancSyncExames) hoje importa via
     * importStudy(), que agrupa o estudo inteiro por tipo antes de criar os
     * exames, igual um zip de lote.
     *
     * @param string $dcmContents Bytes crus da instância DICOM, baixados do Orthanc.
     * @param string $instanceId  ID da instância no Orthanc, usado só pro nome do arquivo temporário local.
     * @return bool true se um exame foi criado (ou já existia via dedupe por CRC), false se foi ignorado.
     */
    public function importInstance(string $dcmContents, string $instanceId): bool
    {
        $relativePath = '/uploads/exames/lotes/' . \Str::random(40) . '.dcm';
        $localPath = $this->storePath($relativePath);

        if (!is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }
        file_put_contents($localPath, $dcmContents);

        // ATENÇÃO: $localPath NÃO é um arquivo temporário descartável.
        // insertDCM()/insertExame() guardam esse mesmo caminho relativo na linha do
        // exame (arquivo_exame) como referência permanente, usada depois pra
        // exibir/baixar o exame — exatamente como todo outro canal de upload faz com
        // arquivos em /uploads/exames/lotes/. Só apague nos caminhos onde "nenhum
        // exame foi criado" — nunca depois de um insertDCM() bem-sucedido.

        $cliente = $this->resolveClienteFromLocalFile($localPath, $instanceId);
        if (!$cliente) {
            @unlink($localPath);
            return false;
        }

        $this->setPropertiesByInstitutionName($cliente);

        $fileName = basename($relativePath);
        $this->insertDCM($relativePath, $fileName, $cliente->chave_transmissao, null);

        return true;
    }

    /**
     * Importa todas as instâncias de um estudo do Orthanc de uma vez só,
     * agrupando-as por tipo (StudyInstanceUID + tipoExame — ver
     * ExameController::getDICOMInfo()) exatamente como um zip de lote faz via
     * addDCMFileInList()/processDCMGroups(): várias imagens do mesmo tipo
     * viram um único exame, em vez de um exame por instância.
     *
     * Feito pra ser chamado quando o Orthanc avisa que o estudo está "estável"
     * (evento StableStudy), ou seja, não é mais esperada nenhuma instância nova
     * pra ele — agrupar só faz sentido depois que o estudo inteiro chegou.
     *
     * @param OrthancClient $client  Usado pra listar as instâncias do estudo e baixar cada uma.
     * @param string        $studyId ID do estudo no Orthanc (o "ID" do evento StableStudy).
     * @return bool true se pelo menos um exame foi criado (ou já existia via dedupe por CRC), false se foi ignorado.
     */
    public function importStudy(OrthancClient $client, string $studyId): bool
    {
        $instanceIds = $client->getStudyInstanceIds($studyId);
        if (empty($instanceIds)) {
            Log::warning("OrthancSync: estudo {$studyId} sem instâncias, ignorado.");
            return false;
        }

        $pathDcm = '/uploads/exames/lotes/dcm/';
        if (!is_dir($this->storePath($pathDcm))) {
            mkdir($this->storePath($pathDcm), 0777, true);
        }

        // A primeira instância é baixada à parte só pra descobrir o Cliente via
        // InstitutionName antes de gastar tempo baixando o resto do estudo — se o
        // Cliente não for encontrado, nem vale a pena continuar.
        $firstBytes = $client->downloadInstanceFile($instanceIds[0]);
        $firstLocalPath = $this->storePath($pathDcm . \Str::random(40) . '.dcm');
        file_put_contents($firstLocalPath, $firstBytes);

        $cliente = $this->resolveClienteFromLocalFile($firstLocalPath, $studyId);
        if (!$cliente) {
            @unlink($firstLocalPath);
            return false;
        }

        // O viewer (Stone Web Viewer) busca os dados via DICOMweb, não pela
        // API nativa do Orthanc — o parâmetro "study=" da URL do viewer precisa
        // ser o StudyInstanceUID do DICOM (tag 0020,000D), não o ID interno do
        // Orthanc ($studyId acima). Todas as instâncias do estudo compartilham
        // o mesmo StudyInstanceUID, então basta ler da primeira.
        $studyInstanceUid = $this->parseStudyInstanceUid($firstLocalPath);

        $this->setPropertiesByInstitutionName($cliente);

        // Agrupamento por tipo de verdade: cada instância baixada passa por
        // addDCMBytesInList() (equivalente ao addDCMFileInList() do zip de lote),
        // que decide em qual grupo ela entra — ver getDICOMInfo()/groupDCMFile().
        $dcmFiles = array();
        $dcmFiles = $this->addDCMBytesInList($dcmFiles, $firstBytes, $pathDcm);
        @unlink($firstLocalPath);

        for ($i = 1; $i < count($instanceIds); $i++) {
            try {
                $bytes = $client->downloadInstanceFile($instanceIds[$i]);
                $dcmFiles = $this->addDCMBytesInList($dcmFiles, $bytes, $pathDcm);
            } catch (\Throwable $e) {
                Log::error("OrthancSync: falha ao baixar instância {$instanceIds[$i]} do estudo {$studyId}: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        }

        // processDCMGroups() é o mesmo método usado pelo zip de lote (insertZip):
        // cria um exame por grupo (zipando as instâncias quando o grupo tem mais
        // de uma). Comparamos a contagem de exames antes/depois porque
        // processDCMGroups() não devolve quantos exames criou.
        $countBefore = \App\Models\Exame::count();
        $this->processDCMGroups($dcmFiles, $pathDcm, $cliente->id, $cliente->chave_transmissao, $studyInstanceUid);

        return \App\Models\Exame::count() > $countBefore;
    }

    /**
     * Descobre o Cliente/tenant de um arquivo DICOM baixado, via tag
     * InstitutionName — compartilhado por importInstance() e importStudy().
     * Loga e registra alerta (registraAlerta) quando nada bate, do mesmo jeito
     * que antes de essa lógica ser extraída pra este método.
     */
    private function resolveClienteFromLocalFile(string $localPath, string $orthancId): ?Cliente
    {
        $dicom = Dicom::getInstance($localPath);
        $dicom->parse(['InstitutionName']);
        $institutionNameRaw = $dicom->value(0x0008, 0x0080);
        $institutionNameId = $this->computeInstitutionNameId($institutionNameRaw);

        $cliente = $institutionNameId ? Cliente::where('institution_name_id', $institutionNameId)->first() : null;
        if (!$cliente) {
            $mensagem = "OrthancSync: {$orthancId} sem Cliente correspondente. "
                . "InstitutionName=[" . $this->sanitizeTextForDisplay($institutionNameRaw) . "] "
                . "InstitutionNameId=[" . ($institutionNameId ?? '(vazio)') . "] — "
                . "cadastre esse Institution Name Id no cliente correto pra próxima vez ser reconhecido.";
            Log::warning($mensagem);
            $this->tentaRegistrarAlerta($mensagem);
            return null;
        }

        if (empty($cliente->chave_transmissao)) {
            Log::warning("OrthancSync: Cliente {$cliente->id} resolvido para {$orthancId}, mas não tem chave_transmissao configurada, ignorado.");
            return null;
        }

        return $cliente;
    }

    /**
     * Lê o StudyInstanceUID (tag DICOM 0020,000D) de um arquivo já baixado —
     * é esse valor, e não o ID interno do Orthanc, que o Stone Web Viewer
     * espera no parâmetro "study=" da URL (ele resolve os dados via DICOMweb,
     * que identifica estudos pelo UID do próprio DICOM).
     */
    private function parseStudyInstanceUid(string $localPath): ?string
    {
        $dicom = Dicom::getInstance($localPath);
        $dicom->parse(['StudyInstanceUID']);
        $uid = trim((string) $dicom->value(0x0020, 0x000D));
        return $uid !== '' ? $uid : null;
    }

    /**
     * Espelha ExameController::getInstitutionName(): o Orthanc/DICOM preenche
     * strings curtas com um byte nulo no final, por isso o "tira o último byte
     * hex" antes de comparar com Cliente.institution_name_id.
     *
     * IMPORTANTE: isso NÃO bate com o jeito que o checkbox "Gerar Institution
     * Name Id" da tela do site calcula o valor (bin2hex(texto) puro, sem
     * cortar nada) — digitar o nome da instituição no cadastro do cliente e
     * clicar em "Gerar" normalmente vai produzir um valor um byte mais longo
     * do que qualquer instância DICOM real vai ter. Sempre copie o
     * InstitutionNameId exato logado/alertado aqui e cole direto no campo
     * "Institution Name Id" do cliente, em vez de gerar pelo botão.
     */
    protected function computeInstitutionNameId(?string $institutionNameRaw): ?string
    {
        return OrthancInstitutionName::computeIdFromRawBytes($institutionNameRaw);
    }

    /**
     * Equipamentos DICOM muitas vezes mandam bytes que não são UTF-8
     * (normalmente Windows-1252/Latin-1 pra caracteres acentuados, via
     * SpecificCharacterSet ISO_IR 100) em tags de texto como InstitutionName.
     * Guardar isso cru numa coluna MySQL UTF-8 (ex: operacoes.operacao) dá erro
     * 1366 e derruba o alerta silenciosamente — isso aqui é só sanitização pra
     * exibição, nunca usado na comparação byte-a-byte do
     * computeInstitutionNameId() acima.
     */
    protected function sanitizeTextForDisplay(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        // Último recurso: substitui o que ainda não for UTF-8 válido em vez de
        // deixar uma segunda tentativa de conversão quebrar o insert no banco.
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    /**
     * Mostra os avisos de instituição não encontrada na própria tela de
     * Operações do sistema (registraAlerta), não só no log do servidor, pra
     * quem administra os clientes conseguir ver e agir. Usa o usuário/cliente
     * "sistema" (não existe sessão logada nesse contexto de CLI) — se isso não
     * estiver configurado, cai de volta silenciosamente pro Log::warning que
     * quem chamou já emitiu.
     */
    protected function tentaRegistrarAlerta(string $mensagem): void
    {
        try {
            $this->registraAlerta($mensagem);
        } catch (\Throwable $e) {
            Log::debug('OrthancSync: não foi possível registrar alerta em Operações: ' . $e->getMessage());
        }
    }
}
