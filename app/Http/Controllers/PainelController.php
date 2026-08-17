<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Empresa;
use App\ViewModels\PainelViewModel;

class PainelController extends Controller
{
    function __construct() {
    }

    function getPainel(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        $usuario = $this->getUsuarioLogado($request);
        $isAdmin = ($usuario->conta_cliente + $usuario->conta_medico) == 0;
        return PainelViewModel::getPainel(
            $usuario->conta_cliente,
            $usuario->conta_medico,
            $isAdmin ? $empresa->matriz : 0);
    }

}
