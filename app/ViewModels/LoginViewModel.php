<?php

namespace App\ViewModels;

use Spatie\ViewModels\ViewModel;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

use App\Exceptions\LoginInvalidoException;
use App\Exceptions\LoginBloqueadoException;
use App\Exceptions\SenhaInvalidaException;
use App\Exceptions\CodigoDeValidacaoIncorretoException;
use App\Exceptions\ClienteBloqueadoException;
use App\Exceptions\BloqueioException;


use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Perfil;
use App\Models\Acesso;

class LoginViewModel extends BaseViewModel
{
    public function __construct()
    {
        //
    }

    public static function getLogin($empresa, $usuario, $senha = null)
    {
        $usuario = strtolower($usuario);
        $sql = "CALL GetLoginDoUsuario('{$empresa}','{$usuario}')";
        $rs = Self::callStoredProcedure(
            $sql,
            new LoginInvalidoException(),
            Self::$ARRAY_EXTRACT_ONE
        );
        if ($rs['UsuarioSituacao'] == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
        if ($rs['UsuarioInativo'] == Usuario::$INATIVO) throw new BloqueioException('Login inativo!');
        if ($senha == null) return $rs;
        if (!Hash::check($senha, $rs['UsuarioSenha'])) throw new SenhaInvalidaException();
        $usuario = Usuario::where('id', $rs['UsuarioId'])->first();
        $cliente = $usuario->cliente();
        if ($cliente) {
            if ($cliente->situacao == Cliente::$BLOQUEADO) throw new ClienteBloqueadoException();
            if ($cliente->inativo == Cliente::$INATIVO) throw new BloqueioException('Cliente inativo!');
        }
        return $rs;

    }

    public static function getACL(Usuario $usuario) {
        $perfil = new Perfil();
        $perfil->id = $usuario->perfil_id;
        $permissoes = PerfilViewModel::getPermissoes($perfil);

        $ACL = array();
        foreach($permissoes as $permissao)
        {
            $ACL[$permissao['PermissaoRecurso']] = $permissao['PermissaoAcesso'];
        }
        return $ACL;
    }

    public static function doAuthenticate($data)
    {
        $usuario = Usuario::where('login', $data['U'])->first();
        if (!$usuario) throw new LoginInvalidoException();
        if ($usuario->situacao == Usuario::$BLOQUEADO) throw new LoginBloqueadoException();
        if ($usuario->v2 == Usuario::$V2_INATIVO) {
            return array(
                'login' => $usuario->login,
                'ACL' => Self::getACL($usuario),
            );
        }
        if ($usuario->device_code == $data['code'])  {
            return array(
                'login' => $usuario->login,
                'ACL' => Self::getACL($usuario),
            );
        }

        throw new CodigoDeValidacaoIncorretoException();

    }

}
