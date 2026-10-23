<?php

namespace App\Services\Dicom;

use App\Models\Cliente;
use App\Models\DicomEquipamentoVisto;
use App\Models\DicomPerfil;
use App\Models\DicomTraducao;
use App\Utils\DicomFields;
use App\Utils\OrthancInstitutionName;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Leitura DICOM guiada por perfis cadastrados no banco (tabela dicom_perfis),
 * editáveis na tela de administração. Usado por ExameController::getDICOMInfo()
 * só pra equipamentos que NÃO têm leitura dedicada em código (CRJ, Konica,
 * Imex, E-COM) — esses continuam sendo testados primeiro e não passam por aqui.
 *
 * Tudo aqui é à prova de falha: se as tabelas ainda não existem (deploy antes
 * da migration) ou o banco/cache falham, devolve "sem perfil" e o caminho
 * genérico do ExameController segue como antes. Nunca derruba a importação.
 */
class DicomProfileResolver
{
    private const CACHE_PERFIS = 'dicom_perfis_ativos';
    private const CACHE_TRADUCOES = 'dicom_traducoes_mapa';
    private const CACHE_SEGUNDOS = 300;

    /**
     * Tags que um perfil pode usar (nome => [grupo, elemento]). Lista fechada
     * de propósito: a tela só deixa escolher destas, e são as que o parser
     * (nanodicom) sabe ler pelo nome.
     */
    public const TAGS = [
        'ProtocolName'                      => [0x0018, 0x1030],
        'SeriesDescription'                 => [0x0008, 0x103E],
        'StudyDescription'                  => [0x0008, 0x1030],
        'PerformedProcedureStepDescription' => [0x0040, 0x0254],
        'RequestedProcedureDescription'     => [0x0032, 0x1060],
        'AcquisitionDeviceProcessingDescription' => [0x0018, 0x1400],
        'BodyPartExamined'                  => [0x0018, 0x0015],
        'ViewPosition'                      => [0x0018, 0x5101],
        'ReferringPhysicianName'            => [0x0008, 0x0090],
        'OperatorsName'                     => [0x0008, 0x1070],
        'PatientComments'                   => [0x0010, 0x4000],
        'InstitutionalDepartmentName'       => [0x0008, 0x1040],
    ];

    /** Tags aceitas como origem do tipo do exame (as que descrevem o exame). */
    public const TAGS_TIPO_EXAME = [
        'ProtocolName', 'SeriesDescription', 'StudyDescription',
        'PerformedProcedureStepDescription', 'RequestedProcedureDescription',
        'AcquisitionDeviceProcessingDescription', 'BodyPartExamined', 'ViewPosition',
    ];

    /**
     * Como cada tag aparece na tela de administração: nome em português + uma
     * frase dizendo o que ela costuma conter, pra quem cadastra não precisar
     * conhecer DICOM. O nome técnico continua visível ao lado.
     */
    public const ROTULOS = [
        'ProtocolName' => ['Protocolo do exame', 'Nome do protocolo configurado no aparelho (ex.: "Torax OIT PA", "Mao"). Costuma ser o mais confiável.'],
        'SeriesDescription' => ['Descrição da imagem (série)', 'Nome da imagem ou posição (ex.: "Obliqua", "PA"). Alguns aparelhos deixam vazio.'],
        'StudyDescription' => ['Descrição do estudo', 'Nome geral do exame (ex.: "TORAX OIT"). Atenção: alguns aparelhos gravam o nome da empresa aqui.'],
        'PerformedProcedureStepDescription' => ['Procedimento realizado', 'Procedimento informado pelo aparelho ou pelo sistema da clínica (RIS).'],
        'RequestedProcedureDescription' => ['Procedimento solicitado', 'O que foi pedido na requisição do exame.'],
        'AcquisitionDeviceProcessingDescription' => ['Processamento da imagem', 'Texto de processamento do aparelho; alguns usam como nome do exame.'],
        'BodyPartExamined' => ['Parte do corpo', 'Código padrão do aparelho (HAND, CHEST...), traduzido pela tabela "Traduções de parte do corpo".'],
        'ViewPosition' => ['Posição da imagem', 'Posição do raio-X (PA, AP, LAT...).'],
        'ReferringPhysicianName' => ['Médico solicitante', 'Médico que pediu o exame.'],
        'OperatorsName' => ['Operador / técnico', 'Quem operou o aparelho. Muitas vezes é só um login genérico.'],
        'PatientComments' => ['Comentários do paciente', 'Texto livre. Muitos aparelhos gravam aqui a empresa onde o paciente trabalha.'],
        'InstitutionalDepartmentName' => ['Departamento da instituição', 'Setor da clínica que fez o exame.'],
    ];

    /** [valor, rotulo, descricao] de uma lista de tags, pra tela. */
    public static function descrever(array $nomes): array
    {
        return array_map(function ($nome) {
            $info = self::ROTULOS[$nome] ?? [$nome, ''];

            return ['valor' => $nome, 'rotulo' => $info[0], 'descricao' => $info[1]];
        }, $nomes);
    }

    /**
     * Valor especial de empresa_tag: em vez de uma tag, usa o nome do Cliente
     * cadastrado cujo institution_name_id bate com o InstitutionName do DICOM
     * (o mesmo hash que o sistema já usa pra descobrir o Cliente/tenant).
     */
    public const ORIGEM_CLIENTE = '@CLIENTE';
    public const ROTULO_ORIGEM_CLIENTE = 'Cliente cadastrado (via InstitutionNameId)';

    /** Tags aceitas como origem de médico/empresa. */
    public const TAGS_TEXTO = [
        'ReferringPhysicianName', 'OperatorsName', 'PatientComments',
        'StudyDescription', 'InstitutionalDepartmentName',
    ];

    /**
     * Critério puro de detecção, separado do banco pra ser testável: todo
     * critério preenchido precisa estar contido no valor correspondente do
     * equipamento (sem diferenciar maiúsculas/acentos); critério vazio é
     * ignorado, e um perfil sem nenhum critério nunca casa.
     */
    public static function casa(array $criterios, array $equipamento): bool
    {
        $algum = false;
        foreach (['fabricante', 'modelo', 'estacao'] as $campo) {
            $busca = DicomFields::toAsciiUpper($criterios[$campo] ?? '');
            if ($busca === '') {
                continue;
            }
            $algum = true;
            if (strpos(DicomFields::toAsciiUpper($equipamento[$campo] ?? ''), $busca) === false) {
                return false;
            }
        }

        return $algum;
    }

    /** Lê Manufacturer/modelo/estação do arquivo. Faz o parse das tags que precisa. */
    public static function equipamento($dicom): array
    {
        $dicom->parse(['Manufacturer']);
        $dicom->parse(['ManufacturerModelName']);
        $dicom->parse(['StationName']);

        return [
            'fabricante' => (string) $dicom->value(0x0008, 0x0070),
            'modelo'     => (string) $dicom->value(0x0008, 0x1090),
            'estacao'    => (string) $dicom->value(0x0008, 0x1010),
        ];
    }

    /** Primeiro perfil ativo (por prioridade) que casa com o equipamento, ou null. */
    public function perfilPara(array $equipamento): ?DicomPerfil
    {
        foreach ($this->perfisAtivos() as $perfil) {
            $criterios = [
                'fabricante' => $perfil->fabricante_contem,
                'modelo'     => $perfil->modelo_contem,
                'estacao'    => $perfil->estacao_contem,
            ];
            if (self::casa($criterios, $equipamento)) {
                return $perfil;
            }
        }

        return null;
    }

    /**
     * Monta o mesmo objeto que ExameController::getDICOMInfo() devolve, a
     * partir do perfil: tipo do exame = primeira tag da lista com valor útil
     * (placeholders como "undefined" são ignorados), traduzido pela tabela de
     * traduções e, se o perfil pedir, com ESQ/DIR da lateralidade.
     */
    public function montarInfo(DicomPerfil $perfil, $dicom): object
    {
        foreach (self::TAGS as $nome => $_) {
            $dicom->parse([$nome]);
        }
        $dicom->parse(['StudyInstanceUID']);
        $dicom->parse(['SeriesInstanceUID']);
        $dicom->parse(['InstitutionName']);
        $dicom->parse(['ImageLaterality']);

        $descricao = '';
        foreach ($perfil->tipoExameTags() as $tag) {
            if (!isset(self::TAGS[$tag])) {
                continue;
            }
            $valor = trim((string) $dicom->value(...self::TAGS[$tag]));
            if (!DicomFields::isPlaceholder($valor)) {
                $descricao = $tag === 'BodyPartExamined' ? DicomFields::normalizeBodyPart($valor) : $valor;
                break;
            }
        }

        $tipo = DicomFields::toAsciiUpper($descricao);
        $tipo = $this->traduzir($tipo);

        if ($perfil->usa_lateralidade && $tipo !== '') {
            $lado = strtoupper(trim((string) $dicom->value(0x0020, 0x0062)));
            if ($lado === 'L') $tipo .= ' ESQ';
            if ($lado === 'R') $tipo .= ' DIR';
        }

        $oit = $perfil->oit_por_texto && DicomFields::isOitByText([
            (string) $dicom->value(0x0018, 0x1030),
            (string) $dicom->value(0x0008, 0x1030),
            (string) $dicom->value(0x0008, 0x103E),
        ]);

        return (object) [
            'id'      => $dicom->value(0x0020, 0x000D) . '-' . $tipo,
            'cliente' => $dicom->value(0x0008, 0x0080),
            'oit'     => $oit ? 'S' : 'N',
            'exame'   => DicomFields::toAsciiUpper($descricao),
            'subtipo' => $tipo,
            'medico'  => $this->textoDaTag($perfil->medico_tag, $dicom),
            'empresa' => $this->textoDaTag($perfil->empresa_tag, $dicom),
            'series_uid' => trim((string) $dicom->value(0x0020, 0x000E)),
            'parte_corpo_examinada' => null, // preenchido pelo ExameController (normalização única)
        ];
    }

    /** Mapa origem => destino das traduções cadastradas (a tabela do banco vence a do código). */
    public static function traducoes(): array
    {
        try {
            return Cache::remember(self::CACHE_TRADUCOES, self::CACHE_SEGUNDOS, function () {
                return DicomTraducao::pluck('destino', 'origem')->all();
            });
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function limparCache(): void
    {
        try {
            Cache::forget(self::CACHE_PERFIS);
            Cache::forget(self::CACHE_TRADUCOES);
        } catch (Throwable $e) {
            // sem cache nada a limpar
        }
    }

    /**
     * Registra (no máximo 1x a cada 10 min por equipamento/perfil) qual
     * equipamento mandou DICOM e como foi lido, pra tela de "equipamentos
     * vistos". Nunca lança: é só observabilidade.
     */
    public static function registrarVisto(array $equipamento, ?DicomPerfil $perfil, string $origem, ?string $tipo): void
    {
        try {
            $chave = 'dicom_visto_' . md5(json_encode([$equipamento, $perfil ? $perfil->id : null, $origem]));
            if (!Cache::add($chave, 1, 600)) {
                return;
            }

            // Texto como o aparelho mandou (só limpo), pra tela mostrar "CS-7" e não "CS?7";
            // o casamento dos perfis normaliza os dois lados por conta própria (casa()).
            $limpa = function ($v) {
                return mb_substr(trim(DicomFields::toUtf8((string) $v), " \t\n\r\0"), 0, 255);
            };
            $fabricante = $limpa($equipamento['fabricante'] ?? '');
            $modelo = $limpa($equipamento['modelo'] ?? '');
            $estacao = $limpa($equipamento['estacao'] ?? '');

            DicomEquipamentoVisto::updateOrCreate(
                ['chave' => md5($fabricante . '|' . $modelo . '|' . $estacao . '|' . $origem)],
                [
                    'fabricante'   => $fabricante,
                    'modelo'       => $modelo,
                    'estacao'      => $estacao,
                    'perfil_origem' => $origem,
                    'perfil_id'    => $perfil ? $perfil->id : null,
                    'tipo_exemplo' => $tipo !== null ? mb_substr($tipo, 0, 255) : null,
                    'ultima_vez'   => now(),
                ]
            );
        } catch (Throwable $e) {
            Log::debug('DicomProfileResolver: não foi possível registrar equipamento visto: ' . $e->getMessage());
        }
    }

    private function perfisAtivos()
    {
        try {
            return Cache::remember(self::CACHE_PERFIS, self::CACHE_SEGUNDOS, function () {
                return DicomPerfil::where('ativo', true)->orderByDesc('prioridade')->orderBy('id')->get();
            });
        } catch (Throwable $e) {
            Log::debug('DicomProfileResolver: perfis indisponíveis, seguindo sem perfil: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Nome do Cliente achado pelo mesmo hash do fluxo normal: hex do
     * InstitutionName cru sem o último byte == clientes.institution_name_id.
     */
    private function nomeDoCliente($dicom): ?string
    {
        $id = OrthancInstitutionName::computeIdFromRawBytes((string) $dicom->value(0x0008, 0x0080));
        if (!$id) {
            return null;
        }

        try {
            $nome = Cliente::where('institution_name_id', $id)->value('nome');
        } catch (Throwable $e) {
            return null;
        }

        return $nome ? (DicomFields::toAsciiUpper($nome) ?: null) : null;
    }

    private function traduzir(string $tipo): string
    {
        $mapa = self::traducoes();

        return $mapa[$tipo] ?? $tipo;
    }

    private function textoDaTag(?string $tag, $dicom): ?string
    {
        if ($tag === self::ORIGEM_CLIENTE) {
            return $this->nomeDoCliente($dicom);
        }
        if (empty($tag) || !isset(self::TAGS[$tag])) {
            return null;
        }
        $valor = trim(str_replace('^', ' ', (string) $dicom->value(...self::TAGS[$tag])));
        if (DicomFields::isPlaceholder($valor)) {
            return null;
        }

        return DicomFields::toAsciiUpper($valor) ?: null;
    }
}
