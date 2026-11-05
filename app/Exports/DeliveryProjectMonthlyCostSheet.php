<?php

namespace App\Exports;

use App\Models\DeliveryProject;
use App\Models\DeliverySupport;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet "Monthly Cost" — report Monthly Cost vs TOP Invoiced, sama dengan
 * tampilan toggle "Monthly Cost vs TOP Invoiced" di section Plan Cost
 * (data dari ProjectMonthlyCostReport::build()).
 */
class DeliveryProjectMonthlyCostSheet implements WithTitle, WithEvents
{
    private const HEADER_ROW = 12;

    /** Release, Actual, TOP Invoiced, Selisih per Bulan, Selisih Kumulatif. */
    private const MONEY_COLS = ['B', 'C', 'D', 'E', 'F'];

    public function __construct(
        private DeliveryProject|DeliverySupport $project,
        private array $report,
    ) {
    }

    public function title(): string
    {
        return 'Monthly Cost';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $text   = fn (string $cell, $value) => $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                $totals = $this->report['totals'];

                // ── Judul + info project ──────────────────────────────
                $text('A1', 'MONTHLY COST VS TOP INVOICED');
                $sheet->mergeCells('A1:F1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $info = [
                    ($this->project instanceof DeliverySupport ? 'Support' : 'Project') => $this->project->name,
                    'IO Number'   => $this->project->io_number ?: '-',
                    'Customer'    => $this->project->client->basicData->name_1 ?? '-',
                    'Exported At' => now()->format('d M Y H:i'),
                ];
                $row = 2;
                foreach ($info as $label => $value) {
                    $text("A{$row}", $label);
                    $text("B{$row}", $value);
                    $sheet->mergeCells("B{$row}:F{$row}");
                    $row++;
                }

                // ── Ringkasan (= kartu di layar) ──────────────────────
                $cards = [
                    'Total Release'                                 => $totals['release'],
                    'Total Actual'                                  => $totals['actual'],
                    'Total TOP Invoiced'                            => $totals['top'],
                    'Cumulative Position (TOP Invoiced - Actual)'      => $totals['diff'],
                ];
                $row = 7;
                foreach ($cards as $label => $value) {
                    $text("A{$row}", $label);
                    $sheet->setCellValueExplicit("B{$row}", (float) $value, DataType::TYPE_NUMERIC);
                    $row++;
                }
                $sheet->getStyle('A2:A10')->getFont()->setBold(true);
                $sheet->getStyle('B7:B10')->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
                $sheet->getStyle('B7:B10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // ── Header tabel ──────────────────────────────────────
                $headerRow = self::HEADER_ROW;
                $headings  = ['Period', 'Release Cost', 'Actual Cost', 'TOP Invoiced', 'Monthly Difference', 'Cumulative Difference'];
                foreach ($headings as $i => $label) {
                    $text(chr(65 + $i) . $headerRow, $label);
                }
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFCC0000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                // ── Baris per bulan ───────────────────────────────────
                $row = $headerRow + 1;
                foreach ($this->report['months'] as $m) {
                    // Period ditulis sebagai tanggal (tgl 1) berformat "mmm yyyy".
                    $sheet->setCellValueExplicit(
                        "A{$row}",
                        Date::PHPToExcel(Carbon::createFromFormat('!Y-m', $m['period'])),
                        DataType::TYPE_NUMERIC
                    );
                    $sheet->getStyle("A{$row}")->getNumberFormat()->setFormatCode('mmm yyyy');
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $this->writeMoney($sheet, $row, $m);
                    $row++;
                }

                if ($this->report['has_unscheduled']) {
                    $text("A{$row}", 'Unscheduled / no period');
                    $this->writeMoney($sheet, $row, $this->report['unscheduled']);
                    $sheet->getStyle("A{$row}:F{$row}")->getFont()->setItalic(true);
                    $row++;
                }

                if ($row === $headerRow + 1) {
                    $text("A{$row}", 'No monthly data yet.');
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $row++;
                }

                // ── Total ─────────────────────────────────────────────
                $text("A{$row}", 'Total');
                $this->writeMoney($sheet, $row, [
                    'release'    => $totals['release'],
                    'actual'     => $totals['actual'],
                    'top'        => $totals['top'],
                    'diff'       => $totals['diff'],
                    'cumulative' => $totals['diff'],
                ]);
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'font'    => ['bold' => true],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
                ]);
                $lastRow = $row;

                // ── Format ────────────────────────────────────────────
                $sheet->getStyle('B' . ($headerRow + 1) . ":F{$lastRow}")
                    ->getNumberFormat()->setFormatCode('#,##0;[Red]-#,##0');
                $sheet->getStyle("A{$headerRow}:F{$lastRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD1D5DB');

                $notes = [
                    '* Monthly Difference = TOP Invoiced - Actual. Positive: more has been invoiced to the customer than spent; negative (red): costs exceed what has been invoiced.',
                    '* Cumulative Difference = running total of Monthly Difference up to that month.',
                    '* TOP Invoiced = terms with status Invoiced / Paid, grouped by Submit Invoice Date month. Release Cost comes from the monthly release schedule.',
                    '* Unscheduled release and expenses without a period are shown in the "Unscheduled / no period" row.',
                ];
                foreach ($notes as $i => $note) {
                    $cell = 'A' . ($lastRow + 2 + $i);
                    $text($cell, $note);
                    $sheet->getStyle($cell)->getFont()->setItalic(true)->getColor()->setARGB('FF6B7280');
                }

                $sheet->getColumnDimension('A')->setWidth(40);
                foreach (self::MONEY_COLS as $col) {
                    $sheet->getColumnDimension($col)->setWidth(22);
                }
                $sheet->freezePane('A' . ($headerRow + 1));
            },
        ];
    }

    /** Tulis lima kolom angka; null dibiarkan kosong (= "-" di layar). */
    private function writeMoney($sheet, int $row, array $r): void
    {
        $values = [$r['release'], $r['actual'], $r['top'], $r['diff'], $r['cumulative']];
        foreach (self::MONEY_COLS as $i => $col) {
            if ($values[$i] !== null) {
                $sheet->setCellValueExplicit("{$col}{$row}", (float) $values[$i], DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit("{$col}{$row}", '-', DataType::TYPE_STRING);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
    }
}
