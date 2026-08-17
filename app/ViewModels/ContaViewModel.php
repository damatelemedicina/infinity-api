<?php

namespace App\ViewModels;

class ContaViewModel extends BaseViewModel
{
    public static function GetSaldoDoCliente($clienteId, $dataDe, $dataAte) {
        $sql = "CALL GetSaldoDoCliente({$clienteId},'{$dataDe}', '{$dataAte}')";
        return Self::callStoredProcedure($sql);
    }
}
