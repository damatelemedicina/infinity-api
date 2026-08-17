<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ViewModels\LoginViewModel;

class LoginController extends Controller
{
    public function doAuthenticate(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);
        return response()->json(LoginViewModel::doAuthenticate($request['body']));
    }
}
