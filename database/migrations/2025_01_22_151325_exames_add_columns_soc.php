<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExamesAddColumnsSoc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->string('soc_nome_arquivo')->nullable()->after('empresa');
            $table->string('soc_codigo_empresa_trabalho')->nullable()->after('soc_nome_arquivo');
            $table->string('soc_nome_empresa_trabalho')->nullable()->after('soc_codigo_empresa_trabalho');
            $table->string('soc_cnpj_empresa_trabalho')->nullable()->after('soc_nome_empresa_trabalho');
            $table->string('soc_codigo_funcionario')->nullable()->after('soc_cnpj_empresa_trabalho');
            $table->string('soc_nome_funcionario')->nullable()->after('soc_codigo_funcionario');
            $table->string('soc_situacao_funcionario')->nullable()->after('soc_nome_funcionario');
            $table->string('soc_data_cadastro_funcionario')->nullable()->after('soc_situacao_funcionario');
            $table->string('soc_cpf_funcionario')->nullable()->after('soc_data_cadastro_funcionario');
            $table->string('soc_seq_ficha')->nullable()->after('soc_cpf_funcionario');
            $table->string('soc_nome_exame')->nullable()->after('soc_seq_ficha');
            $table->string('soc_data_exame')->nullable()->after('soc_nome_exame');
            $table->string('soc_data_ficha')->nullable()->after('soc_data_exame');
            $table->string('soc_codigo_exame')->nullable()->after('soc_data_ficha');
            $table->string('soc_seq_resultado')->nullable()->after('soc_codigo_exame');
            $table->boolean('soc_resultado_alterado')->after('soc_seq_resultado')->default(false);
            $table->boolean('soc_resultado_enviado')->after('soc_resultado_alterado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->dropColumn('soc_nome_arquivo');
            $table->dropColumn('soc_codigo_empresa_trabalho');
            $table->dropColumn('soc_nome_empresa_trabalho');
            $table->dropColumn('soc_cnpj_empresa_trabalho');
            $table->dropColumn('soc_codigo_funcionario');
            $table->dropColumn('soc_nome_funcionario');
            $table->dropColumn('soc_situacao_funcionario');
            $table->dropColumn('soc_data_cadastro_funcionario');
            $table->dropColumn('soc_cpf_funcionario');
            $table->dropColumn('soc_seq_ficha');
            $table->dropColumn('soc_nome_exame');
            $table->dropColumn('soc_data_exame');
            $table->dropColumn('soc_data_ficha');
            $table->dropColumn('soc_codigo_exame');
            $table->dropColumn('soc_seq_resultado');
            $table->dropColumn('soc_resultado_alterado');
            $table->dropColumn('soc_resultado_enviado');
        });
    }
}
