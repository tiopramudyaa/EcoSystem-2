<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penggabungan eco_new + alnaf_hr: halaman Management > Employee > Dropdown Settings (tabel dropdown_configs /
 * dropdown_config_values) digantikan Management > Employee (employee_field_options + tabel positions / departments).
 *
 * Migrasi ini menyalin nilai yang sudah diisi admin di produksi supaya tidak hilang dari form karyawan:
 *   - position   -> positions
 *   - department -> departments
 *   - country    -> employee_field_options (section address, field country)
 *
 * Daftar division, personnel_area, personnel_subarea, employee_group, employee_subgroup tidak punya padanan tabel di sistem baru
 * (enum PHP). Nilainya tetap dibaca dari dropdown_config_values oleh AppServiceProvider dan digabung dengan enum, jadi tidak ada
 * nilai yang hilang. Tabel dropdown_configs dan dropdown_config_values sengaja TIDAK dihapus.
 *
 * Idempoten: aman dijalankan ulang; nilai yang sudah ada tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dropdown_configs') || !Schema::hasTable('dropdown_config_values')) {
            return;
        }

        $values = fn (string $code) => DB::table('dropdown_config_values as v')
            ->join('dropdown_configs as c', 'c.id', '=', 'v.dropdown_config_id')
            ->where('c.code', $code)
            ->orderBy('v.sort_order')->orderBy('v.value')
            ->get(['v.value', 'v.sort_order', 'v.is_active']);

        $now = now();

        foreach (['position' => 'positions', 'department' => 'departments'] as $code => $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $next = (int) DB::table($table)->max('sort_order');
            foreach ($values($code) as $row) {
                $name = trim((string) $row->value);
                if ($name === '' || DB::table($table)->where('name', $name)->exists()) {
                    continue;
                }
                DB::table($table)->insert([
                    'name'       => $name,
                    'sort_order' => ++$next,
                    'is_active'  => (bool) $row->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('employee_field_options')) {
            $next = (int) DB::table('employee_field_options')->where('section', 'address')->where('field', 'country')->max('sort_order');
            foreach ($values('country') as $row) {
                $name = trim((string) $row->value);
                if ($name === '' || DB::table('employee_field_options')
                    ->where(['section' => 'address', 'field' => 'country', 'value' => $name])->exists()) {
                    continue;
                }
                DB::table('employee_field_options')->insert([
                    'section'       => 'address',
                    'field'         => 'country',
                    'value'         => $name,
                    'label'         => $name,
                    'sort_order'    => ++$next,
                    'is_active'     => (bool) $row->is_active,
                    'internal_only' => false,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
        // Halaman Dropdown Settings sudah dihapus: nonaktifkan izin menunya agar tidak tampil sebagai izin yang tidak berfungsi.
        if (Schema::hasTable('menu') && Schema::hasColumn('menu', 'is_active')) {
            DB::table('menu')->where('slug', 'like', 'management.employee.dropdown-settings%')->update(['is_active' => false]);
        }
    }

    public function down(): void
    {
        // Tidak ada yang dibalik: data hasil salinan bercampur dengan data yang dibuat langsung di tabel tujuan.
    }
};
