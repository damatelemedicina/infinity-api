<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\TipoExameCampo;

class TipoExameCampoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //TipoExameCampo::truncate();

        // ECG Ocupacional I
        TipoExameCampo::upsert([
            'id' => 1,
            'nome' => 'Paciente',
            'tipo' => 'String',
            'ordem' => 1,
            'tamanho' => 12,
            'tipo_exame_id' => 1,
        ]);        
        TipoExameCampo::upsert([
            'id' => 2,
            'nome' => 'RG',
            'tipo' => 'String',
            'ordem' => 2,
            'tamanho' => 12,
            'tipo_exame_id' => 1,
        ]);        
        TipoExameCampo::upsert([
            'id' => 3,
            'nome' => 'CPF',
            'tipo' => 'String',
            'ordem' => 3,
            'tamanho' => 12,
            'tipo_exame_id' => 1,
        ]);        

        // ECG Ocupacional II
        TipoExameCampo::upsert([
            'id' => 4,
            'nome' => 'Paciente',
            'tipo' => 'String',
            'ordem' => 1,
            'tamanho' => 12,
            'tipo_exame_id' => 2,
        ]);        
        TipoExameCampo::upsert([
            'id' => 5,
            'nome' => 'RG',
            'tipo' => 'String',
            'ordem' => 2,
            'tamanho' => 12,
            'tipo_exame_id' => 2,
        ]);        
    }
}
