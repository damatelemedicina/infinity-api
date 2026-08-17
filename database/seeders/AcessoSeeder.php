<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Schema;

use Illuminate\Database\Seeder;
use App\Models\Acesso;

class AcessoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        Acesso::truncate();
        
        // GRUPO INFINITY
        Acesso::insert([
            'id' => 1,
            'empresa_id' => 1, // INFINITY
            'usuario_id' => 1, // Davi
        ]);
        Acesso::insert([
            'id' => 2,
            'empresa_id' => 1, // INFINITY
            'usuario_id' => 2, // Thais
        ]);
        
        // DAMA TELEMEDICINA
        Acesso::insert([
            'id' => 3,
            'empresa_id' => 2, // DAMA
            'usuario_id' => 1, // Davi
        ]);
        Acesso::insert([
            'id' => 4,
            'empresa_id' => 2, // DAMA
            'usuario_id' => 2, // Thais
        ]);
        Acesso::insert([
            'id' => 5,
            'empresa_id' => 2, // DAMA
            'usuario_id' => 3, // Flávia
        ]);
        Acesso::insert([
            'id' => 6,
            'empresa_id' => 2, // DAMA
            'usuario_id' => 4, // Iago
        ]);

        // VT TELEMEDICINA
        Acesso::insert([
            'id' => 7,
            'empresa_id' => 3, // VICTHAMED
            'usuario_id' => 1, // Davi
        ]);
        Acesso::insert([
            'id' => 8,
            'empresa_id' => 3, // VICTHAMED
            'usuario_id' => 2, // Thais
        ]);
        Acesso::insert([
            'id' => 9,
            'empresa_id' => 3, // VICTHAMED
            'usuario_id' => 3, // Flávia
        ]);
        Acesso::insert([
            'id' => 10,
            'empresa_id' => 3, // VICTHAMED
            'usuario_id' => 4, // Iago
        ]);
        Acesso::insert([
            'id' => 11,
            'empresa_id' => 3, // VICTHAMED
            'usuario_id' => 5, // Vitor
        ]);

    }
}
