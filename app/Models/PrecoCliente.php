<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class PrecoCliente extends BaseModel
{
    use HasFactory;

    static function serverProcessing($empresa_id)
    {
        $whereColumns = [
            'preco_clientes.id',
            'preco_clientes.nome',
            'clientes.nome',
            'tipo_exames.nome',
            DB::raw("date_format(preco_clientes.vigencia_de, '%d/%m/%Y')"),
            DB::raw("date_format(preco_clientes.vigencia_ate, '%d/%m/%Y')"),
            '',
            '',
            '',
            'preco_clientes.cobranca1',
            '',
            '',
            '',
            'preco_clientes.cobranca2',
        ];

        $query = DB::table('preco_clientes')
            ->selectRaw("preco_clientes.id, preco_clientes.nome as regra, clientes.nome as cliente, tipo_exames.nome as tipo_exame," . PHP_EOL
                . "    date_format(preco_clientes.vigencia_de, '%d/%m/%Y') as vigencia_de, date_format(preco_clientes.vigencia_ate, '%d/%m/%Y') as vigencia_ate," . PHP_EOL
                . "    preco_clientes.de1, preco_clientes.de2," . PHP_EOL
                . "    preco_clientes.ate1, preco_clientes.ate2," . PHP_EOL
                . "    format(preco_clientes.preco1, 2, 'pt_BR') as preco1," . PHP_EOL
                . "    format(preco_clientes.preco2, 2, 'pt_BR') as preco2," . PHP_EOL
                . "    preco_clientes.cobranca1, preco_clientes.cobranca2, preco_clientes.cliente_id")
            ->join('clientes', 'clientes.id', '=', 'preco_clientes.cliente_id')
            ->join('tipo_exames', 'tipo_exames.id', '=', 'preco_clientes.tipo_exame_id')
            ->where('preco_clientes.empresa_id', $empresa_id);

        return self::serverProcessingBase($query, $whereColumns);
    }
}
