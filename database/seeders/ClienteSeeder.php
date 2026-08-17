<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Cliente;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Cliente::truncate();
        Cliente::insert([
            'id' => 1,
            'nome' => 'DLC',
            'cnpj' => '11111111111111',
            'empresa_id' => 2,
        ]);
        Cliente::insert([
            'id' => 2,
            'nome' => 'CRJ',
            'cnpj' => '22222222222222',
            'empresa_id' => 2,
        ]);

    }
}
