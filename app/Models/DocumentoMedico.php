<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class DocumentoMedico extends BaseModel
{
    use HasFactory;

    protected $table = "documentos_medicos";
}
