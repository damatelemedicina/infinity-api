<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExamesAddParteCorpoExaminada extends Migration
{
    /**
     * Coluna paralela, só de apoio/análise: guarda uma versão padronizada da
     * tag DICOM genérica BodyPartExamined (0018,0015), a mesma em qualquer
     * fabricante — ver ExameController::normalizeParteCorpoExaminada().
     * Não substitui nem altera exame_id/sub_tipo_exame (que continuam vindo
     * da lógica específica de cada fabricante, já ajustada em produção); às
     * vezes fica vazia, pra equipamentos que não preenchem essa tag.
     */
    public function up()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->string('parte_corpo_examinada')->nullable()->after('sub_tipo_exame');
        });
    }

    public function down()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->dropColumn('parte_corpo_examinada');
        });
    }
}
