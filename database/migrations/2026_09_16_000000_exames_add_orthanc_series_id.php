<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExamesAddOrthancSeriesId extends Migration
{
    /**
     * Guarda o SeriesInstanceUID do DICOM (tag 0020,000E) — junto com
     * exames.orthanc_study_id, dá pra apontar o Stone Web Viewer direto pra
     * série (o grupo/tipo) desse exame específico, em vez de abrir o estudo
     * inteiro com todos os tipos de raio-x do paciente misturados.
     */
    public function up()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->string('orthanc_series_id')->nullable()->after('orthanc_study_id');
        });
    }

    public function down()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->dropColumn('orthanc_series_id');
        });
    }
}
