<?php

namespace App\ViewModels;

class ExameViewModel extends BaseViewModel
{
    public function __construct()
    {
    }

    public static function getExamesDaEmpresa($id, $ativos)
    {
        $sql = "CALL GetExamesDaEmpresa('{$id}', '{$ativos}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getExamesDoCliente($id, $ativos)
    {
        $sql = "CALL GetExamesDoCliente('{$id}', '{$ativos}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getExamesDoMedico($id, $ativos)
    {
        $sql = "CALL GetExamesDoMedico('{$id}', '{$ativos}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getExameParaLaudar($id)
    {
        $sql = "CALL GetExameParaLaudar('{$id}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getMedicosDoExame($tipoExameId, $matrizId) {
        $sql = "CALL GetMedicosDoExame('{$tipoExameId}','{$matrizId}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getLaudosParaDownload($clienteId) {
        $sql = "CALL GetLaudosParaDownload('{$clienteId}')";
        return Self::callStoredProcedure($sql);
    }

}
