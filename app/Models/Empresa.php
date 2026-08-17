<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    public static $ATIVA = 0;
    public static $BLOQUEADA = 1;
    public static $INATIVA = 2;

}
