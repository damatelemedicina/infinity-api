<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    use HasFactory;
    
    public static $OCUPACIONAL = 'OCUPACIONAL';
    public static $CLINICO = 'CLINICO';

}
