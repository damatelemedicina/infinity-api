<?php
// https://laravelcode.com/post/import-and-export-excel-file-in-laravel-7
// https://erickosma.medium.com/gerando-arquivos-excel-laravel-excel-d355254072c3
// https://docs.laravel-excel.com/3.1/exports/from-view.html

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\Log;

use App\Exceptions\FinanceiroException;
use App\Exceptions\FaturamentoException;

use App\ViewModels\RelatorioViewModel;

use App\Exports\FinanceiroClienteExport;
use App\Exports\FinanceiroMedicoExport;
use App\Exports\FaturamentoPacoteExport;

use App\Models\Medico;
use App\Models\Cliente;
use App\Utils\Utils;

class RelatorioController extends Controller
{

    public static $DATA_INICIAL = '2023-01-01 00:00:00';
    public static $DATA_FINAL   = '2030-12-31 23:59:59';
    public static $TODOS_OS_STATUS = -1;
    public static $WXML_INCOMPLETO = 4;

    private function toData($data) {
        $data = $this->isNullOrEmptyValue($data) ? date('d/m/YY') : $data;
        $ano = substr($data, 6, 4);
        $mes = substr($data, 3, 2);
        $dia = substr($data, 0, 2);
        return $ano . '-' . $mes . '-' . $dia;
    }

    public function financeiroClientes(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $clienteId = $this->isNullOrEmptyValue($request['body']['ClienteId']) ? 0 : $request['body']['ClienteId'];
        $dataInicial = $this->toDataInicial($request['body']['ClienteDe']);
        $dataFinal = $this->toDataFinal($request['body']['ClienteAte']);
        $exportar = isset($request['body']['exportar']) ? true : false;
        $laudosDoCliente = RelatorioViewModel::GetLaudosDoCliente($empresa->id, $clienteId, $dataInicial, $dataFinal);
        return $exportar ? $this->exportarLaudosDoCliente($clienteId, $laudosDoCliente) : $laudosDoCliente;
    }

    private function exportarLaudosDoCliente($clienteId, $laudosDoCliente) {
        $cliente = Cliente::where('id', $clienteId)->first();
        $nome_cliente = $cliente ? $cliente->nome : 'TODOS';
        $name = 'RF_I' . $clienteId  . '_' . $nome_cliente . '-cliente-Infinity-' . '.xls';
        \Excel::store(new FinanceiroClienteExport($laudosDoCliente), $name);
        return ['name' => $name];
    }

    public function financeiroMedicos(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $medicoId = $request['body']['MedicoId'];
        if ($this->isNullOrEmptyValue($medicoId)) throw new FinanceiroException("Médico não informado!");
        $dataInicial = $this->toDataInicial($request['body']['MedicoDe']);
        $dataFinal = $this->toDataFinal($request['body']['MedicoAte']);
        $exportar = isset($request['body']['exportar']) ? true : false;
        $laudosDoMedico = RelatorioViewModel::GetLaudosDoMedico($medicoId, $dataInicial, $dataFinal);
        return $exportar ? $this->exportarLaudosDoMedico($medicoId, $laudosDoMedico) : $laudosDoMedico;
    }

    private function exportarLaudosDoMedico($medicoId, $laudosDoMedico) {
        $medico = Medico::where('id', $medicoId)->first();
        $nome = str_replace(' ', '_', $medico->nome);
        $name = 'RM_' . $medicoId . '_' . $nome . '_' . Utils::getMesAno() . '.xls';
        \Excel::store(new FinanceiroMedicoExport($laudosDoMedico), $name);
        return ['name' => $name];
    }

    public function faturamentoClientes(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $dataInicial = $this->toDataInicial($request['body']['PeriodoDe']);
        $dataFinal = $this->toDataFinal($request['body']['PeriodoAte']);
        return $this->doFaturamentoExportar($empresa->id, $dataInicial, $dataFinal, 0);
    }

    public function faturamentoExportar(Request $request) {
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $dataInicial = $this->toDataInicial($request['body']['PeriodoDe']);
        $dataFinal = $this->toDataFinal($request['body']['PeriodoAte']);
        $result = $this->doFaturamentoExportar($empresa->id, $dataInicial, $dataFinal, 1);
        $content = "cliente_id;exame_id;COUNT(id)\r\n";
        foreach ($result as $item){
            $content .= "{$item['cliente']};{$item['tipo_exame']};{$item['quantidade']}\r\n";
        }
        $content = strlen($content) > 0 ? substr($content, 0, strlen($content) - 2) : '';
        $name = md5(uniqid(rand(), true)).'.txt';
        $file = fopen(storage_path($name), "wb");
        fwrite($file, $content);
        fclose($file);
        return ['file' => $name];
    }

    private function doFaturamentoExportar($empresaId, $dataInicial, $dataFinal, $exportar = 0) {
        return RelatorioViewModel::GetFaturamentoExportar($empresaId, $dataInicial, $dataFinal, $exportar);
    }

    public function faturamentoPacotes(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $clienteId = $this->isNullOrEmptyValue($request['body']['ClienteId']) ? 0 : $request['body']['ClienteId'];
        $dataInicial = $this->toDataInicial($request['body']['ClienteDe']);
        $dataFinal = $this->toDataFinal($request['body']['ClienteAte']);
        $exportar = isset($request['body']['exportar']) ? true : false;
        $faturamentoPacote = RelatorioViewModel::GetFaturamentoPacotes($empresa->id, $clienteId, $dataInicial, $dataFinal);
        return $exportar ? $this->exportarFaturamentoPacotes($clienteId, $faturamentoPacote) : $faturamentoPacote;
    }

    private function exportarFaturamentoPacotes($clienteId, $faturamentoPacote) {
        $cliente = Cliente::where('id', $clienteId)->first();
        $id = $cliente ? $cliente->id : 0;
        $nome = $cliente ? $cliente->nome : 'TODOS';
        $name = 'RP_I' . $id  . '_' . $nome . '-cliente-Infinity-' . '.xls';
        \Excel::store(new FaturamentoPacoteExport($faturamentoPacote), $name);
        return ['name' => $name];
    }

    public function pesquisaAvancada(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $statusExame = $request['body']['PesquisaStatus'] == '' ? Self::$TODOS_OS_STATUS : $request['body']['PesquisaStatus'];
        if ($statusExame == Self::$WXML_INCOMPLETO) {
            return RelatorioViewModel::GetWXMLIncompleto($empresa->id);
        }
        $dataInicial = $this->isNullOrEmptyValue($request['body']['PesquisaDe']) ? Self::$DATA_INICIAL : $this->toDataInicial($request['body']['PesquisaDe']);
        $dataFinal = $this->isNullOrEmptyValue($request['body']['PesquisaAte'])  ? Self::$DATA_FINAL : $this->toDataFinal($request['body']['PesquisaAte']);
        $clienteId = $this->isNullOrEmptyValue($request['body']['PesquisaCliente']) ? 0 : $request['body']['PesquisaCliente'];
        $medicoId = $this->isNullOrEmptyValue($request['body']['PesquisaMedico']) ? 0 : $request['body']['PesquisaMedico'];
        return RelatorioViewModel::GetPesquisaAvancada($empresa->id, $dataInicial, $dataFinal, $clienteId, $medicoId, $statusExame);
    }

}
