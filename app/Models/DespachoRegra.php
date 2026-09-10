<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DespachoRegra extends BaseModel
{
    use HasFactory;

    static function serverProcessing($empresa_id)
    {
        $whereColumns = [
            'despacho_regras.id',
            'despacho_regras.nome',
            'clientes.nome',
            'medicos.nome',
            'tipo_exames.nome',
            DB::raw('case despacho_regras.ativa when 1 then "SIM" else "NAO" end'),
            'despacho_regras.tipo',
        ];

        $query = DB::table('despacho_regras')
            ->select('despacho_regras.id', 'despacho_regras.nome as regra', 'despacho_regras.ativa', 'despacho_regras.tipo', 'clientes.nome as cliente', 'medicos.nome as medico', 'tipo_exames.nome as tipo_exame')
            ->selectRaw('case despacho_regras.ativa when 1 then "SIM" else "NAO" end as status')
            ->leftJoin('medicos', 'medicos.id', '=', 'despacho_regras.medico_id')
            ->leftJoin('tipo_exames', 'tipo_exames.id', '=', 'despacho_regras.tipo_exame_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'despacho_regras.cliente_id')
            ->where('despacho_regras.empresa_id', $empresa_id);

        return self::serverProcessingBase($query, $whereColumns);
    }
}
