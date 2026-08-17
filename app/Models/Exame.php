<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exame extends Model
{
    use HasFactory;

    public static $ECG = 'ECG';
    public static $EEG = 'EEG';
    public static $MAPA = 'MAPA';
    public static $HOLTER = 'HOLTER';
    public static $ESPIRO = 'ESPIRO';
    public static $RAIOX = 'RAIOX';

    public static $AGUARDADO_LAUDO = 0;
    public static $LAUDADO = 1;
    public static $IMPOSSIBILITADO = 2;
    public static $CANCELADO = 3;

    protected $guarded = ['id'];

}
