<?php

use App\Models\Documento;
use App\Models\Medico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentosMedicos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentos_medicos', function (Blueprint $table) {
            $table->foreignIdFor(Documento::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignIdFor(Medico::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['documento_id', 'medico_id']);
            $table->primary(['documento_id', 'medico_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentos_medicos');
    }
}
