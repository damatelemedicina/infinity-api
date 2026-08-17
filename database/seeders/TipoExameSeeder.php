<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\TipoExame;

class TipoExameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //TipoExame::truncate();
        TipoExame::insert([
            [
                'id' => 1,
                'nome' => 'Eletrocardiograma',
                'empresa_id' => 1
            ],[
                'id' => 2,
                'nome' => 'Eletroencefalograma',
                'empresa_id' => 1
            ],[
                'id' => 3,
                'nome' => 'Espirometria',
                'empresa_id' => 1
            ],[
                'id' => 4,
                'nome' => 'Raio X',
                'empresa_id' => 1
            ],[
                'id' => 5,
                'nome' => 'Mapa',
                'empresa_id' => 1
            ],[
                'id' => 6,
                'nome' => 'Acuidade Visual',
                'empresa_id' => 1
            ],[
                'id' => 7,
                'nome' => 'EEG Clinico',
                'empresa_id' => 1
            ],[
                'id' => 8,
                'nome' => 'Mapeamento Cerebral',
                'empresa_id' => 1
            ],[
                'id' => 9,
                'nome' => 'Raio X - OIT',
                'empresa_id' => 1
            ],[
                'id' => 10,
                'nome' => 'Holter',
                'empresa_id' => 1
            ],[
                'id' => 11,
                'nome' => 'Espirometria Pneumo',
                'empresa_id' => 1
            ],[
                'id' => 12,
                'nome' => 'Espirometria Clínica',
                'empresa_id' => 1
            ],[
                'id' => 13,
                'nome' => 'Teste de Ishihara',
                'empresa_id' => 1
            ]
        ], $this->command);
    }
}

// $this->command->info($message)
// $this->command->line($message)
// $this->command->comment($message)
// $this->command->question($message)
// $this->command->error($message)
// $this->command->warn($message)
// $this->command->alert($message)

