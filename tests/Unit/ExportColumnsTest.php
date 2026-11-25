<?php

namespace Tests\Unit;

use App\Exports\DeliverySupportExport;
use App\Exports\SelectedColumnsExport;
use App\Exports\TicketExport;
use App\Support\ExportColumns;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Pilihan kolom export (field picker + template). Tidak menyentuh database.
 */
class ExportColumnsTest extends TestCase
{
    public function test_every_export_type_has_a_non_empty_unique_column_catalog(): void
    {
        foreach (array_keys(ExportColumns::TYPES) as $type) {
            $catalog = ExportColumns::catalog($type);
            $this->assertNotEmpty($catalog, $type);
            $this->assertSame(count($catalog), count(array_unique($catalog)), "duplicate header in {$type}");
        }
    }

    public function test_catalog_comes_from_the_real_export_headers(): void
    {
        $catalog = ExportColumns::catalog('ticket');
        $this->assertContains('Tiket', $catalog);
        $this->assertContains('Description', $catalog);
        $this->assertContains('Respon Time Status', $catalog);
    }

    public function test_sanitize_drops_unknown_columns_and_keeps_file_order(): void
    {
        $cols = ExportColumns::sanitize('ticket', ['Description', 'Nope', 'Tiket', 123, ['x']]);
        $this->assertSame(['Tiket', 'Description'], $cols);
        $this->assertSame([], ExportColumns::sanitize('ticket', 'Tiket'));
        $this->assertSame([], ExportColumns::sanitize('ticket', null));
    }

    public function test_request_without_cols_or_with_all_cols_means_full_export(): void
    {
        $this->assertNull(ExportColumns::fromRequest(new Request(), 'ticket'));
        $this->assertNull(ExportColumns::fromRequest(new Request(['cols' => ['Nope']]), 'ticket'));
        $this->assertNull(ExportColumns::fromRequest(new Request(['cols' => ExportColumns::catalog('ticket')]), 'ticket'));
        $this->assertSame(['Tiket'], ExportColumns::fromRequest(new Request(['cols' => ['Tiket']]), 'ticket'));
    }

    public function test_selected_columns_export_writes_only_chosen_columns_with_original_values(): void
    {
        $rows = collect([
            ['name' => 'Sup A', 'type' => 'T1', 'io_number' => 'IO-1', 'total_mandays' => 0, 'start_date' => '2026-01-01',
                'end_date' => '2026-02-01', 'delivery_owner' => 'Ann', 'support_manager' => 'Bob', 'co_pm' => '-',
                'support_admin' => '-', 'sales' => '-', 'customer' => 'Acme'],
        ]);

        $export = new SelectedColumnsExport(new DeliverySupportExport($rows), ['Support Name', 'Total Mandays', 'Customer']);
        $sheet  = IOFactory::load($this->writeTemp($export))->getActiveSheet()->toArray(null, true, false, false);

        // Urutan mengikuti export asli (Customer, Support Name, ..., Total Mandays).
        $this->assertSame(['Customer', 'Support Name', 'Total Mandays'], $sheet[0]);
        $this->assertSame(['Acme', 'Sup A', 0], $sheet[1]);
        $this->assertCount(2, $sheet);
    }

    public function test_selected_columns_export_works_for_collection_based_exports(): void
    {
        $rows = collect([[
            'ticket_number' => 'TCK-1', 'description' => 'Printer down', 'created_at' => now(), 'last_update' => now(),
            'closed_at' => null, 'day_on_close' => 3, 'activity_date' => null, 'sla' => null,
            'customer' => ['customer_name' => 'Acme'], 'end_customer_name' => null, 'employee' => null,
            'ticket_priority' => 'High', 'scale' => 'S1', 'status' => 'open', 'ticket_type' => 'Incident',
            'module' => 'FI', 'customer_mandays' => null, 'all_consultant_progress' => 0, 'assign_delivery' => null,
        ]]);

        $export = new SelectedColumnsExport(new TicketExport($rows), ['Tiket', 'Description', 'Customer']);
        $sheet  = IOFactory::load($this->writeTemp($export))->getActiveSheet()->toArray(null, true, false, false);

        $this->assertSame(['Tiket', 'Description', 'Customer'], $sheet[0]);
        $this->assertSame(['TCK-1', 'Printer down', 'Acme'], $sheet[1]);
    }

    private function writeTemp(object $export): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xl') . '.xlsx';
        file_put_contents($path, Excel::raw($export, ExcelWriter::XLSX));

        return $path;
    }
}
