<?php

namespace App\ViewModels;

use Spatie\ViewModels\ViewModel;

use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Autorizacao;

use Illuminate\Support\Facades\Log;

class PerfilViewModel extends BaseViewModel
{
    public function __construct()
    {
        //
    }


    public static function popularPermissoes($todas, $perfil_id, Perfil $perfil)
    {
        foreach($todas as $permissao)
        {
            $autorizacao = new Autorizacao();
            $autorizacao->perfil_id = $perfil_id;
            $autorizacao->permissao_id = $permissao->id;
            $autorizacao->acesso = Autorizacao::$PADRAO;
            $autorizacao->save();
        }
        return Self::getPermissoes($perfil);
    }

    public static function getPermissoes(Perfil $perfil)
    {
        $todas = Permissao::all();
        $sql = "CALL GetPermissoesDoPerfil('{$perfil->id}')";
        $permissoes = Self::callStoredProcedure($sql);
        if (count($todas) == count($permissoes)) return $permissoes;
        return Self::popularPermissoes($todas, $perfil->id, $perfil);
    }

    public static function getPerfis($empresa)
    {
        if (!$empresa) return array();

        $perfis = Perfil::where('empresa_id', $empresa->matriz)->get();
        $result = [];
        foreach ($perfis as $perfil) {
            $result[] = array(
                'PerfilId' => $perfil->id,
                'PerfilNome' => $perfil->nome,
                'PerfilEmpresaId' => $perfil->empresa_id
            );
        }
        return $result;
    }

    public static function getPerfisOld($empresa)
    {
        if (!$empresa) return array();

        $perfis = Perfil::where('empresa_id', $empresa->matriz)->get();
        $result = [];
        foreach ($perfis as $perfil) {
            if ($empresa->id !== $empresa->matriz && !$perfil->cliente) continue;
            $result[] = array(
                'PerfilId' => $perfil->id,
                'PerfilNome' => $perfil->nome,
                'PerfilEmpresaId' => $perfil->empresa_id
            );
        }
        return $result;
    }
}
