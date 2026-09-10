<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClientesAddColumnsSoc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('soc_classificacao', 255)->nullable();
            $table->string('soc_codigo_empresa', 255)->nullable();
            $table->string('soc_codigo_ged', 255)->nullable();
            $table->string('soc_codigo_exporta_cad_empresas', 255)->nullable();
            $table->string('soc_chave_exporta_cad_empresas', 255)->nullable();
            $table->string('soc_codigo_exporta_cad_funcionarios', 255)->nullable();
            $table->string('soc_chave_exporta_cad_funcionarios', 255)->nullable();
            $table->string('soc_codigo_exporta_pedido_exame', 255)->nullable();
            $table->string('soc_chave_exporta_pedido_exame', 255)->nullable();
            $table->string('soc_codigo_exporta_pedido_exame_seq_ficha', 255)->nullable();
            $table->string('soc_chave_exporta_pedido_exame_seq_ficha', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('soc_classificacao');
            $table->dropColumn('soc_codigo_empresa');
            $table->dropColumn('soc_codigo_ged');
            $table->dropColumn('soc_codigo_exporta_cad_empresas');
            $table->dropColumn('soc_chave_exporta_cad_empresas');
            $table->dropColumn('soc_codigo_exporta_cad_funcionarios');
            $table->dropColumn('soc_chave_exporta_cad_funcionarios');
            $table->dropColumn('soc_codigo_exporta_pedido_exame');
            $table->dropColumn('soc_chave_exporta_pedido_exame');
            $table->dropColumn('soc_codigo_exporta_pedido_exame_seq_ficha');
            $table->dropColumn('soc_chave_exporta_pedido_exame_seq_ficha');
        });
    }
}
