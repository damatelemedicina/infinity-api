<?php

namespace App\ViewModels;

class ClienteViewModel extends BaseViewModel
{
    public static function GetClientes($empresaId) {
        $sql = "CALL GetClientes({$empresaId})";
        return Self::callStoredProcedure($sql);
    }
}
