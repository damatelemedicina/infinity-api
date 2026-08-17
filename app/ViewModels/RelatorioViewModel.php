<?php

namespace App\ViewModels;

class RelatorioViewModel extends BaseViewModel {

    public function __construct()
    {
    }

    public static function GetLaudosDoCliente($empresaId, $clienteId, $dataInicial, $dataFinal) {
        $sql = "CALL GetLaudosDoCliente({$empresaId},{$clienteId}, '{$dataInicial}','{$dataFinal}')";
        \Log::Debug($sql);
        return Self::callStoredProcedure($sql);
    }

    public static function GetLaudosDoMedico($medicoId, $dataInicial, $dataFinal) {
        $sql = "CALL GetLaudosDoMedico({$medicoId},'{$dataInicial}', '{$dataFinal}')";
        return Self::callStoredProcedure($sql);
    }

    public static function GetFaturamentoExportar($empresaId, $dataInicial, $dataFinal, $exportar = 0) {
        $sql = "CALL GetFaturamentoExportar({$empresaId},'{$dataInicial}', '{$dataFinal}', {$exportar})";
        return Self::callStoredProcedure($sql);
    }

    public static function GetFaturamentoPacotes($empresaId, $clienteId, $dataInicial, $dataFinal) {
        $sql = "CALL GetFaturamentoPacotes({$empresaId}, {$clienteId}, '{$dataInicial}', '{$dataFinal}')";
        return Self::callStoredProcedure($sql);
    }

    public static function GetPesquisaAvancada($empresaId, $dataInicial, $dataFinal, $clienteId, $medicoId, $statusExame) {
        $sql = "CALL GetPesquisaAvancada({$empresaId}, '{$dataInicial}', '{$dataFinal}', {$clienteId}, {$medicoId}, {$statusExame})";
        return Self::callStoredProcedure($sql);
    }

    public static function GetWXMLIncompleto($empresaId) {
        $sql = "CALL GetWXMLIncompleto({$empresaId})";
        return Self::callStoredProcedure($sql);
    }

}
