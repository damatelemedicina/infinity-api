<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('login', 20)->unique();
            $table->string('nome', 50)->require();
            $table->string('email', 30)->require();
            $table->string('senha', 100)->require();
            $table->string('cpf', 11)->nullable();
            $table->string('device_id', 256)->nullable()->unique();
            $table->integer('device_code')->default(0);
            $table->boolean('v2')->default(1);
            $table->integer('situacao')->default(0);
            $table->integer('conta_cliente')->default(0);
            $table->integer('conta_medico')->default(0);
            $table->foreignId('empresa_id')->constrained();
            $table->foreignId('perfil_id')->constrained();
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
        Schema::dropIfExists('usuarios');
    }
}
