<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExamesAddIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exames_antigos', function (Blueprint $table) {
            $table->unsignedBigInteger('exame_id')->nullable()->change();
            $table->foreign('exame_id')->references('id')->on('tipo_exames');

            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            $table->foreign('cliente_id')->references('id')->on('clientes');

            $table->unsignedBigInteger('empresa_id')->nullable()->change();
            $table->foreign('empresa_id')->references('id')->on('empresas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exames_antigos', function (Blueprint $table) {
            // Remove foreign keys e índices se precisar reverter
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['exame_id']);
            $table->dropForeign(['cliente_id']);

            $table->dropIndex('exames_antigos_empresa_id_foreign');
            $table->dropIndex('exames_antigos_exame_id_foreign');
            $table->dropIndex('exames_antigos_cliente_id_foreign');
        });
    }
}


// ExamesAddIndex: ALTER TABLE exames_antigos CHANGE exame_id exame_id BIGINT UNSIGNED DEFAULT NULL, CHANGE cliente_id cliente_id BIGINT UNSIGNED DEFAULT NULL, CHANGE empresa_id empresa_id BIGINT UNSIGNED DEFAULT NULL, CHANGE medico_id medico_id BIGINT UNSIGNED DEFAULT 0
// ExamesAddIndex: alter table `exames_antigos` add constraint `exames_antigos_exame_id_foreign` foreign key (`exame_id`) references `tipo_exames` (`id`)
// ExamesAddIndex: alter table `exames_antigos` add constraint `exames_antigos_cliente_id_foreign` foreign key (`cliente_id`) references `clientes` (`id`)
// ExamesAddIndex: alter table `exames_antigos` add constraint `exames_antigos_empresa_id_foreign` foreign key (`empresa_id`) references `empresas` (`id`)
// ExamesAddIndex: alter table `exames_antigos` add constraint `exames_antigos_medico_id_foreign` foreign key (`medico_id`) references `medicos` (`id`)
