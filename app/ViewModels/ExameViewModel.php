<?php

namespace App\ViewModels;

class ExameViewModel extends BaseViewModel
{
    public function __construct()
    {
    }

    public static function getExamesDaEmpresa($id, $ativos, $data_inicio_pesquisa= '', $data_fim_pesquisa = '', $limit_pesquisa = '20000', $offset_pesquisa = '')
    {
        $sql = "CALL GetExamesDaEmpresa('{$id}', '{$ativos}', '{$data_inicio_pesquisa}', '{$data_fim_pesquisa}', '{$limit_pesquisa}', '{$offset_pesquisa}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getExamesDoCliente($id, $ativos, $usuarioId, $data_inicio_pesquisa= '', $data_fim_pesquisa = '', $limit_pesquisa = '20000', $offset_pesquisa = '')
    {
        $sql = "CALL GetExamesDoCliente('{$id}', '{$ativos}', '{$usuarioId}', '{$data_inicio_pesquisa}', '{$data_fim_pesquisa}', '{$limit_pesquisa}', '{$offset_pesquisa}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getExamesDoMedico($id, $ativos, $data_inicio_pesquisa= '', $data_fim_pesquisa = '', $limit_pesquisa = '20000', $offset_pesquisa = '')
    {
        $sql = "CALL GetExamesDoMedico('{$id}', '{$ativos}', '{$data_inicio_pesquisa}', '{$data_fim_pesquisa}', '{$limit_pesquisa}', '{$offset_pesquisa}')";
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
