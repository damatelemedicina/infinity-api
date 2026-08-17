<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class BaseExport extends DefaultValueBinder implements WithCustomValueBinder {

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'A') return parent::bindValue($cell, $value);
        if (is_numeric($value)) {
            $pos = $cell->getColumn() . ':' . $cell->getRow();
            $cell->getStyle($pos)->getNumberFormat()->setFormatCode('R$ #,##0.00_-');
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    protected function total($sumario) {
        $total = 0;
        foreach ($sumario as $item) {
            $total += $item->total;
        }
        return $total;
    }

}
