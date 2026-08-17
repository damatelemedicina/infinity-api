<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\TipoExameNaoEncontradoException;
use App\Exceptions\CampoTipoExameNaoEncontradoException;
use App\Exceptions\RequisicaoMalFormadaException;
use App\Exceptions\ExclusaoNaoPermitidaException;
use App\Exceptions\MedicoNaoEncontradoException;

use App\Models\TipoExame;
use App\Models\TipoExameCampo;
use App\Models\Servico;
use App\Models\Medico;

use App\Services\Entity\EntityManager;

use Illuminate\Support\Facades\DB;

class TipoExameController extends Controller
{

    protected $entity;

    public function __construct(EntityManager $entity)
    {
        $this->entity = $entity;
    }

    public function delTipoExame(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $data = $request["body"];
        if (!isset($data['id'])) throw new RequisicaoMalFormadaException();

        try {

            $tipoExame = TipoExame::where('id', $data['id'])->first();

            if (!$tipoExame) throw new TipoExameNaoEncontradoException();

            $tipoExame->delete();

        } catch (\Throwable $th) {

            // testar questão de violação da integridade referencial.
            throw new ExclusaoNaoPermitidaException();

        }

    }

    public function delTipoExameCampo(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $data = $request["body"];
        if (!isset($data['id'])) throw new RequisicaoMalFormadaException();
        $campo = TipoExameCampo::where('id', $data['id'])->first();
        if (!$campo) throw new CampoTipoExameNaoEncontradoException();
        $campo->delete();
        return [];
    }

    public function setTipoExameCampo(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $data = $request["body"];

        $tipoExame = isset($data['TipoExameId'])
                    ? TipoExame::where('id', $data['TipoExameId'])->first()
                    : null;

        if (!isset($tipoExame))
            throw new TipoExameNaoEncontradoException();

        $campo = isset($data['CampoId'])
        ? TipoExameCampo::where('id', $data['CampoId'])->first()
        : new TipoExameCampo();

        if (!$campo) throw new CampoTipoExameNaoEncontradoException();

        $campo->nome = $data['CampoNome'];
        $campo->tipo = strtoupper($data['CampoTipo']);
        $campo->ordem = (int)$data['CampoOrdem'];
        $campo->tamanho = (int)$data['CampoTamanho'];
        $campo->opcoes = $data['CampoOpcoes'] ? $data['CampoOpcoes'] : '';
        $campo->obrigatorio = $data['CampoObrigatorio'];
        $campo->tipo_exame_id = $tipoExame->id;
        $campo->save();

        return $campo;

    }

    private function clonarCampos($id, $data) {
        if (isset($data['TipoExameId']) || !isset($data['TipoExameCopiar'])) return;
        $tipoExame = TipoExame::where('id', $data['TipoExameCopiar'])->first();
        if (!$tipoExame) throw new TipoExameNaoEncontradoException();
        foreach($tipoExame->campos() as $campo) {
            $novo = new TipoExameCampo();
            $novo->nome = $campo->nome;
            $novo->tipo = $campo->tipo;
            $novo->ordem = $campo->ordem;
            $novo->tamanho = $campo->tamanho;
            $novo->opcoes = $campo->opcoes;
            $novo->obrigatorio = $campo->obrigatorio;
            $novo->tipo_exame_id = $id;
            $novo->save();
        }
    }

    public function setTipoExame(Request $request)
    {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $data = $request["body"];

        $tipoExame = isset($data['TipoExameId'])
                    ? TipoExame::where('id', $data['TipoExameId'])->first()
                    : new TipoExame();

        $tipoExame->nome = $data['TipoExameNome'];
        $tipoExame->empresa_id = isset($data['TipoExameId']) ?
                                 $tipoExame->empresa_id :
                                 $this->getMatriz($request);
        $tipoExame->situacao = $data['TipoExameSituacao'];
        $tipoExame->emergencia=$data['TipoExameEmergencia'];
        $tipoExame->laudo_rapido=$data['TipoExameLaudoRapido'];
        $tipoExame->desativar_modelo=$data['TipoExameDesativarModelo'];
        $tipoExame->desativar_upload=$data['TipoExameDesativarUpload'];
        $tipoExame->save();

        //$this->clonarCampos($tipoExame->id, $data);

        Servico::where('tipo_exame_id', '=', $data['TipoExameId'])->delete();

        foreach($data['TipoExameServicos'] as $value){
            if (!$value) continue;
            $servico = new Servico();
            $servico->empresa_id = $this->getMatriz($request);
            $servico->tipo_exame_id = $tipoExame->id;
            $servico->filial_id = $value;
            $servico->save();
        }

        return $tipoExame;

    }

    public function getTipoExame(Request $request) {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $tipoExame = $this->entity->findOne(
            'tipo_exames',
            [
                'where' => ['tipo_exames.id = ', $request['body']['id']],
                'orderBy' => [ 'tipo_exame_campos' => 'ordem ASC' ]
            ],
            Self::$ENTITY_WITH_CHILDS
        );

        if (!$tipoExame) throw new TipoExameNaoEncontradoException();

        return $tipoExame;

    }

    public function getProcedimentos(Request $request) {

        $tipoExames = $this->getTipoExames($request);

        foreach($tipoExames as $item) {
            $result[] = [
                'ProcedimentoId' => $item->TipoExameId,
                'ProcedimentoNome' => $item->TipoExameNome,
                'ProcedimentoTipoAtendimento' => $item->TipoExameAtendimento
            ];
        }
        return $result;
    }

    private function getTipoExamesMedico($medicoId) {
        $medico =  Medico::where('id', $medicoId)->first();
        if (!$medico) throw new MedicoNaoEncontradoException();
        $exames = $medico->exames();
        $result = [];
        foreach($exames as $exame) {
            $tipoExame = TipoExame::where('id', $exame->tipo_exame_id)->first();
            if (!$tipoExame) continue;
            $result[] = array('TipoExameId' => $tipoExame->id, 'TipoExameNome' => $tipoExame->nome);
        }
        return $result;
    }

    public function getTipoExames(Request $request) {

        $this->validarRequisicao($request);

        $data = isset($request['body']) ? $request['body'] : null;

        if ($data && $data['medico_id']) {
            return $this->getTipoExamesMedico($data['medico_id']);
        }

        $empresa = $this->entity->findOne(
            'empresas',
            ['where' => ['empresas.login = ', $this->getEmpresaDoDominio($request)]],
        );

        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $tipoExames = $this->entity->findAll(
            'tipo_exames',
            [
                'where' => [
                    'tipo_exames.empresa_id = ',
                    $empresa->EmpresaMatriz,
                    //' and tipo_exames.situacao = ', 0
                ],

                'orderBy' => [ 'tipo_exame_campos' => 'ordem ASC' ]
            ],
        );

        return $tipoExames;
    }
}
