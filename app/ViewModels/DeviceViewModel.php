<?php

namespace App\ViewModels;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use Spatie\ViewModels\ViewModel;

use App\Exceptions\DispositivoNaoRegistradoException;
use App\Exceptions\LoginBloqueadoException;
use App\Exceptions\CodigoDeValidacaoIncorretoException;

use App\Models\Usuario;

class DeviceViewModel extends BaseViewModel
{
    public function __construct()
    {
    }

    public static function getDeviceHash($login)
    {
        if ($login['UsuarioV2'] == Usuario::$V2_INATIVO) return array('H' => false);
        $hash = Hash::make($login['UsuarioId'] . Carbon::now());
        if ($login['UsuarioDeviceId'] == null) {
            $usuario = Usuario::where('id', $login['UsuarioId'])->first();
            $usuario->device_id = $hash;
            $usuario->save();
            $hash .= random_int(100,999);
        }

        Log::Debug("DeviceViewModel.getDeviceHash ================================================================");
        Log::Debug('   HASH: ' . $hash);
        Log::Debug("DeviceViewModel.getDeviceHash ================================================================");

        return array(
            'H' => $hash,
        );
    }

    public static function doValidateDevice($data) {

        $usuario = Usuario::where('device_id', $data['R'])->first();
        if (!$usuario || !$usuario->device_id) throw new DispositivoNaoRegistradoException();
        if ($usuario->situacao == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
    }

    public static function doAuthenticate($data) {
        $usuario = Usuario::where('login', $data['U'])->first();
        if (!$usuario || !$usuario['device_id']) throw new DispositivoNaoRegistradoException();
        if ($usuario->situacao == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
        if ($usuario->device_code == $data['code'])  return array('U' => $usuario->login);
        throw new CodigoDeValidacaoIncorretoException();
    }

    public static function getValidateCode($data)
    {
        $usuario = Usuario::where('device_id', $data['R'])->first();
        if (!$usuario || !$usuario['device_id']) throw new DispositivoNaoRegistradoException();
        if ($usuario->situacao == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
        $usuario->device_code = random_int(10000, 99999);
        $usuario->save();
        return array('C' => $usuario->device_code);
    }

}
