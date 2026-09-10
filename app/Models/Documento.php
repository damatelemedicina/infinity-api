<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Documento extends BaseModel
{
    use HasFactory;

    static function serverProcessing($isContaAdmin, $EmpresaId, $perfil_id, $conta_cliente, $conta_medico)
    {
        $PERFIL_ID_MASTER = 1;
        $PERFIL_ID_CLIENTE = 5;
        $PERFIL_ID_MEDICO = 6;
        $criado_em = "date_format(d.created_at, '%d/%m/%Y %H:%i')";
        $whereColumns = [
            "d.nome",
            "td.nome",
            "e.nome",
            "c.nome_formatted",
            "m.nome_formatted",
            "p.nome",
            "u.nome",
            DB::raw($criado_em),
        ];

        $orderColumns = [
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'd.created_at',
        ];

        /** @var \Illuminate\Database\Query\Builder $query */
        $query = self::query();

        /** @var \Illuminate\Database\Query\Builder $queryCliente */
        $queryCliente = Cliente::query();

        /** @var \Illuminate\Database\Query\Builder $queryMedico */
        $queryMedico = Medico::query();

        $queryCliente->selectRaw('d.id, group_concat(distinct c.nome separator "-*-") as nome')
            ->selectRaw('group_concat(distinct c.nome separator ",") as nome_formatted')
            ->from($query->from, "d")
            ->leftJoin((new DocumentoCliente())->getTable() . " as dc", "dc.documento_id", "=", "d.id")
            ->leftJoin((new DocumentoMedico())->getTable() . " as dm", "dm.documento_id", "=", "d.id")
            ->leftJoin((new Cliente())->getTable() . " as c", "c.id", "=", "dc.cliente_id")
            ->groupBy('d.id');

            $queryMedico->selectRaw('d.id, group_concat(distinct m.nome separator "-*-") as nome')
            ->selectRaw('group_concat(distinct m.nome separator ",") as nome_formatted')
            ->from($query->from, "d")
            ->leftJoin((new DocumentoMedico())->getTable() . " as dm", "dm.documento_id", "=", "d.id")
            ->leftJoin((new DocumentoCliente())->getTable() . " as dc", "dc.documento_id", "=", "d.id")
            ->leftJoin((new Medico())->getTable() . " as m", "m.id", "=", "dm.medico_id")
            ->groupBy('d.id');

        // Login da matriz, exibe todos os documentos!
        $isContaAdmin = ($isContaAdmin ?? 0) == 1;
        $joinTypeCliente = 'left';
        $joinTypeMedico = 'left';
        if ($isContaAdmin == false) {
            if ($conta_medico > 0 || $conta_cliente > 0) {
                if ($conta_cliente > 0) {
                    $joinTypeCliente = 'inner';
                    $queryCliente->whereRaw('(c.id = ? or (d.perfil_id = ? and c.id is null) or (d.perfil_id is null and c.id is null and dm.medico_id is null))', [$conta_cliente, $PERFIL_ID_CLIENTE]);
                    $query->whereNotNull('c.id');
                }
                if ($conta_medico > 0) {
                    $joinTypeMedico = 'inner';
                    $queryMedico->whereRaw('(m.id = ? or (d.perfil_id = ? and m.id is null) or (m.id = ? and d.perfil_id = ?) or (d.perfil_id is null and m.id is null and dc.cliente_id is null))', [$conta_medico, $PERFIL_ID_MEDICO, $conta_medico, $PERFIL_ID_MASTER]);
                    $query->whereNotNull('m.id');
                }
            } else if ($perfil_id > 0) {
                $query->whereRaw('(d.perfil_id = ? or d.perfil_id is null)', [$perfil_id]);
            }
        }

        $query->select(
            'd.nome',
            'td.nome as tipo_documento',
            'e.nome as empresa',
            'c.nome_formatted as cliente',
            'm.nome_formatted as medico',
            'p.nome as perfil',
            'u.nome as usuario',
            DB::raw("{$criado_em} as criado_em"),
            'd.created_at',
            'd.id'
        )
            ->from("{$query->from} as d")
            ->leftJoin('tipos_documentos as td', 'td.id', '=', 'd.tipos_documentos_id')
            ->joinSub($queryCliente, 'c', 'c.id', '=', 'd.id', $joinTypeCliente)
            ->joinSub($queryMedico, 'm', 'm.id', '=', 'd.id', $joinTypeMedico)
            ->leftJoin('empresas as e', 'e.id', '=', 'd.empresa_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'd.usuario_id')
            ->leftJoin('perfils as p', 'p.id', '=', 'd.perfil_id')
            ->whereRaw('(d.empresa_id = ? or d.empresa_id is null)', [$EmpresaId]);

        return self::serverProcessingBase($query, $whereColumns, $orderColumns);
    }
}
