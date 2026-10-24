<?php

namespace App\Http\Controllers;

use App\Exceptions\BloqueioException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Models\DicomEquipamentoVisto;
use App\Models\DicomPerfil;
use App\Models\DicomTraducao;
use App\Services\Dicom\DicomProfileResolver;
use App\Utils\DicomFields;
use App\ViewModels\LoginViewModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Administração dos perfis de leitura DICOM por fabricante (ver
 * DicomProfileResolver). Só administrador da conta pode alterar.
 */
class DicomPerfilController extends Controller
{
    public function getDicomPerfis(Request $request)
    {
        $this->validarRequisicao($request);

        return DicomPerfil::orderByDesc('prioridade')->orderBy('nome')->get()->toArray();
    }

    public function getDicomPerfil(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        return DicomPerfil::findOrFail($request['body']['id'])->toArray();
    }

    public function setDicomPerfil(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $this->exigirAdmin($request);

        $data = $request['body'];
        $tagsTipo = implode(',', array_map('trim', (array) ($data['tipo_exame_tags'] ?? [])));
        if (is_string($data['tipo_exame_tags'] ?? null)) {
            $tagsTipo = implode(',', array_filter(array_map('trim', explode(',', $data['tipo_exame_tags']))));
        }
        $data['tipo_exame_tags'] = $tagsTipo;

        \validator($data, [
            'id' => 'nullable|integer',
            'nome' => 'required|string|max:255|min:2',
            'fabricante_contem' => 'nullable|string|max:255',
            'modelo_contem' => 'nullable|string|max:255',
            'estacao_contem' => 'nullable|string|max:255',
            'prioridade' => 'nullable|integer',
            'tipo_exame_tags' => 'required|string',
            'medico_tag' => ['nullable', Rule::in(DicomProfileResolver::TAGS_TEXTO)],
            'empresa_tag' => ['nullable', Rule::in(array_merge(DicomProfileResolver::TAGS_TEXTO, [DicomProfileResolver::ORIGEM_CLIENTE]))],
        ])->validate();

        foreach (explode(',', $tagsTipo) as $tag) {
            if (!in_array($tag, DicomProfileResolver::TAGS_TIPO_EXAME, true)) {
                throw new BloqueioException("Tag inválida para tipo do exame: {$tag}");
            }
        }

        // Um perfil sem nenhum critério casaria com... nada (ver DicomProfileResolver::casa),
        // então é recusado aqui pra não parecer que está funcionando.
        if (!DicomFields::toAsciiUpper(($data['fabricante_contem'] ?? '') . ($data['modelo_contem'] ?? '') . ($data['estacao_contem'] ?? ''))) {
            throw new BloqueioException('Informe pelo menos um critério: fabricante, modelo ou estação.');
        }

        $perfil = empty($data['id']) ? new DicomPerfil() : DicomPerfil::findOrFail($data['id']);
        $perfil->fill([
            'nome' => trim($data['nome']),
            'fabricante_contem' => $this->vazioParaNull($data['fabricante_contem'] ?? null),
            'modelo_contem' => $this->vazioParaNull($data['modelo_contem'] ?? null),
            'estacao_contem' => $this->vazioParaNull($data['estacao_contem'] ?? null),
            'prioridade' => (int) ($data['prioridade'] ?? 0),
            'ativo' => !empty($data['ativo']),
            'tipo_exame_tags' => $tagsTipo,
            'usa_lateralidade' => !empty($data['usa_lateralidade']),
            'oit_por_texto' => !array_key_exists('oit_por_texto', $data) || !empty($data['oit_por_texto']),
            'medico_tag' => $this->vazioParaNull($data['medico_tag'] ?? null),
            'empresa_tag' => $this->vazioParaNull($data['empresa_tag'] ?? null),
            'observacao' => $this->vazioParaNull($data['observacao'] ?? null),
        ])->save();

        DicomProfileResolver::limparCache();

        return ['message' => 'Perfil salvo com sucesso', 'id' => $perfil->id];
    }

    public function excluirDicomPerfil(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $this->exigirAdmin($request);

        DicomPerfil::findOrFail($request['body']['id'])->delete();
        DicomProfileResolver::limparCache();

        return ['message' => 'Perfil excluído com sucesso'];
    }

    /** Tags que a tela oferece (nome => descrição curta), pra montar os selects. */
    public function getDicomTags(Request $request)
    {
        $this->validarRequisicao($request);

        $texto = DicomProfileResolver::descrever(DicomProfileResolver::TAGS_TEXTO);

        return [
            'tipo_exame' => DicomProfileResolver::descrever(DicomProfileResolver::TAGS_TIPO_EXAME),
            'texto' => $texto,
            // Empresa aceita as tags de texto + o nome do Cliente achado pelo InstitutionNameId.
            'empresa' => array_merge($texto, [[
                'valor' => DicomProfileResolver::ORIGEM_CLIENTE,
                'rotulo' => DicomProfileResolver::ROTULO_ORIGEM_CLIENTE,
                'descricao' => 'No envio pela tela, usa o nome da empresa (cliente) logada. Nos exames que chegam do aparelho (Orthanc) não há ninguém logado: a empresa fica em branco para ser informada na tela de laudo.',
            ]]),
        ];
    }

    public function getDicomEquipamentosVistos(Request $request)
    {
        $this->validarRequisicao($request);

        return DicomEquipamentoVisto::orderByDesc('ultima_vez')->limit(500)->get()->toArray();
    }

    public function getDicomTraducoes(Request $request)
    {
        $this->validarRequisicao($request);

        return DicomTraducao::orderBy('origem')->get()->toArray();
    }

    public function setDicomTraducao(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $this->exigirAdmin($request);

        $data = $request['body'];
        $data['origem'] = DicomFields::toAsciiUpper($data['origem'] ?? '');
        $data['destino'] = DicomFields::toAsciiUpper($data['destino'] ?? '');

        \validator($data, [
            'id' => 'nullable|integer',
            'origem' => 'required|string|max:255',
            'destino' => 'required|string|max:255',
        ])->validate();

        $traducao = empty($data['id'])
            ? DicomTraducao::firstOrNew(['origem' => $data['origem']])
            : DicomTraducao::findOrFail($data['id']);
        $traducao->origem = $data['origem'];
        $traducao->destino = $data['destino'];
        $traducao->save();

        DicomProfileResolver::limparCache();

        return ['message' => 'Tradução salva com sucesso'];
    }

    public function excluirDicomTraducao(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $this->exigirAdmin($request);

        DicomTraducao::findOrFail($request['body']['id'])->delete();
        DicomProfileResolver::limparCache();

        return ['message' => 'Tradução excluída com sucesso'];
    }

    private function exigirAdmin(Request $request): void
    {
        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['session']['login']
        );
        if (!$login) throw new EmpresaNaoEncontradaException();
        if (($login['isContaAdmin'] ?? 0) !== 1) {
            throw new BloqueioException('Você não tem permissão para alterar os perfis DICOM!');
        }
    }

    private function vazioParaNull($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
