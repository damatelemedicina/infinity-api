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

use App\Exceptions\UsuarioNaoEncontradoException;
use App\Exceptions\ClienteNaoEncontradoException;

use App\Exceptions\MedicoNaoEncontradoException;
use App\ViewModels\RelatorioViewModel;

use App\Exports\FinanceiroClienteExport;
use App\Exports\FinanceiroMedicoExport;
use App\Exports\FaturamentoPacoteExport;

use App\Models\Medico;
use App\Models\Cliente;
use App\Models\Usuario;

use App\Utils\Utils;
use Maatwebsite\Excel\Facades\Excel;

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
        if (empty($laudosDoCliente)) {
            throw new FinanceiroException("Nenhum laudo encontrado para o cliente informado!");
        }
        
        $cliente = Cliente::where('id', $clienteId)->first();
        $nome_cliente = $cliente ? $cliente->nome : 'TODOS';
        $name = 'RF_I' . $clienteId  . '_' . $nome_cliente . '-cliente-Infinity-' . '.xls';
        Excel::store(new FinanceiroClienteExport($laudosDoCliente), $name);
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
        if ($exportar == true && empty($laudosDoMedico) == true) {
            throw new FinanceiroException("Não foram encontrados dados para exportar!");
        }
        return $exportar ? $this->exportarLaudosDoMedico($medicoId, $laudosDoMedico) : $laudosDoMedico;
    }

    private function exportarLaudosDoMedico($medicoId, $laudosDoMedico) {
        $medico = Medico::where('id', $medicoId)->first();
        $nome = str_replace(' ', '_', $medico->nome);
        $name = 'RM_' . $medicoId . '_' . $nome . '_' . Utils::getMesAno() . '.xls';
        Excel::store(new FinanceiroMedicoExport($laudosDoMedico), $name);
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

    public function faturamentoPacotesTotal(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $clienteId = $this->isNullOrEmptyValue($request['body']['ClienteId']) ? 0 : $request['body']['ClienteId'];
        $dataInicial = $this->toDataInicial($request['body']['ClienteDe']);
        $dataFinal = $this->toDataFinal($request['body']['ClienteAte']);
        $faturamentoPacote = RelatorioViewModel::GetFaturamentoPacotes($empresa->id, $clienteId, $dataInicial, $dataFinal);

        $faturamentoPacoteTotal = array_reduce($faturamentoPacote, function ($acc, $cur) {
            $key = $cur['cliente'];
            if (empty($acc[$key]) === true) {
                $acc[$key] = [
                    'cliente' => $cur['cliente'],
                    'total_exames' => 0,
                ];
            }

            $acc[$key]['total_exames'] = bcadd($acc[$key]['total_exames'], $cur['total_exames'], 6);

            return $acc;
        }, []);

        $faturamentoPacoteTotal = array_values($faturamentoPacoteTotal);

        $faturamentoPacoteTotal = array_map(function ($item) {
            $item['total_exames_formatado'] = 'R$ ' . number_format($item['total_exames'], 2, ',', '.');

            return $item;
        }, $faturamentoPacoteTotal);

        return $faturamentoPacoteTotal;
    }

    private function exportarFaturamentoPacotes($clienteId, $faturamentoPacote) {
        $cliente = Cliente::where('id', $clienteId)->first();
        $id = $cliente ? $cliente->id : 0;
        $nome = $cliente ? $cliente->nome : 'TODOS';
        $name = 'RP_I' . $id  . '_' . $nome . '-cliente-Infinity-' . '.xls';
        Excel::store(new FaturamentoPacoteExport($faturamentoPacote), $name);
        return ['name' => $name];
    }

    public function pesquisaAvancada(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $statusExame = $request['body']['PesquisaStatus'] == '' ? Self::$TODOS_OS_STATUS : $request['body']['PesquisaStatus'];
        $inativos = isset($request['body']) && isset($request['body']['inativos']) ? 1 : 0;
        if ($statusExame == Self::$WXML_INCOMPLETO) {
            return RelatorioViewModel::GetWXMLIncompleto($empresa->id, $inativos);
        }
        $data = $this->toDataIntervalo();
        $dataInicial = $this->isNullOrEmptyValue($request['body']['PesquisaDe']) ? $data['inicial'] : $this->toDataInicial($request['body']['PesquisaDe']);
        $dataFinal = $this->isNullOrEmptyValue($request['body']['PesquisaAte'])  ? $data['final'] : $this->toDataFinal($request['body']['PesquisaAte']);
        $clienteId = $this->isNullOrEmptyValue($request['body']['PesquisaCliente']) ? 0 : $request['body']['PesquisaCliente'];
        $medicoId = $this->isNullOrEmptyValue($request['body']['PesquisaMedico']) ? 0 : $request['body']['PesquisaMedico'];
        $tipoExameId = $this->isNullOrEmptyValue($request['body']['PesquisaTipoExame']) ? 0 : $request['body']['PesquisaTipoExame'];
        return RelatorioViewModel::GetPesquisaAvancada($empresa->id, $dataInicial, $dataFinal, $clienteId, $medicoId, $statusExame, $inativos, $tipoExameId);
    }

    public function pesquisaAvancadaCliente(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));
        $usuario = Usuario::where('login', $request['session']['login'])->first();
        if ($usuario == null) throw new UsuarioNaoEncontradoException();
        $cliente = Cliente::where('id', $usuario->conta_cliente)->first();
        if ($cliente == null) throw new ClienteNaoEncontradoException();
        $data = $this->toDataIntervalo();
        $dataInicial = $this->isNullOrEmptyValue($request['body']['PesquisaClienteDe']) ? $data['inicial'] : $this->toDataInicial($request['body']['PesquisaClienteDe']);
        $dataFinal = $this->isNullOrEmptyValue($request['body']['PesquisaClienteAte'])  ? $data['final'] : $this->toDataFinal($request['body']['PesquisaClienteAte']);
        $statusExame = $request['body']['PesquisaClienteStatus'] == '' ? Self::$TODOS_OS_STATUS : $request['body']['PesquisaClienteStatus'];
        $tipoExameId = $this->isNullOrEmptyValue($request['body']['PesquisaClienteTipoExame']) ? 0 : $request['body']['PesquisaClienteTipoExame'];
        $inativos = isset($request['body']) && isset($request['body']['inativos']) ? 1 : 0;
        $usuarioId = $this->isNullOrEmptyValue($request['body']['PesquisaClienteUsuario']) ? 0 : $request['body']['PesquisaClienteUsuario'];
        $usuarioId = $usuario->restringir_exames == 1 ? $usuario->id : $usuarioId;
        return RelatorioViewModel::GetPesquisaAvancadaCliente($dataInicial, $dataFinal, $cliente->id, $statusExame, $tipoExameId, $inativos, $usuarioId);
    }

    public function pesquisaAvancadaMedico(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $this->getEmpresaByLogin($this->getEmpresaDoDominio($request));

        $usuario = Usuario::where('login', $request['session']['login'])->first();
        if ($usuario == null) throw new UsuarioNaoEncontradoException();
        $medico = Medico::where('id', $usuario->conta_medico)->first();
        if ($medico == null) throw new MedicoNaoEncontradoException();
        $dataInicial = $this->isNullOrEmptyValue($request['body']['PesquisaMedicoDe']) ? null : (date_create_from_format('d/m/Y',$request['body']['PesquisaMedicoDe'])->format('Y-m-d 00:00:00'));
        $dataFinal = $this->isNullOrEmptyValue($request['body']['PesquisaMedicoAte'])  ? null : (date_create_from_format('d/m/Y',$request['body']['PesquisaMedicoAte'])->format('Y-m-d 23:59:59'));
        $statusExame = $request['body']['PesquisaMedicoStatus'] == '' ? Self::$TODOS_OS_STATUS : $request['body']['PesquisaMedicoStatus'];
        $tipoExameId = $this->isNullOrEmptyValue($request['body']['PesquisaMedicoTipoExame']) ? 0 : $request['body']['PesquisaMedicoTipoExame'];
        $inativos = isset($request['body']) && isset($request['body']['inativos']) ? 1 : 0;
        return RelatorioViewModel::GetPesquisaAvancadaMedico($dataInicial, $dataFinal, $medico->id, $statusExame, $tipoExameId, $inativos);
    }

}
