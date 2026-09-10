<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmpresasAddColumnsSoc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('soc_usuario_webservice', 255)->nullable()->after('recado_medicos');
            $table->string('soc_password_webservice', 255)->nullable()->after('soc_usuario_webservice');
            $table->string('soc_codigo_usuario', 255)->nullable()->after('soc_password_webservice');
            $table->string('soc_chave_acesso', 255)->nullable()->after('soc_codigo_usuario');
            $table->string('soc_codigo_empresa_principal', 255)->nullable()->after('soc_chave_acesso');
            $table->string('soc_codigo_responsavel', 255)->nullable()->after('soc_codigo_empresa_principal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('soc_usuario_webservice');
            $table->dropColumn('soc_password_webservice');
            $table->dropColumn('soc_codigo_usuario');
            $table->dropColumn('soc_chave_acesso');
            $table->dropColumn('soc_codigo_empresa_principal');
            $table->dropColumn('soc_codigo_responsavel');
        });
    }
}
