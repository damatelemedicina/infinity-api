<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTipoExameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_exames', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->integer('situacao')->default(0);
            $table->integer('emergencia')->default(0);
            $table->integer('laudo_rapido')->default(0);
            $table->foreignId('empresa_id')->constrained();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tipo_exames');
    }
}
