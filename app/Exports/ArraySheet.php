<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** One worksheet from plain arrays. Text a spreadsheet could run as a formula is neutralised (the reports come from anyone). */
class ArraySheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithCustomCsvSettings
{
    public function __construct(private string $title, private array $headings, private array $rows) {}

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return array_map(fn (array $row) => array_map(fn ($v) => is_string($v) && $v !== '' && str_contains("=+-@\t\r", $v[0]) ? "'" . $v : $v, $row), $this->rows);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function getCsvSettings(): array
    {
        return ['use_bom' => true];
    }
}
