<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Exceptions\SessaoInvalidaException;
use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\UsuarioNaoEncontradoException;
use App\Exceptions\CadastroException;

use App\ViewModels\LoginViewModel;
use App\ViewModels\UsuarioViewModel;

use App\Models\Acesso;
use App\Models\Usuario;
use App\Models\Empresa;

use App\Services\Entity\EntityManager;
use App\Services\Security\PermissionManager;

use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{

    public static $SENHA_PADRAO = '12345';

    protected $entity;
    protected $permissions;

    public function __construct(EntityManager $entity, PermissionManager $permissions)
    {
        $this->entity = $entity;
        $this->permissions = $permissions;
    }

    public function getUsuarioLogado(Request $request)
    {

        $this->validarRequisicao($request);

        $login = LoginViewModel::getLogin(
            $this->getEmpresaDoDominio($request),
            $request['session']['login']
        );

        $usuario = Usuario::where('id', $login['UsuarioId'])->first();
        $login['ACL'] = LoginViewModel::getACL($usuario);

        $this->registerSession(
            $request,
            'usuario',
            $usuario
        );

        return response() ->json($login);

    }

    public function getUsuarios(Request $request)
    {
        $this->validarRequisicao($request);

        $dominio = $this->getEmpresaDoDominio($request);

        $empresa = $this->entity->findOne(
            'empresas',
            ['where' => ['empresas.login = ', $dominio]]
        );

        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $usuarios = $this->entity->findAll(
            'usuarios',
            ['where' => ['usuarios.empresa_id = ', $empresa->EmpresaMatriz]]
        );

        // Login da matriz, exibe todos os usuários!
        if ($empresa->EmpresaId == $empresa->EmpresaMatriz) return $usuarios;

        $result = [];

        foreach($usuarios as $usuario){

            // Login do domínio, não exibe usuários com acesso a matriz!
            $acesso = Acesso::where([
                ['empresa_id', '=', $empresa->EmpresaMatriz],
                ['usuario_id' , '=', $usuario->UsuarioId]
            ])->first();

            if ($acesso) continue;

            // Login do domínio, só exibe usuários com acesso ao domínio
            $acesso = Acesso::where([
                ['empresa_id', '=', $empresa->EmpresaId],
                ['usuario_id' , '=', $usuario->UsuarioId]
            ])->first();

            if (!$acesso) continue;

            $result[] = $usuario;
        }

        return $result;

    }

    public function getUsuario(Request $request)
    {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $usuario = $this->entity->findOne(
            'usuarios',
            ['where' => ['usuarios.id = ', $request['body']['id']]],
            Self::$ENTITY_WITH_CHILDS
        );

        if (!$usuario) throw new UsuarioNaoEncontradoException();

        /*
        $permissoes = $this->entity->findAll(
            'permissaos',
            ['where' => [ 'permissaos.perfil_id = ', $usuario->UsuarioPerfilId]]
        );

        Log::Debug('Permissoes ================================================');
        Log::Debug('Usuario->perfil_id: ' . $usuario->UsuarioPerfilId);
        Log::Debug(print_r($permissoes, true));
        Log::Debug('Permissões ================================================');
        */

        return $usuario;

    }

    public function setUsuario(Request $request)
    {

        $this->validarRequisicao($request, Self::$BODY_REQUIRED);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();

        $data = $request['body'];

        $usuario = isset($data['UsuarioId'])
                    ? Usuario::where('id', $data['UsuarioId'])->first()
                    : new Usuario();

        $usuario->login = $data['UsuarioLogin'];
        $usuario->nome = $data['UsuarioNome'];
        $usuario->perfil_id = $data['UsuarioPerfilId'];
        $usuario->email = $data['UsuarioEmail'];
        $usuario->cpf = $data['UsuarioCpf'];
        $usuario->v2 = $data['UsuarioV2'] ? Usuario::$V2_ATIVO : Usuario::$V2_INATIVO;
        $usuario->device_id = $data['UsuarioRegistrado'] ? $usuario->device_id : null;
        $usuario->situacao = $data['UsuarioBloquear'] ? Usuario::$BLOQUEADO : Usuario::$ATIVO;

        if (isset($data['UsuarioContaCliente'])) {
            $usuario->conta_cliente = $data['UsuarioContaCliente'];
            $usuario->conta_medico = 0;
        };

        if (isset($data['UsuarioContaMedico'])) {
            $usuario->conta_medico = $data['UsuarioContaMedico'];
            $usuario->conta_cliente = 0;
        };

        $usuario->senha = $usuario->senha ?? Hash::make(Self::$SENHA_PADRAO);
        $usuario->senha = $data['UsuarioSenha'] ? Hash::make(Self::$SENHA_PADRAO) : $usuario->senha;

        $usuario->empresa_id = $usuario->empresa_id ?? $this->getMatriz($request);

        $usuario->save();

        Acesso::where('usuario_id', '=', $data['UsuarioId'])->delete();

        $semAcesso = true;

        foreach($data['UsuarioAcessos'] as $value){
            if (!$value) continue;
            $semAcesso = false;
            $acesso = new Acesso();
            $acesso->empresa_id = $value;
            $acesso->usuario_id = $usuario->id;
            $acesso->save();
        }

        if ($semAcesso) {
            $acesso = new Acesso();
            $acesso->empresa_id = $empresa->id;
            $acesso->usuario_id = $usuario->id;
            $acesso->save();
        }

        return [];

        return $this->entity->findAll(
            'usuarios',
            ['where' => ['usuarios.empresa_id = ', '1']]
        );

    }

    function trocarSenha(Request $request) {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $usuario = Usuario::where('login', $request['session']['login'])->first();
        if (!$usuario) throw new UsuarioNaoEncontradoException();
        $data = $request['body'];
        if (!Hash::check($data['SenhaAtual'], $usuario->senha)) throw new CadastroException('Senha inválida!');
        if ($data['NovaSenha'] !== $data['ConfirmarSenha']) throw new CadastroException('Senhas não conferem!');
        $usuario->senha = Hash::make($data['NovaSenha']);
        $usuario->save();
        return [];
    }

}
