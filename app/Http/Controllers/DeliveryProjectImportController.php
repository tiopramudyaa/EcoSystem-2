<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\DeliveryProjectType;
use App\Models\EmployeeBasicData;
use App\Services\DeliveryProjectImporter;
use App\Services\ProjectReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Import Delivery Project dari Excel (tombol "Import Excel" di daftar Project).
 * Aturan per baris ada di App\Services\DeliveryProjectImporter.
 * Izin: sama dengan Add New Project (menu delivery-project.add-new).
 */
class DeliveryProjectImportController extends Controller
{
    /**
     * Header template, urutan & grup sama dengan file template tim Delivery.
     * [grup, judul kolom, lebar, wajib]
     */
    private const TEMPLATE = [
        ['Project Form', 'No', 6, false],
        ['Project Form', 'Customer/Client', 34, true],
        ['Project Form', 'Project Owner', 28, true],
        ['Project Form', 'Project Type', 16, true],
        ['Project Form', 'High Level Risk', 15, false],
        ['Project Form', 'Contract Start Date', 16, true],
        ['Project Form', 'Contract End Date', 16, true],
        ['Project Form', 'IO/Number Order', 20, true],
        ['Project Form', 'Project Name', 48, true],
        ['Project Form', 'Description', 36, true],
        ['Delivery Information', 'Account Executive Type', 16, false],
        ['Delivery Information', 'Account Executive Name', 26, false],
        ['Delivery Information', 'Phone Number', 18, false],
        ['Delivery Information', 'Email', 30, false],
        ['Delivery Information', 'Revenue (Rp)', 18, false],
        ['Delivery Information', 'Plan Cost (Rp)', 18, false],
        ['Delivery Information', 'Gross Profit (Rp)', 18, false],
        ['Delivery Information', '% Gross Profit', 12, false],
        ['Delivery Information', 'Delivery Method', 14, false],
        ['Delivery Information', 'Warranty Period (Weeks)', 14, false],
        ['Delivery Information', 'Total Mandays', 12, false],
        ['Location Information', 'Location Name', 26, false],
        ['Location Information', 'Type of Address', 15, false],
        ['Location Information', 'Country', 14, false],
        ['Location Information', 'Geographical', 14, false],
        ['Location Information', 'Region / Province', 20, false],
        ['Location Information', 'City', 18, false],
        ['Location Information', 'Street Address', 40, false],
        ['Location Information', 'Paste from Google Maps (koordinat/link)', 30, false],
        ['Location Information', 'Latitude', 12, false],
        ['Location Information', 'Longitude', 12, false],
    ];

    private const GROUP_COLORS = [
        'Project Form'         => '1F4E78',
        'Delivery Information' => '2E75B6',
        'Location Information' => '548235',
    ];

    private const FIRST_DATA_ROW = 3;
    private const LAST_DATA_ROW  = 502; // 500 baris siap isi (dropdown & rumus)

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Projects');

        $lastCol = count(self::TEMPLATE);
        $colL    = fn (int $i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        $colOf   = [];

        // Baris 1: judul grup (digabung); baris 2: judul kolom.
        $groupStart = 1;
        foreach (self::TEMPLATE as $i => [$group, $title, $width, $required]) {
            $c = $i + 1;
            $colOf[$title] = $colL($c);
            $sheet->setCellValue($colL($c) . '2', $title . ($required ? ' *' : ''));
            $sheet->getColumnDimension($colL($c))->setWidth($width);

            $nextGroup = self::TEMPLATE[$i + 1][0] ?? null;
            if ($nextGroup !== $group) {
                $range = $colL($groupStart) . '1:' . $colL($c) . '1';
                $sheet->setCellValue($colL($groupStart) . '1', $group);
                $sheet->mergeCells($range);
                $sheet->getStyle($colL($groupStart) . '1:' . $colL($c) . '2')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::GROUP_COLORS[$group]]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFBFBF']]],
                ]);
                $groupStart = $c + 1;
            }
        }
        $sheet->getRowDimension(2)->setRowHeight(34);
        $sheet->freezePane('C3');

        // Daftar pilihan di sheet "Lists" (dipakai dropdown).
        $lists = $spreadsheet->createSheet();
        $lists->setTitle('Lists');
        $listData = [
            'Project Type'    => DeliveryProjectType::active()->orderBy('order_seq')->orderBy('name')->pluck('name')->all(),
            'High Level Risk' => DeliveryProjectImporter::RISKS,
            'AE Type'         => DeliveryProjectImporter::AE_TYPES,
            'Delivery Method' => DeliveryProjectImporter::METHODS,
            'Type of Address' => DeliveryProjectImporter::LOCATION_TYPES,
            'Customer Code'   => [],
            'Customer Name'   => [],
            'Employee'        => [],
        ];
        $customers = Customer::with('basicData')->customers()->get()
            ->sortBy(fn ($c) => mb_strtolower($c->basicData->name_1 ?? $c->customer_code ?? ''));
        foreach ($customers as $c) {
            $listData['Customer Code'][] = $c->customer_code;
            $listData['Customer Name'][] = $c->basicData->name_1 ?? '';
        }
        $listData['Employee'] = EmployeeBasicData::all()->map->full_name->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        $listRange = [];
        $li = 1;
        foreach ($listData as $title => $values) {
            $col = $colL($li++);
            $lists->setCellValue($col . '1', $title);
            foreach (array_values($values) as $r => $value) {
                $lists->setCellValueExplicit($col . ($r + 2), (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $lists->getColumnDimension($col)->setAutoSize(true);
            $listRange[$title] = "Lists!\${$col}\$2:\${$col}\$" . max(2, count($values) + 1);
        }
        $lists->getStyle('A1:' . $colL(count($listData)) . '1')->getFont()->setBold(true);
        $lists->freezePane('A2');

        $range = fn (string $title) => $colOf[$title] . self::FIRST_DATA_ROW . ':' . $colOf[$title] . self::LAST_DATA_ROW;
        $dropdown = function (string $title, string $source, bool $strict = true) use ($sheet, $range) {
            $dv = new DataValidation();
            $dv->setType(DataValidation::TYPE_LIST)
               ->setErrorStyle($strict ? DataValidation::STYLE_STOP : DataValidation::STYLE_INFORMATION)
               ->setAllowBlank(true)
               ->setShowDropDown(true)
               ->setShowErrorMessage(true)
               ->setErrorTitle('Invalid value')
               ->setError('Choose a value from the list.')
               ->setFormula1($source);
            $sheet->setDataValidation($range($title), $dv);
        };
        $dropdown('Project Type', $listRange['Project Type']);
        $dropdown('High Level Risk', $listRange['High Level Risk']);
        $dropdown('Account Executive Type', $listRange['AE Type']);
        $dropdown('Delivery Method', $listRange['Delivery Method']);
        $dropdown('Type of Address', $listRange['Type of Address']);
        // Customer & nama karyawan: dropdown sebagai bantuan, teks lain tetap boleh
        // (customer boleh diisi kode atau nama).
        $dropdown('Customer/Client', $listRange['Customer Name'], false);
        $dropdown('Project Owner', $listRange['Employee'], false);
        $dropdown('Account Executive Name', $listRange['Employee'], false);

        // Format & rumus bantu (Gross Profit dihitung ulang saat import).
        $first = self::FIRST_DATA_ROW;
        $last  = self::LAST_DATA_ROW;
        foreach (['Contract Start Date', 'Contract End Date'] as $t) {
            $sheet->getStyle($range($t))->getNumberFormat()->setFormatCode('d-mmm-yy');
        }
        foreach (['Revenue (Rp)', 'Plan Cost (Rp)', 'Gross Profit (Rp)'] as $t) {
            $sheet->getStyle($range($t))->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle($range('% Gross Profit'))->getNumberFormat()->setFormatCode('0.00%');
        foreach (['Phone Number', 'IO/Number Order'] as $t) {
            $sheet->getStyle($range($t))->getNumberFormat()->setFormatCode('@');
        }
        $rev = $colOf['Revenue (Rp)'];
        $pc  = $colOf['Plan Cost (Rp)'];
        $gp  = $colOf['Gross Profit (Rp)'];
        for ($r = $first; $r <= $last; $r++) {
            $sheet->setCellValue("{$gp}{$r}", "=IF({$rev}{$r}=\"\",\"\",{$rev}{$r}-N({$pc}{$r}))");
            $sheet->setCellValue("{$colOf['% Gross Profit']}{$r}", "=IF(OR({$rev}{$r}=\"\",N({$rev}{$r})=0),\"\",{$gp}{$r}/{$rev}{$r})");
        }
        $sheet->getStyle("{$gp}{$first}:{$colOf['% Gross Profit']}{$last}")->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');

        // Sheet panduan.
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Guide');
        $guide->fromArray([
            ['Column', 'Rule'],
            ['No', 'Optional, ignored by the import.'],
            ['Customer/Client *', 'Customer code or customer name from Business Partner (type Customer); "(Induk)" after the name is allowed. A name shared by a parent customer and its branches means the parent; fill the branch customer code to choose a branch. A name shared by unrelated customers is refused: fill the customer code.'],
            ['Project Owner *', 'Full name of an employee in Master Employee.'],
            ['Project Type *', 'An active Project Type: ' . implode(', ', $listData['Project Type']) . '.'],
            ['High Level Risk', 'Low, Moderate or High.'],
            ['Contract Start Date *', 'Date. Excel date, or text such as 2026-01-31, 31/01/2026 or 31-Jan-26.'],
            ['Contract End Date *', 'Date, on or after the Contract Start Date.'],
            ['IO/Number Order *', 'An IO Number may be reused only within the same customer.'],
            ['Project Name *', 'At most 255 characters.'],
            ['Description *', 'Free text.'],
            ['Account Executive Type', 'Internal or External. Required when Account Executive Name is filled.'],
            ['Account Executive Name', 'Internal: full name of an employee. External: free text.'],
            ['Phone Number / Email', 'Optional. Email must be a valid address.'],
            ['Revenue (Rp) / Plan Cost (Rp)', 'Number of 0 or more.'],
            ['Gross Profit (Rp) / % Gross Profit', 'Not read. Recalculated as Revenue - Plan Cost, like the Create Project form.'],
            ['Delivery Method', 'Onsite, Hybrid or WFH.'],
            ['Warranty Period (Weeks) / Total Mandays', 'Whole number of 0 or more.'],
            ['Type of Address', 'Head Office or Plant.'],
            ['Paste from Google Maps', 'Coordinates such as -6.3032,106.8429 or a Google Maps link. Used when Latitude and Longitude are empty.'],
            ['Latitude / Longitude', 'Fill both or neither.'],
            ['Duplicates', 'A row exactly the same as an earlier row in the file is skipped. A row whose Customer, IO Number and Project Name already exist in EcoSystem is skipped. Both are reported, not imported.'],
            ['Errors', 'A row with an error is skipped and reported; the other rows are still imported.'],
            ['Limit', 'At most ' . DeliveryProjectImporter::MAX_ROWS . ' projects per file.'],
        ]);
        $guide->getStyle('A1:B1')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setWidth(38);
        $guide->getColumnDimension('B')->setWidth(110);
        $guide->getStyle('B:B')->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'Template_Import_Delivery_Project.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.mimes' => 'Use an .xlsx, .xls or .csv file.',
            'file.max'   => 'The file may not be larger than 10 MB.',
        ]);

        set_time_limit(300);
        $file = $request->file('file');

        try {
            $result = (new DeliveryProjectImporter())->import(
                $file->getRealPath(),
                strtolower($file->getClientOriginalExtension())
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('DeliveryProjectImportController@import', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'The import failed because of a server error.'], 500);
        }

        if ($result['imported'] > 0) {
            // Project baru bisa langsung masuk jendela pengingat akhir kontrak.
            app(ProjectReminderService::class)->syncAllQuietly();
        }

        AuditLog::recordAction(
            module: 'Delivery Project',
            auditableType: 'delivery_project_import',
            auditableId: 0,
            event: 'imported',
            recordLabel: $file->getClientOriginalName(),
            description: "imported delivery projects ({$result['imported']} added, {$result['duplicates']} duplicates skipped, {$result['errors']} rows with errors)",
            old: null,
            new: ['imported' => $result['imported'], 'duplicates' => $result['duplicates'], 'errors' => $result['errors']],
        );

        return response()->json([
            'success' => true,
            'message' => "Import finished: {$result['imported']} imported, {$result['duplicates']} duplicates skipped, {$result['errors']} rows with errors.",
        ] + $result);
    }
}
