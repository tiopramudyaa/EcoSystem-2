<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Toleransi yang diisi di Attendance Settings dulu tidak pernah dipakai karena
 * presensi membaca tabel shifts. Untuk data yang sudah ada: bila shift default
 * masih 0 menit padahal Settings sudah berisi toleransi, salin nilai Settings
 * ke shift default. Nilai shift yang sudah diisi tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = DB::table('attendance_settings')->first();

        if (!$settings || (int) $settings->default_late_tolerance_minutes <= 0) {
            return;
        }

        DB::table('shifts')
            ->where('is_default', true)
            ->where('late_tolerance_minutes', 0)
            ->update(['late_tolerance_minutes' => (int) $settings->default_late_tolerance_minutes]);
    }

    public function down(): void
    {
        // Penyelarasan data satu arah; tidak ada yang perlu dikembalikan.
    }
};
