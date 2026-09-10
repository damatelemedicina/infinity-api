<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class TipoDocumento extends BaseModel
{
    use HasFactory;
    public $table = "tipos_documentos";

    static function serverProcessing()
    {
        $whereColumns = [
            "tipos_documentos.id",
            "tipos_documentos.nome",
        ];

        $query = DB::table('tipos_documentos')
            ->select('tipos_documentos.id', 'tipos_documentos.nome');

        return self::serverProcessingBase($query, $whereColumns);
    }
}
