<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichaProcedimentosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ficha_procedimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_id')->constrained(); 
            $table->string('nome');
            $table->string('tipo');
            $table->integer('ordem');
            $table->integer('tamanho');
            $table->string('opcoes')->default('')->nullable();
            $table->boolean('obrigatorio')->default(false);
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
        Schema::dropIfExists('ficha_procedimentos');
    }
}
