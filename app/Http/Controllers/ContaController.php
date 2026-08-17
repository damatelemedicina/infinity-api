<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Exceptions\CadastroException;

use App\ViewModels\ContaViewModel;

use App\Models\Conta;


class ContaController extends Controller
{
    private static $VALORES_NAO_INFORMADOS = "Valores não informados!";
    private static $TODAY_IF_NULL = true;

    private function doValidate($data) {
        if ($this->isNullOrEmptyValue($data['ContaDescricao'])) throw new CadastroException("Descrição do lançamento não informado!");
        if ($this->isNullOrEmptyValue($data['ContaCliente'])) throw new CadastroException("Cliente não informado!");
        if (!is_numeric($data['ContaValor'])  || $data['ContaValor'] == 0) throw new CadastroException(Self::$VALORES_NAO_INFORMADOS);
        return $data;
    }

    function setConta(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $data = $this->doValidate($request['body']);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $conta = isset($data['ContaId']) ? Conta::where('id', $data['ContaId'])->first() : new Conta();
        $conta->empresa_id = $empresa->id;
        $conta->cliente_id = $data['ContaCliente'];
        $conta->descricao = $data['ContaDescricao'];
        $conta->valor = $data['ContaValor'];
        $conta->data = $this->toDateTime($data['ContaData'], Self::$TODAY_IF_NULL);
        $conta->save();
        return ['id' => $conta->id ];
    }

    function getConta(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $conta = Conta::where('id', $request['body']['id'])->first();
        if (!$conta) throw new CadastroException("Conta não encontrado!");
        return $conta;
    }

    function getContas(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $contas = Conta::where('empresa_id', $empresa->id)->get();
        return $contas;
    }

    private function doSaldoValidate($request) {
        if ($this->isNullOrEmptyValue($request['body']['ExtratoCliente'])) throw new CadastroException('Informe o cliente!');
        if ($this->isNullOrEmptyValue($request['body']['ExtratoDias'])) throw new CadastroException('Informe o período!');
    }

    function getPeriodo($dias) {
        $dias = preg_replace('/[^0-9]/', '', $dias);
        $periodo = array(
            'de' => new \DateTime(),
            'ate' => new \DateTime()
        );
        date_sub($periodo['de'], \DateInterval::createFromDateString("$dias day"));
        $periodo['de'] = $periodo['de']->format('d/m/Y');
        $periodo['ate'] = $periodo['ate']->format('d/m/Y');
        return $periodo;
    }
    function getSaldo(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $periodo = $this->getPeriodo($request['body']['ExtratoDias']);
        return ContaViewModel::GetSaldoDoCliente(
            $request['body']['ExtratoCliente'],
            $this->toDataInicial($periodo['de']),
            $this->toDataFinal($periodo['ate'])
        );
    }

}
