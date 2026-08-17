<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\MotivoExame;

class MotivoExameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MotivoExame::truncate();
        MotivoExame::insert([
            'id' => 1,
            'nome' => 'Admissional',
            'atendimento' => 'OCUPACIONAL',
            'empresa_id' => 1,
        ], $this->command);
        MotivoExame::insert([
            'id' => 2,
            'nome' => 'Demissional',
            'atendimento' => 'OCUPACIONAL',
            'empresa_id' => 1,
        ], $this->command);
        MotivoExame::insert([
            'id' => 3,
            'nome' => 'Rotina',
            'atendimento' => 'CLINICO',
            'empresa_id' => 1,
        ], $this->command);
    }
}
