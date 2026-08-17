<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class FinanceiroMedicoExport extends BaseExport implements FromView
{

    private static $LAUDOS;

    function __construct($laudos)
    {
        Self::$LAUDOS = $this->toObject($laudos);
    }

    public function view(): View
    {
        $sumario = $this->sumario(Self::$LAUDOS);
        $total_geral = $this->total($sumario);
        return view('exports.financeiro-medico', [
            'laudos' => Self::$LAUDOS,
            'sumario' =>$sumario,
            'total_geral' => $total_geral
        ]);
    }

    private function sumario($laudos) {
        $total = [];
        foreach ($laudos as $laudo) {
            $total[$laudo->tipo_exame] = isset($total[$laudo->tipo_exame])
                ? ( $total[$laudo->tipo_exame] + number_format($laudo->preco_laudo, 2) )
                : number_format($laudo->preco_laudo, 2);
        }
        $object = [];
        foreach($total as $exame => $total) {
            $object[] = (Object) array('exame' => $exame, 'total' => $total);
        }
        return $object;
    }

    private function toObject($laudos) {
        $objects = [];
        foreach ($laudos as $laudo) {
            $objects[] = (Object)$laudo;
        }
        return $objects;
    }
}

