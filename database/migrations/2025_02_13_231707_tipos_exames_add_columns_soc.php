<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TiposExamesAddColumnsSoc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipo_exames', function (Blueprint $table) {
            $table->string('soc_codigo_exame', 255)->nullable()->after('fumante');
            $table->boolean('soc_nao_envia_texto_laudo')->after('soc_codigo_exame')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipo_exames', function (Blueprint $table) {
            $table->dropColumn('soc_codigo_exame');
            $table->dropColumn('soc_nao_envia_texto_laudo');
        });
    }
}
