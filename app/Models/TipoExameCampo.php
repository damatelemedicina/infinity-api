<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoExameCampo extends BaseModel
{
    use HasFactory;

    public static $OBRIGATORIO = '1';

    public function tipo_exame()
    {
        return $this->belongsTo(TipoExame::class);
    }

}
