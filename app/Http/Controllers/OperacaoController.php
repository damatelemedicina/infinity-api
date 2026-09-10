<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Services\DateTime\DateTimeService;
use App\Services\Db\DbManager;

use App\Exceptions\EmpresaNaoEncontradaException;

use App\Models\Operacao;
use App\Models\Empresa;

class OperacaoController extends Controller
{
    function __construct(DbManager $dbm, DateTimeService $dts) {
        $this->dbManager = $dbm;
        $this->dateTimeService = $dts;
    }

    function pesquisar(Request $request) {
        $this->validarRequisicao($request);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $usuario = $this->getUsuarioLogado($request);

        $body = $request['body'];

        $dataInicial = $this->dateTimeService->toDateFirstTime($body['OperacaoPeriodoDe']);
        $dataFinal = $this->dateTimeService->toDateLastTime($body['OperacaoPeriodoAte']);

        $empresa_id = $this->isNullOrEmptyValue($body['OperacaoEmpresa']) ?
            ($empresa->isMatriz() ? 0 : $empresa->id) : $body['OperacaoEmpresa'];

        $cliente_id = $this->isNullOrEmptyValue($body['OperacaoCliente']) ? 0 : $body['OperacaoCliente'];
        $cliente_id = $usuario->conta_cliente == 0 ? $cliente_id : $usuario->conta_cliente;
        $usuario_id = $this->isNullOrEmptyValue($body['OperacaoUsuario']) ? 0 : $body['OperacaoUsuario'];
        $exame_id = $this->isNullOrEmptyValue($body['OperacaoExame']) ? 0 : $body['OperacaoExame'];
        $medico_id = $usuario->conta_medico == 0 ? 0 : $usuario->conta_medico;

        $sql = "CALL GetOperacoes('{$empresa_id}','{$cliente_id}','{$usuario_id}', '{$exame_id}', '{$medico_id}', '{$dataInicial}', '{$dataFinal}')";
        $rs = $this->dbManager->callStoredProcedure($sql);

        // Log::debug("============================================");
        // Log::debug($rs);
        // Log::debug("============================================");

        return $rs;
    }
}
