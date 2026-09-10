<?php

namespace App\ViewModels;

use Illuminate\Support\Facades\DB;

class PainelViewModel extends BaseViewModel
{
    public static function getPainel($clienteId, $medicoId, $matrizId)
    {
        $sql = "CALL GetPainel({$clienteId},{$medicoId},{$matrizId})";

        $result = Self::callStoredProcedures($sql);
        $result[] = Self::getExamesSemLaudarMedico($clienteId, $medicoId, $matrizId);

        return $result;
    }

    protected static function getExamesSemLaudarMedico($clienteId, $medicoId, $matrizId)
    {
        $tableExames = DB::table('exames')
            ->selectRaw('coalesce(medicos.nome, \'SEM MEDICO\') as medico, count(exames.exame_id) as total')
            ->leftJoin('medicos', 'medicos.id', '=', 'exames.medico_id')
            ->join('empresas', 'empresas.id', '=', 'exames.empresa_id')
            ->where('exames.status', '=', 0)
            ->where('exames.ativo', '=', 1)
            ->groupBy('exames.medico_id')
            ->orderByRaw('(' . PHP_EOL
                . '    case when medicos.nome is null' . PHP_EOL
                . '    then 0 -- forca ir para o fim da lista' . PHP_EOL
                . '    else total end' . PHP_EOL
                . ') desc');

        if (empty($clienteId) === false) {
            $tableExames->where('exames.cliente_id', '=', $clienteId);
        }

        if (empty($medicoId) === false) {
            $medicosExames = DB::table('medicos_exames')
                ->select('medicos_exames.tipo_exame_id')
                ->where('medicos_exames.medico_id', '=', $medicoId);

            $tableExames->whereIn('exames.medico_id', $medicosExames)
                ->where(function ($query) use ($medicoId) {
                    $query->where('exames.medico_id', '=', $medicoId)
                        ->orWhere('exames.medico_id', '=', 0);
                });
        }

        if (empty($matrizId) === false) {
            $tableExames->where('empresas.matriz', '=', $matrizId);
        }

        return $tableExames->get()->toArray();
    }
}
