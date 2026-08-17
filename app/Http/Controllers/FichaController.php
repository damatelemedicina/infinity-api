<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use App\Services\Entity\EntityManager;
use App\Services\Db\DbManager;
use App\Services\DateTime\DateTimeService;

use App\Exceptions\FichaNaoEncontradaException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\ClienteNaoEncontradoException;

use Illuminate\Support\Facades\Log;
use App\Models\Empresa;
use App\Models\Ficha;
use App\Models\Cliente;
use App\Models\Paciente;
use App\Models\MedicoSolicitante;

class FichaController extends Controller
{
    
    protected $entity;
    protected $dbManager;
    protected $dateTimeService;

    public function __construct(EntityManager $entity, DbManager $dbm, DateTimeService $dts)
    {
        $this->entity = $entity;
        $this->dbManager = $dbm;
        $this->dateTimeService = $dts;
    }

    function getFichas(Request $request) 
    {
        $this->validarRequisicao($request);
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $body = $request['body'];

        $documento = $body['documento'] ? ( '%' . $body['documento'] . '%' ) : '';
        $paciente = $body['paciente'] ? ( '%' . $body['paciente'] . '%' ) : '';

        $dataInicial = $this->dateTimeService->toDateFirstTime($body['dataInicial']);
        $dataFinal = $this->dateTimeService->toDateLastTime($body['dataFinal']);

        $sql = "CALL GetFichas('{$empresa->id}','{$documento}','{$paciente}', '{$dataInicial}', '{$dataFinal}')";
        $rs = $this->dbManager->callStoredProcedure($sql);

        //Log::debug("============================================");
        //Log::debug($rs);
        //Log::debug("============================================");

        return $rs;        
    }

    function getDateTimeFromDateString($data) {
        return 
            substr($data, 6, 4) . '-' . 
            substr($data, 3, 2) . '-' . 
            substr($data, 0, 2) . ' ' .
            '00:00:00';
    }

    function getPaciente($data, $empresa_id) {
        $paciente = Paciente::where('doc', $data['PacienteDoc'])->first();
        if (!$paciente) $paciente = new Paciente();
        $autoDoc = !$data['PacienteDoc'];
        $paciente->nome = $data['PacienteNome'];
        $paciente->sexo = $data['PacienteSexo'];
        $paciente->doc = $autoDoc ? md5(uniqid("")) : $data['PacienteDoc'];
        $paciente->empresa = isset($data['PacienteEmpresa']) ? $data['PacienteEmpresa'] : '';
        $paciente->funcao = isset($data['PacienteFuncao']) ? $data['PacienteFuncao'] : '';
        $paciente->nasc = $this->getDateTimeFromDateString($data['PacienteNasc']);
        $paciente->empresa_id = $empresa_id;
        $paciente->save();
        $paciente->doc = $autoDoc ? $paciente->id : $paciente->doc;
        $paciente->save();
        return $paciente;
    }

    function getNumero($empresa, $cliente, $ficha) {
        return 
            substr(trim($empresa->login), 0, 2) . 
            strval($ficha->id + 1000) . 
            substr(trim($cliente->nome), 0, 2);
    }

    function getProcedimentos($data) {
        $res = '';

        foreach($data['FichaProcedimentos'] as $item) {
            if (!$item['ProcedimentoSelecionado']) continue;
            $res .= $item['ProcedimentoId'] . ';';
        }
        return $res; //substr($res,-1);
    }

    function getFicha(Request $request) {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $ficha = $this->getFichaDaEmpresa($request);
        if (!$ficha) throw new FichaNaoEncontradaException();

        //$ficha = Ficha::where('numero', strtoupper($request['body']['id']))->first();
        //if (!$ficha) throw new FichaNaoEncontradaException();

        $ficha->Paciente = Paciente::where('id', $ficha->paciente_id)->first();
        $ficha->MedicoSolicitante = MedicoSolicitante::where('id', $ficha->medico_solicitante_id)->first();

        $ficha->FichaProcedimentos = $this->getProcedimentosSelecionados($ficha);

        return $ficha;

    }

    private function getProcedimentosSelecionados($ficha) {
        $cliente = Cliente::where('id', $ficha->cliente_id)->first();
        if (!$cliente) throw new ClienteNaoEncontradoException();

        $servicos = []; 

        foreach($cliente->servicos() as $servico) {
          $servicos[] = array(
            'ProcedimentoId' => $servico->tipo_exame_id,
            'ProcedimentoSelecionado' => strpos(
                $ficha->procedimentos,
                strval($servico->tipo_exame_id)
            ) !== false
          );
        }
        
        return $servicos;

    }

    function setFicha(Request $request) {
        
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        
        $data = $request['body'];
        
        $ficha = isset($data['FichaId']) 
                    ? Ficha::where('id', $data['FichaId'])->first()
                    : new Ficha();

        $cliente = Cliente::where('id', $data['ClienteId'])->first();
        if (!$cliente) throw new ClienteNaoEncontradoException();

        $paciente = $this->getPaciente($data, $this->getMatriz($request));

        $ficha->atendimento = $data['FichaTipoAtendimento' ];
        $ficha->motivo_exame_id = $data['FichaMotivo' ];
        $ficha->medico_solicitante_id = $data['MedicoSolicitanteId'];
        $ficha->paciente_id = $paciente->id;
        $ficha->cliente_id = $cliente->id; 
        $ficha->empresa_id = $empresa->id;
        $ficha->procedimentos = $this->getProcedimentos($data);

        if (!isset($data['FichaNumero'])) {
            $ficha->numero =  Str::random(9);
            $ficha->save();
            $ficha->numero = $this->getNumero($empresa, $cliente, $ficha);
        }

        $ficha->save();

        return [];

    }

}
