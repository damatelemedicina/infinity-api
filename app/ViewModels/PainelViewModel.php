<?php

namespace App\ViewModels;

class PainelViewModel extends BaseViewModel
{
    public static function getPainel($clienteId, $medicoId, $matrizId) {
        $sql = "CALL GetPainel({$clienteId},{$medicoId},{$matrizId})";
        return Self::callStoredProcedures($sql);
    }
}
