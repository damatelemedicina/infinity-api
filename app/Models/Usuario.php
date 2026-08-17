<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    public static $ATIVO = 0;
    public static $BLOQUEADO = 1;
    public static $V2_ATIVO = 1;
    public static $V2_INATIVO = 0;

    public function cliente() {
        return Cliente::where('id', $this->conta_cliente)->first();
    }

    public function medico() {
        return Medico::where('id', $this->conta_medico)->first();
    }

}
