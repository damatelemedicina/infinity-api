<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Usuario;

use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Usuario::truncate();
        Usuario::insert([
            'id' => 1,
            'login' => 'davi.silveira',
            'nome' => 'Davi Silveira',
            'email' => 'davi.silveira@dominio.com.br',
            'cpf' => '11111111111',
            'senha' => Hash::make('12345'),
            'situacao' => Usuario::$BLOQUEADO,
            'v2' => 0,
            'perfil_id' => 1,
            'empresa_id' => 1,
        ]);
        Usuario::insert([
            'id' => 2,
            'login' => 'thais.lopes',
            'nome' => 'Thais Lopes',
            'email' => 'thais.lopes@dominio.com.br',
            'cpf' => '22222222222',
            'senha' => Hash::make('12345'),
            'v2' => 0,
            'perfil_id' => 1,
            'empresa_id' => 1,
        ]);
    }
}
