<?php

namespace App\ViewModels;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Spatie\ViewModels\ViewModel;

use App\Models\Usuario;

class UsuarioViewModel extends BaseViewModel
{
    public function __construct()
    {
        //
    }

    private static function getDominios(Usuario $usuario)
    {
        $sql = "CALL GetDominiosDoUsuario('{$usuario->login}')";
        return Self::callStoredProcedure($sql);
    }

    public static function getUsuario(Usuario $usuario) 
    {
        return array(
            'UsuarioId' => $usuario->id,
            'UsuarioLogin' => $usuario->login,
            'UsuarioNome' => $usuario->nome,
            'UsuarioEmail' => $usuario->email,
            'UsuarioCpf' => $usuario->cpf,
            'UsuarioV2' => $usuario->v2,
            'UsuarioPerfilId' => $usuario->perfil_id,
            'UsuarioSituacao' => $usuario->situacao,
            'UsuarioDeviceId' => $usuario->device_id,
            'UsuarioAcessos' => Self::getDominios($usuario)
        );

    }

}
