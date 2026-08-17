<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Paciente;

use Carbon\Carbon;

class PacienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Paciente::truncate();
        Paciente::upsert([
            'id' => 1,
            'nome' => 'Carlos Lopes dos Santos',
            'sexo' => 'M',
            'nascimento' => Carbon::parse('03/11/1969'),
            'rg' => '11111111',
            'cpf' => '22222222222',
            'empresa_id' => 1,
        ], $this->command);

    }
}
