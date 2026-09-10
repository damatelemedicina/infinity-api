<?php

namespace App\ViewModels;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RelatorioViewModel extends BaseViewModel
{

    public function __construct() {}

    public static function GetLaudosDoCliente($empresaId, $clienteId, $dataInicial, $dataFinal)
    {
        $sql = "CALL GetLaudosDoCliente({$empresaId},{$clienteId}, '{$dataInicial}','{$dataFinal}')";
        return Self::callStoredProcedure($sql);
    }

    public static function GetLaudosDoMedico($medicoId, $dataInicial, $dataFinal)
    {
        $sql = "CALL GetLaudosDoMedico({$medicoId},'{$dataInicial}', '{$dataFinal}')";
        return Self::callStoredProcedure($sql);
    }

    public static function GetFaturamentoExportar($empresaId, $dataInicial, $dataFinal, $exportar = 0)
    {
        $sql = "CALL GetFaturamentoExportar({$empresaId},'{$dataInicial}', '{$dataFinal}', {$exportar})";
        return Self::callStoredProcedure($sql);
    }

    public static function GetFaturamentoPacotes($empresaId, $clienteId, $dataInicial, $dataFinal)
    {
        $sql = "CALL GetFaturamentoPacotes({$empresaId}, {$clienteId}, '{$dataInicial}', '{$dataFinal}')";
        return Self::callStoredProcedure($sql);
    }

    public static function GetPesquisaAvancada($empresaId, $dataInicial, $dataFinal, $clienteId, $medicoId, $statusExame, $inativos, $tipoExameId)
    {
        ini_set('memory_limit', '-1');
        $sql = "CALL GetPesquisaAvancada({$empresaId}, '{$dataInicial}', '{$dataFinal}', {$clienteId}, {$medicoId}, {$statusExame}, {$inativos}, {$tipoExameId})";
        return Self::callStoredProcedure($sql);
    }

    public static function GetPesquisaAvancadaCliente($dataInicial, $dataFinal, $clienteId, $statusExame, $tipoExameId, $inativos, $usuarioId)
    {
        $sql = "CALL GetPesquisaAvancadaCliente('{$dataInicial}', '{$dataFinal}', {$clienteId}, {$statusExame}, {$tipoExameId}, {$inativos}, {$usuarioId})";
        return Self::callStoredProcedure($sql);
    }

    public static function GetPesquisaAvancadaMedico($dataInicial, $dataFinal, $medicoId, $statusExame, $tipoExameId, $inativos)
    {
        $query = DB::table("exames")
            ->select(
                "exames.id AS id",
                "exames.cliente_id as cliente_id",
                "exames.paciente AS paciente",
                "tipo_exames.nome AS exame",
                "tipo_exames.id AS exame_id",
                "clientes.nome AS cliente",
                "empresas.login AS empresa",
                "exames.status AS status",
                "exames.emergencia AS emergencia",
                "exames.arquivo_exame AS arquivo_exame",
                "exames.arquivo_laudo AS arquivo_laudo",
                "exames.ativo AS ativo",
                "exames.abonado AS abonado",
                "exames.abonado_medico AS abonado_medico",
                "exames.medico_id AS medico",
                "exames.pausado AS pausado",
                "exames.soc_resultado_enviado",
                "exames.soc_seq_resultado",
                "exames.soc_nome_arquivo",
                "clientes.soc_codigo_empresa as soc_codigo_cliente",
                "empresas.soc_codigo_empresa_principal"
            )
            ->selectSub("select nome from medicos where medicos.id = exames.medico_id", "medico_nome")
            ->selectRaw("TRUE AS isMedico")
            ->addSelect(
                DB::raw("date_format(exames.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data"),
                DB::raw("date_format(exames.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data")
            )
            ->join("tipo_exames", "tipo_exames.id", "=", "exames.exame_id")
            ->join("empresas", "empresas.id", "=", "exames.empresa_id")
            ->join("clientes", "clientes.id", "=", "exames.cliente_id")
            ->where("exames.medico_id", "=", $medicoId)
            ->whereRaw("IF (? >= 0, exames.status = ?, TRUE)", [$statusExame, $statusExame])
            ->whereRaw("IF (? > 0, exames.exame_id = ?, TRUE)", [$tipoExameId, $tipoExameId])
            ->whereRaw("IF (? = 1, TRUE, exames.ativo = 1)", [$inativos])
            ->orderByDesc('exames.emergencia');

        empty($dataInicial) || $query->whereDate('exames.created_at', '>=', $dataInicial);
        empty($dataFinal) || $query->whereDate('exames.created_at', '<=', $dataFinal);

        return $query->get()->toArray();
    }

    public static function GetWXMLIncompleto($empresaId, $inativos)
    {
        $sql = "CALL GetWXMLIncompleto({$empresaId}, {$inativos})";
        return Self::callStoredProcedure($sql);
    }
}
