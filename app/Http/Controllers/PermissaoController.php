<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Permissao;

use Illuminate\Support\Facades\Log;

class PermissaoController extends Controller
{
    public function getPermissao(Request $request) {
        $this->validarRequisicao($request);
        Log::Debug('================= getPermissao ================');
        Log::Debug($request);
        Log::Debug('================= getPermissao ================');
        return [];
    }

}
