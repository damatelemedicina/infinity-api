<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        //Eloquent::unguard();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            EmpresaSeeder::class,
            PerfilSeeder::class,
            UsuarioSeeder::class,
            AcessoSeeder::class,
            PermissaoSeeder::class,
            FichaSeeder::class,
            TipoExameSeeder::class,
            TipoExameCampoSeeder::class,
            ServicoSeeder::class,
            PacienteSeeder::class,
            MotivoExameSeeder::class,
            MedicoSolicitanteSeeder::class,
            ClienteSeeder::class,
            ImpossibilidadeSeeder::class,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

}
