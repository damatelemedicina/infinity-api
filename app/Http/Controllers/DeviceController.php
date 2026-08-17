<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\ViewModels\DeviceViewModel;
use App\ViewModels\LoginViewModel;

use App\Exceptions\SessaoDeValidacaoExpiradaException;

class DeviceController extends Controller
{
    
    public function __construct()
    {
    }

    public function code($id) 
    {
        return response()->json(array('id' => $id));
    }

    public function doValidateDevice(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);
        return response()->json(DeviceViewModel::doValidateDevice($request['body']));
    }

    private function getHash($request)
    {
        if (!array_key_exists('H', $request['body'])) throw new SessaoDeValidacaoExpiradaException();
        $hash = $request['body']['H'];
        if (strlen($hash) == 60) return $hash;
        return substr($hash,0,60);
    }

    public function doValidateRegister(Request $request)
    {
        
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['body']['usuario'],
            $request['body']['senha']
        );

        if ($this->getHash($request) == $login['UsuarioDeviceId'])
            return response([ 'R' => $login['UsuarioDeviceId']]);

        throw new SessaoDeValidacaoExpiradaException();
    }

    public function getValidateCode(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);
        return response()->json(DeviceViewModel::getValidateCode($request['body']));
    }

    public function doAuthenticate(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);
        return response()->json(DeviceViewModel::doAuthenticate($request['body']));
    }

    public function getDeviceHash(Request $request) 
    {
        
        $this->validarRequisicao($request, Self::$BODY_REQUIRED, Self::$SESSION_NOT_REQUIRED);

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['body']['usuario'],
            $request['body']['senha']
        );

        return response()->json(DeviceViewModel::getDeviceHash($login));
    }

}
