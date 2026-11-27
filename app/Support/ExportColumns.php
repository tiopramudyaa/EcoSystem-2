<?php

namespace App\Support;

use App\Exports\DeliveryProjectExport;
use App\Exports\DeliverySupportExport;
use App\Exports\EmployeeExport;
use App\Exports\SelectedColumnsExport;
use App\Exports\TicketExport;
use App\Exports\TimesheetApprovalExport;
use App\Exports\TimesheetNonProjectExport;
use App\Exports\TimesheetOfficeExport;
use App\Exports\TimesheetProjectExport;
use App\Exports\TimesheetSupportExport;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Pilihan kolom + template untuk tombol Export di list.
 *
 * Daftar kolom yang bisa dipilih TIDAK ditulis ulang di sini: diambil dari
 * header export aslinya (lewat instance kosong), jadi kolom baru di export
 * otomatis muncul di pilihan. Kolom diidentifikasi dari teks header-nya.
 *
 * Alur: pop-up di browser mengirim `cols[]` (nama kolom) bersama request export
 * yang sudah ada (filter list tetap berlaku). Tanpa `cols`, atau semua kolom
 * dipilih, export berjalan seperti semula.
 */
class ExportColumns
{
    /** Jenis export => label di pop-up. */
    public const TYPES = [
        'ticket'                => 'Support Tickets',
        'timesheet_all'         => 'Timesheet (All)',
        'timesheet_project'     => 'Timesheet (Project)',
        'timesheet_non_project' => 'Timesheet (Non Project)',
        'timesheet_support'     => 'Timesheet (Support)',
        'timesheet_office'      => 'Timesheet (Office)',
        'delivery_project'      => 'Delivery Project',
        'delivery_support'      => 'Delivery Support',
        'employee'              => 'Master Employee',
    ];

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    /** Export kosong, hanya untuk membaca header. */
    private static function blank(string $type): object
    {
        // Eloquent Collection: sebagian export mensyaratkannya di constructor,
        // dan ia juga lolos untuk yang meminta Support Collection.
        $none = new EloquentCollection();

        return match ($type) {
            'ticket'                => new TicketExport($none),
            'timesheet_all'         => new TimesheetApprovalExport($none),
            'timesheet_project'     => new TimesheetProjectExport($none),
            'timesheet_non_project' => new TimesheetNonProjectExport($none),
            'timesheet_support'     => new TimesheetSupportExport($none, [], $none),
            'timesheet_office'      => new TimesheetOfficeExport($none),
            'delivery_project'      => new DeliveryProjectExport($none),
            'delivery_support'      => new DeliverySupportExport($none),
            'employee'              => new EmployeeExport($none),
        };
    }

    /**
     * Semua kolom yang bisa dipilih untuk satu jenis export, urut seperti file.
     *
     * @return string[]
     */
    public static function catalog(string $type): array
    {
        [$headers] = SelectedColumnsExport::matrix(self::blank($type));

        return array_values(array_unique($headers));
    }

    /**
     * Saring daftar nama kolom ke yang valid saja (urut seperti file).
     *
     * @param  mixed $requested
     * @return string[]
     */
    public static function sanitize(string $type, $requested): array
    {
        if (!is_array($requested)) {
            return [];
        }

        $requested = array_map('strval', array_filter($requested, 'is_scalar'));

        return array_values(array_filter(
            self::catalog($type),
            fn (string $col) => in_array($col, $requested, true)
        ));
    }

    /**
     * Kolom terpilih dari request (`cols`), atau null = export penuh seperti biasa.
     *
     * @return string[]|null
     */
    public static function fromRequest(Request $request, string $type): ?array
    {
        $cols = self::sanitize($type, $request->input('cols'));

        if (empty($cols) || count($cols) === count(self::catalog($type))) {
            return null;
        }

        return $cols;
    }

    /**
     * Unduh export; kalau request membawa pilihan kolom, hanya kolom itu yang ditulis.
     */
    public static function download(Request $request, string $type, object $export, string $filename)
    {
        $cols = self::fromRequest($request, $type);

        return Excel::download($cols ? new SelectedColumnsExport($export, $cols) : $export, $filename);
    }
}
