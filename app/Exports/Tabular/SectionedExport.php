<?php

namespace App\Exports\Tabular;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * One sheet made of stacked sections (a form-like export): a title, then for each record a details block
 * (label | value) and one or more tables below it.
 *
 * `$marks` (1-based sheet rows):
 *   section  => rows that are a section band        header => rows that are a table heading
 *   label    => detail rows (bold label in A, value in B merged across)
 *   tables   => [[firstRow, lastRow], …] bordered table bodies
 *   formats  => [[range, excel number format], …]
 *   lists    => [[range, [dropdown options], what], …]
 */
class SectionedExport implements FromArray, WithTitle, WithEvents
{
    private const COLS = 8;

    public function __construct(
        private string $sheetTitle,
        private array $rows,
        private array $marks,
        private array $widths = ['A' => 30, 'B' => 48, 'C' => 20, 'D' => 14, 'E' => 18, 'F' => 14, 'G' => 12, 'H' => 40],
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return substr(preg_replace('/[\\\\\/\*\?\:\[\]]/', '-', $this->sheetTitle), 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = Coordinate::stringFromColumnIndex(self::COLS);
                $m     = $this->marks;

                foreach ($this->widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                $sheet->mergeCells("A1:{$last}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->mergeCells("A2:{$last}2");
                $sheet->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('6B7280');

                foreach ($m['section'] ?? [] as $row) {
                    $style = $sheet->getStyle("A{$row}:{$last}{$row}");
                    $style->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('991B1B');
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                }

                foreach ($m['label'] ?? [] as $row => $lines) {
                    $sheet->mergeCells("B{$row}:{$last}{$row}");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}:{$last}{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                    $sheet->getStyle("A{$row}:{$last}{$row}")->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                    $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
                    if ($lines > 1) {
                        $sheet->getRowDimension($row)->setRowHeight(15 * $lines);
                    }
                }

                foreach ($m['header'] ?? [] as $row => $cols) {
                    $range = "A{$row}:" . Coordinate::stringFromColumnIndex($cols) . $row;
                    $style = $sheet->getStyle($range);
                    $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('991B1B');
                    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
                    $sheet->getRowDimension($row)->setRowHeight(24);
                }

                foreach ($m['tables'] ?? [] as [$from, $to, $cols, $lines]) {
                    if ($to < $from) {
                        continue;
                    }
                    $range = "A{$from}:" . Coordinate::stringFromColumnIndex($cols) . $to;
                    $sheet->getStyle($range)->getBorders()->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                    $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
                    foreach ($lines as $row => $count) {
                        $sheet->getRowDimension($row)->setRowHeight(15 * $count);
                    }
                }

                foreach ($m['lists'] ?? [] as [$range, $list, $what]) {
                    ListValidation::apply($sheet, $range, $list, $what);
                }

                foreach ($m['formats'] ?? [] as [$range, $code]) {
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode($code);
                    $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
