<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BaseExport extends DefaultValueBinder implements WithCustomValueBinder, ShouldAutoSize {

    protected $lastColumn = '';
    protected $lastRow = null;

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getRow() == 1) {
            $this->lastColumn = $cell->getColumn();
        } else if ($cell->getColumn() == $this->lastColumn) {
            $cell->getWorksheet()->getRowDimension($cell->getRow())->setRowHeight(18);
        }
        if ($cell->getRow() == 2 && $cell->getColumn() == 'A') {
            $cell->getWorksheet()->setAutoFilter($cell->getColumn() . '1:' . $this->lastColumn . '1');
            $cell->getWorksheet()->freezePane($cell->getColumn() . '2');
            $cell->getWorksheet()->getRowDimension(1)->setRowHeight(18);
        }
        if (empty($this->lastRow) == false && $cell->getRow() == $this->lastRow) {
            $cell->getWorksheet()->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }

        if ($cell->getColumn() == 'A') return parent::bindValue($cell, $value);
        if (is_numeric($value)) {
            $pos = $cell->getColumn() . ':' . $cell->getRow();
            $cell->getStyle($pos)->getNumberFormat()->setFormatCode('R$ #,##0.00_-');
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            return true;
        }
        if (Carbon::hasFormat($value, 'Y-m-d H:i:s')) {
            $pos = $cell->getColumn() . ':' . $cell->getRow();
            $cell->setValue(Date::PHPToExcel(Carbon::parse($value)));
            $cell->getStyle($pos)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
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
