<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\FromView;

class FaturamentoPacoteExport implements WithColumnFormatting, FromView
{
    const REAL = 'R$ #,##0.00_-';

    private static $EXAMES;

    function __construct($exames)
    {
        Self::$EXAMES = $this->toObject($exames);
    }

    public function columnFormats(): array
    {
        return [
            'D' => Self::REAL,
            'H' => Self::REAL,
            'I' => Self::REAL,
            'J' => Self::REAL,
        ];
    }

    public function view(): View
    {
        $total = $this->total(Self::$EXAMES);
        return view('exports.faturamento-pacote', [
            'exames' => Self::$EXAMES,
            'total' => $total
        ]);
    }

    private function total($exames) {
        $total = 0;
        foreach ($exames as $exame) {
            $total += $exame->total_exames;
        }
        return $total;
    }

    private function toObject($exames) {
        $objects = [];
        foreach ($exames as $exame) {
            $objects[] = (Object)$exame;
        }
        return $objects;
    }
}

