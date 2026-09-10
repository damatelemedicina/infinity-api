<?php

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TipoDocumento::class,'tipos_documentos_id')->nullable()->constrained();
            $table->foreignIdFor(Empresa::class)->nullable()->constrained();
            $table->foreignIdFor(Usuario::class)->nullable()->constrained();
            $table->foreignIdFor(Perfil::class)->nullable()->constrained();
            $table->string('nome')->nullable();
            $table->string('filename')->nullable();
            $table->string('uuid', 100)->nullable();
            $table->string('rawfilename')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentos');
    }
}
