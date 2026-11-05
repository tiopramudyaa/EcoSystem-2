<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet "Release Schedule" — jadwal release bulanan per cost item
 * (data dari ProjectMonthlyCostReport::releaseSchedules()).
 *
 *   1. Rekap per cost item: Total Release, Terjadwal, Belum Dijadwalkan.
 *   2. Rincian jadwal: satu baris per jadwal release.
 */
class DeliveryProjectReleaseScheduleSheet implements WithTitle, WithEvents
{
    private const HEADER_STYLE = [
        'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFCC0000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ];

    private const TOTAL_STYLE = [
        'font'    => ['bold' => true],
        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
        'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
    ];

    public function __construct(private array $items)
    {
    }

    public function title(): string
    {
        return 'Release Schedule';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $text  = fn (string $cell, $value) => $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                $num   = fn (string $cell, $value) => $sheet->setCellValueExplicit($cell, (float) $value, DataType::TYPE_NUMERIC);
                $type  = fn (string $t) => $t === 'indirect' ? 'Indirect Cost' : 'Direct Cost';

                // ── 1. Rekap per cost item ────────────────────────────
                $text('A1', 'RELEASE SUMMARY PER COST ITEM');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

                $head = 2;
                foreach (['No', 'Cost Type', 'Cost Code', 'Cost Item', 'Total Release', 'Scheduled', 'Unscheduled'] as $i => $label) {
                    $text(chr(65 + $i) . $head, $label);
                }
                $sheet->getStyle("A{$head}:G{$head}")->applyFromArray(self::HEADER_STYLE);

                $row = $head + 1;
                $sum = ['total_release' => 0, 'scheduled' => 0, 'unscheduled' => 0];
                foreach ($this->items as $i => $it) {
                    $num("A{$row}", $i + 1);
                    $text("B{$row}", $type($it['cost_type']));
                    $text("C{$row}", $it['cost_code']);
                    $text("D{$row}", $it['cost_name']);
                    $num("E{$row}", $it['total_release']);
                    $num("F{$row}", $it['scheduled']);
                    $num("G{$row}", $it['unscheduled']);
                    foreach ($sum as $k => $v) {
                        $sum[$k] = $v + $it[$k];
                    }
                    $row++;
                }
                $text("D{$row}", 'Total');
                $num("E{$row}", $sum['total_release']);
                $num("F{$row}", $sum['scheduled']);
                $num("G{$row}", $sum['unscheduled']);
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray(self::TOTAL_STYLE);
                $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('E' . ($head + 1) . ":G{$row}")->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
                $sheet->getStyle("A{$head}:G{$row}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');

                // ── 2. Rincian jadwal ─────────────────────────────────
                $title = $row + 3;
                $text("A{$title}", 'RELEASE SCHEDULE DETAILS');
                $sheet->getStyle("A{$title}")->getFont()->setBold(true)->setSize(12);

                $head = $title + 1;
                foreach (['No', 'Cost Type', 'Cost Code', 'Cost Item', 'Date', 'Period', 'Amount', 'Notes'] as $i => $label) {
                    $text(chr(65 + $i) . $head, $label);
                }
                $sheet->getStyle("A{$head}:H{$head}")->applyFromArray(self::HEADER_STYLE);

                $row   = $head + 1;
                $no    = 0;
                $total = 0;
                foreach ($this->items as $it) {
                    foreach ($it['schedules'] as $s) {
                        $num("A{$row}", ++$no);
                        $text("B{$row}", $type($it['cost_type']));
                        $text("C{$row}", $it['cost_code']);
                        $text("D{$row}", $it['cost_name']);
                        $num("E{$row}", Date::PHPToExcel($s['date']->copy()->startOfDay()));
                        // Period = tgl 1 bulan tsb. berformat "mmm yyyy" (urut kronologis).
                        $num("F{$row}", Date::PHPToExcel($s['date']->copy()->startOfMonth()));
                        $num("G{$row}", $s['amount']);
                        $text("H{$row}", $s['notes'] ?: '-');
                        $total += $s['amount'];
                        $row++;
                    }
                }

                if ($no === 0) {
                    $text("A{$row}", 'No release schedule yet.');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                } else {
                    $last = $row - 1;
                    $sheet->getStyle('E' . ($head + 1) . ":E{$last}")->getNumberFormat()->setFormatCode('dd mmm yyyy');
                    $sheet->getStyle('F' . ($head + 1) . ":F{$last}")->getNumberFormat()->setFormatCode('mmm yyyy');
                    foreach (['A', 'B', 'C', 'E', 'F'] as $col) {
                        $sheet->getStyle("{$col}" . ($head + 1) . ":{$col}{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $text("F{$row}", 'Total');
                    $num("G{$row}", $total);
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray(self::TOTAL_STYLE);
                    $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('G' . ($head + 1) . ":G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                }
                $sheet->getStyle("A{$head}:H{$row}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');

                foreach (['A' => 6, 'B' => 16, 'C' => 12, 'D' => 34, 'E' => 18, 'F' => 18, 'G' => 20, 'H' => 40] as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
