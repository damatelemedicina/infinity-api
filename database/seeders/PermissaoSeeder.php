<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Permissao;

class PermissaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permissao::truncate();
        Permissao::insert([
            'id' => 1,
            'recurso' => 'MenuEmpresa',
            'descricao' => 'Menu: Empresas.',
        ]);  
        Permissao::insert([
            'id' => 2,
            'recurso' => 'MenuPainel',
            'descricao' => 'Menu: Painel de Controle.',
        ]);  
        Permissao::insert([
            'id' => 3,
            'recurso' => 'MenuUsuario',
            'descricao' => 'Menu: Usuários.',
        ]);  
        Permissao::insert([
            'id' => 4,
            'recurso' => 'MenuPerfilDeUso',
            'descricao' => 'Menu: Perfil de Uso.',
        ]);
        Permissao::insert([
            'id' => 5,
            'recurso' => 'MenuFicha',
            'descricao' => 'Menu: Atendimentos.',
        ]);
        Permissao::insert([
            'id' => 6,
            'recurso' => 'MenuConfiguracao',
            'descricao' => 'Menu: Configurações.',
        ]);
        Permissao::insert([
            'id' => 7,
            'recurso' => 'MenuClinica',
            'descricao' => 'Menu: Clínicas.',
        ]);
    }
}
