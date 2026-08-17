<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Autorizacao;

use App\ViewModels\PerfilViewModel;

use App\Exceptions\EmpresaNaoEncontradaException;
use App\Exceptions\PerfilNaoEncontradoException;

use Illuminate\Support\Facades\Log;

class PerfilController extends Controller
{
    public function getPerfis(Request $request) {

        $this->validarRequisicao($request);

        $empresa = Empresa::where('login', $this->getEmpresaDoDominio($request))->first();
        if (!$empresa) throw new EmpresaNaoEncontradaException();
        return PerfilViewModel::getPerfis($empresa);
    }

    public function getPerfil(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $perfil = Perfil::where('id', $request['body']['id'])->first();
        if (!$perfil) throw new PerfilNaoEncontradoException();
        return array(
            'PerfilId' => $perfil->id,
            'PerfilNome' => $perfil->nome,
            'PerfilMaster' => $perfil->master,
            'PerfilCliente' => $perfil->cliente,
            'PerfilMedico' => $perfil->medico,
            'PerfilEmpresaId' => $perfil->empresa_id,
            'PerfilPermissoes' => PerfilViewModel::getPermissoes($perfil),
        );
    }

    public function setPerfil(Request $request)
    {
        $this->validarRequisicao($request, Self::$BODY_REQUIRED);
        $data = $request['body'];

        $perfil = new Perfil();

        if (isset($data['PerfilId'])) {
            $perfil = Perfil::where('id', $data['PerfilId'])->first();
            if (!$perfil) throw new PerfilNaoEncontradoException();
        } else {
            $perfil->empresa_id = $this->getMatriz($request);
        }
        $perfil->nome = $data['PerfilNome'];
        $perfil->cliente = $data['PerfilCliente'];
        $perfil->medico = $data['PerfilMedico'];

        $perfil->save();

        $permissoes = $data['PerfilPermissoes'];

        foreach ($permissoes as $item) {
            foreach ($item as $r => $p)
            {
                $permissao = Permissao::where('recurso', $r)->first();
                $autorizacao = Autorizacao::where(
                    'perfil_id', $perfil->id)->where('permissao_id', $permissao->id)->first();
                if (!$autorizacao) $autorizacao = new Autorizacao();
                $autorizacao->perfil_id = $perfil->id;
                $autorizacao->permissao_id = $permissao->id;
                $autorizacao->acesso = $p;
                $autorizacao->save();
            }

        }

        return [
            'PerfilId' => $perfil->id,
            'PerfilNome' => $perfil->nome,
            'PerfilEmpresaId' =>$perfil->empresa_id,
        ];
    }

}
