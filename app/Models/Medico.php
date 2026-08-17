<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\MedicoExame;
use App\Models\MedicoModelo;
use App\Models\TipoExame;

class Medico extends Model
{
    use HasFactory;

    public static $ATIVO = 0;
    public static $BLOQUEADO = 1;

    public function exames() {
        $exames = MedicoExame::where('medico_id', $this->id)->get();
        foreach ($exames as $exame) {
            $tipoExame = TipoExame::where('id', $exame->tipo_exame_id)->first();
            if (!$tipoExame) continue;
            $exame['tipo_exame_nome'] = $tipoExame->nome;
            $exame['recusa'] = $exame->recusa;
        }
        return $exames;
    }

    public function modelos() {
        return MedicoModelo::where('medico_id', $this->id)->get();
    }
}
