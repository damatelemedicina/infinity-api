<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Perfil;

class PerfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Perfil::truncate();
        Perfil::insert([
            'id' => 1,
            'nome' => 'Master',
            'padrao' => 1,
            'empresa_id' => 1,
        ]);  
        Perfil::insert([
            'id' => 2,
            'nome' => 'Suporte',
            'empresa_id' => 1,
        ]);  
        Perfil::insert([
            'id' => 3,
            'nome' => 'Financeiro',
            'empresa_id' => 1,
        ]);  
        Perfil::insert([
            'id' => 4,
            'nome' => 'Vendas',
            'empresa_id' => 1,
        ]);  
        Perfil::insert([
            'id' => 5,
            'nome' => 'Cliente',
            'empresa_id' => 1,
            'cliente' => true,
        ]);  
    }
}
