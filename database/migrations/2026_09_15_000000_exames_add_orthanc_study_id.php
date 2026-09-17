<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExamesAddOrthancStudyId extends Migration
{
    /**
     * Guarda o ID do estudo no Orthanc (o "ID" interno, não o StudyInstanceUID
     * DICOM) pros exames importados via OrthancExameImporter::importStudy().
     * Com isso dá pra montar o link direto pro Stone Web Viewer do Orthanc:
     * "<orthanc.public_url>/stone-webviewer/index.html?study=<orthanc_study_id>".
     */
    public function up()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->string('orthanc_study_id')->nullable()->after('enviado_por');
        });
    }

    public function down()
    {
        Schema::table('exames', function (Blueprint $table) {
            $table->dropColumn('orthanc_study_id');
        });
    }
}
