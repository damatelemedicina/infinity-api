<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\DespachoException;

use App\Models\Medico;
use App\Models\DespachoRegra;
use App\Models\DespachoFila;
use App\Models\Exame;
use App\ViewModels\ExameViewModel;

use App\Exceptions\MedicoNaoEncontradoException;
use App\Exceptions\UsuarioNaoAssociadoAMedicoException;
use App\Exceptions\MedicoEstaNaFilaException;

class DespachoController extends Controller
{

    private static $MEDICO_NAO_INFORMADO = "Médico não informado!";
    private static $TIPO_DE_REGRA_NAO_INFORMADA = "Tipo de regra não informada!";
    private static $NOME_DA_REGRA_NAO_INFORMADA = "Nome da regra não informada!";
    private static $TIPO_DE_EXAME_NAO_INFORMADO = "Tipo de exame não informado!";
    private static $REGRA_NAO_ENCONTRADA = "Regra não encontrada!";

    private static $SEMANA_TODA = "DOM,SEG,TER,QUA,QUI,SEX,SAB";
    private static $PRIMEIRA_HORA = '00:00:00';
    private static $ULTIMA_HORA = '23:59:00';

    private static $REGRA_EXCLUSIVIDADE = "EXCLUSIVIDADE";

    public function getRegras(Request $request) {
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        return DespachoRegra::where('empresa_id', $empresa->id)->get();
    }

    private function doValidate($data) {
        if (!$data['DespachoNomeRegra']) throw new DespachoException(Self::$NOME_DA_REGRA_NAO_INFORMADA);
        if (!$data['DespachoTipoRegra']) throw new DespachoException(Self::$TIPO_DE_REGRA_NAO_INFORMADA);
        if (!$data['DespachoMedico']) throw new DespachoException(Self::$MEDICO_NAO_INFORMADO);
        if (!$data['DespachoTipoExame']) throw new DespachoException(Self::$TIPO_DE_EXAME_NAO_INFORMADO);
    }

    public function setRegraDespacho(Request $request) {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));

        $data = $request["body"];
        $this->doValidate($data);

        $regra = isset($data['DespachoId'])
            ? DespachoRegra::where('id', $data['DespachoId'])->first()
            : new DespachoRegra();

        $regra->nome = $data['DespachoNomeRegra'];
        $regra->tipo = $data['DespachoTipoRegra'];
        $regra->ativa = $data['DespachoRegraAtiva'];

        $regra->empresa_id = $empresa->id;
        $regra->medico_id = $data['DespachoMedico'];

        $regra->cliente_id = $this->checkIfNull($data['DespachoCliente'], 0);
        $regra->tipo_exame_id = $this->checkIfNull($data['DespachoTipoExame'], 0);
        $regra->quantidade = $this->checkIfNull($data['DespachoQuantidade'], 0);
        $regra->dias = $this->checkIfNull($data['DespachoDias'], Self::$SEMANA_TODA);
        $regra->hora_inicial = $this->checkIfNull($data['DespachoHoraInicial'], Self::$PRIMEIRA_HORA);
        $regra->hora_final = $this->checkIfNull($data['DespachoHoraFinal'], Self::$ULTIMA_HORA);
        $regra->incompleto = $regra->tipo == Self::$REGRA_EXCLUSIVIDADE ? $data['DespachoIncompleto'] : false;

        $regra->save();

        $this->liberarExames(
            $regra->medico_id,
            $this->checkIfNull($data['DespachoLiberarExames'], false)
        );

        return ['id' => $regra->id];
    }

   public function getRegraDespacho(Request $request){
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $regra = DespachoRegra::where('id', $request['body']['id'])->first();
        $regra->dias = explode(',', $regra->dias);
        if (!$regra) throw new DespachoException(Self::$REGRA_NAO_ENCONTRADA);
        return $regra;
    }

    public function getRegrasDespacho(Request $request){
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        return DespachoRegra::where('empresa_id', $empresa->id)->get();
    }

    public function setFilaDespacho(Request $request) {
        $this->validarRequisicao($request);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $usuario = $this->getUsuarioLogado($request);
        if ($usuario->conta_medico == Self::$MEDICO_NAO_DEFINIDO) throw new UsuarioNaoAssociadoAMedicoException();
        $medico = Medico::where('id', $usuario->conta_medico)->first();
        if (!$medico) throw new MedicoNaoEncontradoException();

        $exames = Exame::where([
            'medico_id' => $medico->id,
            'status' => 0,
            'ativo' => 1,
            'pausado' => null
        ])->get();
        if (count($exames) > 0) throw new DespachoException("Já há exame(s) aguardando laudo!");

        $fila = DespachoFila::where([
             'medico_id' => $medico->id,
             'status' => 0
         ])->first();
         if ($fila) throw new DespachoException("Em breve seu exame estará disponível!");

        $examesERecusas = $this->getTiposDeExamesERecusas($medico);

        $fila = new DespachoFila();
        $fila->empresa_id = $empresa->id;
        $fila->medico_id = $medico->id;
        $fila->exames =  $examesERecusas['exames'];
        $fila->recusas = $examesERecusas['recusas'];
        $fila->status = Self::$FILA_AGUARDANDO;
        $fila->save();

        return ['id' => $fila->id];
    }

    private function getTiposDeExamesERecusas($medico) {
        $exames = $medico->exames();
        if (!$exames) throw new DespachoException("Nenhum tipo de exame definido!");
        $result = array('exames' => '', 'recusas' => '');
        foreach($exames as $exame) {
            $result['exames'] .= $exame->tipo_exame_id . ',';
            $result['recusas'] .= $exame->recusa ? ($exame->tipo_exame_id . ',') : '';
        }
        $result['exames'] = rtrim($result['exames'], ",");
        $result['recusas'] = rtrim($result['recusas'], ",");
        return $result;
    }

    private function getTiposDeExame($medico) {
        $exames = $medico->exames();
        if (!$exames) throw new DespachoException("Nenhum tipo de exame definido!");
        $result = '';
        foreach($exames as $exame) {
            $result .= $exame->tipo_exame_id . ',';
        }
        return rtrim($result, ",");
    }

    public function getFilaDespacho(Request $request) {
        \Log::Debug($request);
        return [];
    }

}
