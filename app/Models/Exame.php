<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Exame extends BaseModel
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

    static function serverProcessingExamesAntigos($empresa, $usuario, $status)
    {
        $whereColumns = $orderColumns = [
            'exames_antigos.id',
            'clientes.nome',
            'exames_antigos.paciente',
            'medicos.nome',
            'tipo_exames.nome',
            'exames_antigos.created_at',
            'exames_antigos.laudo_date',
            'exames_antigos.protocolo',
        ];

        $whereColumns[5] = DB::raw("date_format(exames_antigos.created_at, '%d/%m/%Y %H:%i:%s')");
        $whereColumns[6] = DB::raw("date_format(exames_antigos.laudo_date, '%d/%m/%Y %H:%i:%s')");

        $isContaCliente = ($usuario->conta_cliente > 0);
        $isContaMedico = ($usuario->conta_medico > 0);
        $isContaEmpresa = ($isContaCliente == false && $isContaMedico == false);

        $query = DB::table('exames_antigos')
            ->select(
                "exames_antigos.id AS id",
                "exames_antigos.paciente AS paciente",
                "medicos.nome AS medico_nome",
                "tipo_exames.nome AS exame",
                DB::raw("date_format(exames_antigos.created_at, '%d/%m/%Y %H:%i:%s') AS exame_data"),
                DB::raw("date_format(exames_antigos.laudo_date, '%d/%m/%Y %H:%i:%s') AS laudo_data"),
                "exames_antigos.protocolo AS protocolo",
                "exames_antigos.status",
                DB::raw(($isContaMedico ? "''" : 'clientes.nome') . ' as cliente'),
                DB::raw(($isContaMedico ? "''" : 'exames_antigos.arquivo_laudo') . ' as arquivo_laudo'),
                DB::raw(($isContaCliente ? '1' : '0') . ' as iscliente'),
                DB::raw(($isContaMedico ? '1' : '0') . ' as ismedico'),
                DB::raw(($isContaEmpresa ? '1' : '0') . ' as isempresa'),
            )
            ->join("tipo_exames", "tipo_exames.id", "=", "exames_antigos.exame_id")
            ->join("clientes", "clientes.id", "=", "exames_antigos.cliente_id")
            ->join("medicos", "medicos.id", "=", "exames_antigos.medico_id");

        /*==========================================================*/
        /* Contagem de totais de linhas                             */
        /*==========================================================*/
        self::$recordsTotalQuery = $query->clone()
            ->select(DB::raw('count(exames_antigos.id) as recordsTotal'));

        if ($isContaCliente) {
            $usuarioId = $usuario->restringir_exames == 1 ? $usuario->id : 0;
            $query->whereRaw("(exames_antigos.cliente_id = ? OR exames_antigos.recepcionado = ?)", [$usuario->conta_cliente, $usuario->conta_cliente])
                ->whereRaw("IF (? = 0, TRUE, exames_antigos.digitado = ?)", [$usuarioId, $usuarioId]);

            /*==========================================================*/
            /* Where: Contagem de totais de linhas                      */
            /*==========================================================*/
            self::$recordsTotalQuery->whereRaw("(exames_antigos.cliente_id = ? OR exames_antigos.recepcionado = ?)", [$usuario->conta_cliente, $usuario->conta_cliente])
                ->whereRaw("IF (? = 0, TRUE, exames_antigos.digitado = ?)", [$usuarioId, $usuarioId]);
        } else if ($isContaMedico) {
            $query->whereRaw("exames_antigos.medico_id = {$usuario->conta_medico}");

            /*==========================================================*/
            /* Where: Contagem de totais de linhas                      */
            /*==========================================================*/
            self::$recordsTotalQuery->whereRaw("exames_antigos.medico_id = {$usuario->conta_medico}");
        } else {
            if ($empresa->id != 1) {
                $query->where('exames_antigos.empresa_id', $empresa->id);

                /*==========================================================*/
                /* Where: Contagem de totais de linhas                      */
                /*==========================================================*/
                self::$recordsTotalQuery->where('exames_antigos.empresa_id', $empresa->id);
            }
        }

        if ($status != '') {
            $query->where("exames_antigos.ativo", $status);
        }


        return self::serverProcessingBase($query, $whereColumns, $orderColumns);
    }
}
