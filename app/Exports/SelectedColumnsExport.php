<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Membungkus export list mana pun dan hanya menulis kolom yang dipilih user.
 *
 * Header dan nilai diambil dari export aslinya (FromCollection + WithHeadings
 * atau FromArray dengan baris pertama = header), lalu disaring berdasarkan
 * NAMA kolom. Urutan kolom mengikuti export aslinya. Baris data tidak disentuh,
 * jadi isi tiap sel sama persis dengan export penuh.
 *
 * Format khusus per-kolom milik export asli (format angka, rata tengah) tidak
 * ikut, karena huruf kolomnya berubah saat kolom dibuang. Yang dipakai: header
 * merah, baris pertama dibekukan, lebar kolom otomatis.
 */
class SelectedColumnsExport implements FromArray, WithEvents, WithTitle, WithStrictNullComparison, ShouldAutoSize
{
    /** @var object Export asli */
    protected $inner;

    /** @var string[] Nama kolom yang dipertahankan */
    protected array $columns;

    protected int $rowCount = 0;

    /**
     * @param object   $inner   Export asli (FromCollection+WithHeadings atau FromArray)
     * @param string[] $columns Nama kolom (sama dengan teks header) yang dipilih
     */
    public function __construct($inner, array $columns)
    {
        $this->inner   = $inner;
        $this->columns = array_values($columns);
    }

    /**
     * Header + baris export asli sebagai matriks mentah (belum disaring).
     *
     * @return array{0: string[], 1: array<int, array>}
     */
    public static function matrix($inner): array
    {
        if ($inner instanceof FromArray) {
            $all     = $inner->array();
            $headers = array_map('strval', array_values($all[0] ?? []));
            $rows    = array_map(fn ($r) => array_values((array) $r), array_slice($all, 1));

            return [$headers, $rows];
        }

        if ($inner instanceof FromCollection && $inner instanceof WithHeadings) {
            $headers = array_map('strval', array_values($inner->headings()));
            $rows    = $inner->collection()
                ->map(fn ($r) => array_values((array) $r))
                ->all();

            return [$headers, $rows];
        }

        throw new \InvalidArgumentException('Export tidak mendukung pemilihan kolom: ' . get_class($inner));
    }

    public function title(): string
    {
        return $this->inner instanceof WithTitle ? $this->inner->title() : 'Export';
    }

    public function array(): array
    {
        [$headers, $rows] = self::matrix($this->inner);

        // Indeks kolom yang dipilih, urut seperti export asli.
        $keep = [];
        foreach ($headers as $i => $name) {
            if (in_array($name, $this->columns, true)) {
                $keep[] = $i;
            }
        }

        $out = [array_map(fn ($i) => $headers[$i], $keep)];
        foreach ($rows as $row) {
            $out[] = array_map(fn ($i) => $row[$i] ?? null, $keep);
        }

        $this->rowCount = count($out) - 1;

        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$last}1")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFCC0000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                $sheet->freezePane('A2');
            },
        ];
    }
}
