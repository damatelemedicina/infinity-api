<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Empresa::truncate();
        Empresa::insert([
            'id' => 1,
            'nome' => 'Grupo Infinity',
            'login' => 'MASTER',
            'matriz' => 1,
        ]);
        Empresa::insert([
            'id' => 2,
            'nome' => 'Dama Telemedicina',
            'login' => 'DAMA',
            'matriz' => 1,
        ]);
        Empresa::insert([
            'id' => 3,
            'nome' => 'VT Telemedicina',
            'login' => 'VT',
            'matriz' => 1,
        ]);
    }
}
