<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\MedicoSolicitante;

class MedicoSolicitanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MedicoSolicitante::upsert([
            'id' => 1,
            'nome' => 'MAURO FERRETI',
            'crm' => '91064SP',
            'empresa_id' => 1,
        ], $this->command);

        MedicoSolicitante::upsert([
            'id' => 2,
            'nome' => 'FABRÍCIO PELUCCI MACHADO',
            'crm' => '50656MG',
            'empresa_id' => 1,
        ], $this->command);

        MedicoSolicitante::upsert([
            'id' => 3,
            'nome' => 'MARCELO JOSE TULESKI',
            'crm' => '18366PR',
            'empresa_id' => 1,
        ], $this->command);

    }
}
