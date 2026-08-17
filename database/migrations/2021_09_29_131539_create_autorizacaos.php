<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Autorizacao;

class CreateAutorizacaos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autorizacaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained();
            $table->foreignId('permissao_id')->constrained();
            $table->string('acesso')->default(Autorizacao::$PADRAO);
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
        Schema::dropIfExists('autorizacaos');
    }
}
