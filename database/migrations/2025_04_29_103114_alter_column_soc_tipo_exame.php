<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnSocTipoExame extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipo_exames', function (Blueprint $table) {
            $table->text('soc_codigo_exame')->nullable()->change();
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
            $table->string('soc_codigo_exame', 255)->nullable()->change();
        });
    }
}
