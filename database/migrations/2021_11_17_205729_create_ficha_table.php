<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFichaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();
            $table->string('atendimento')->default('OCUPACIONAL'); // 1) OCUPACIONAL; 2) CLÍNICO
            $table->datetime('data')->useCurrent();
            $table->string('procedimentos');
            $table->string('numero')->unique();
            $table->foreignId('motivo_exame_id')->constrained();
            $table->foreignId('medico_solicitante_id')->constrained();
            $table->foreignId('paciente_id')->constrained();
            $table->foreignId('cliente_id')->constrained();
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
        Schema::dropIfExists('fichas');
    }
}
