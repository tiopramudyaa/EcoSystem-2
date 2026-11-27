<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jam kerja dihitung sampai detik. work_minutes tetap ada (pembulatan ke bawah, dipakai lembur dan ekspor),
 * work_seconds menyimpan angka tepat: selisih check-in dan check-out dikurangi istirahat shift.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('attendance_records', 'work_seconds')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unsignedInteger('work_seconds')->default(0)->after('work_minutes');
        });

        $breaks = DB::table('shifts')->pluck('break_minutes', 'id');

        DB::table('attendance_records')
            ->whereNotNull('check_in_at')->whereNotNull('check_out_at')
            ->select('id', 'shift_id', 'check_in_at', 'check_out_at')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($breaks) {
                foreach ($rows as $r) {
                    $gross = strtotime((string) $r->check_out_at) - strtotime((string) $r->check_in_at);
                    $seconds = max(0, $gross - ((int) ($breaks[$r->shift_id] ?? 0)) * 60);
                    DB::table('attendance_records')->where('id', $r->id)->update(['work_seconds' => $seconds]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_records', 'work_seconds')) {
            Schema::table('attendance_records', fn (Blueprint $t) => $t->dropColumn('work_seconds'));
        }
    }
};
