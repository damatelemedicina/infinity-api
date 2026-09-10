<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PrecoMedico extends BaseModel
{
    use HasFactory;

    static function serverProcessing($empresa_id)
    {
        $whereColumns = [
            'preco_medicos.id',
            'preco_medicos.nome',
            'medicos.nome',
            'tipo_exames.nome',
        ];

        $query = DB::table('preco_medicos')
            ->selectRaw("preco_medicos.id, preco_medicos.nome as regra,"
                . "    medicos.nome as medico,"
                . "    tipo_exames.nome as tipo_exame,"
                . "    date_format(preco_medicos.vigencia_de, '%d/%m/%Y') as vigencia_de,"
                . "    date_format(preco_medicos.vigencia_ate, '%d/%m/%Y') as vigencia_ate,"
                . "    format(preco_medicos.preco, 2, 'pt_BR') as preco")
            ->join('medicos', 'medicos.id', '=', 'preco_medicos.medico_id')
            ->join('tipo_exames', 'tipo_exames.id', '=', 'preco_medicos.tipo_exame_id')
            ->where('preco_medicos.empresa_id', $empresa_id);

        return self::serverProcessingBase($query, $whereColumns);
    }
}
