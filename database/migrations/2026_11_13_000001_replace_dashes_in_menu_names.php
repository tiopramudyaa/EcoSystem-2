<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Samakan nama menu yang sudah tersimpan dengan aturan gaya "tanpa em dash/en dash".
 * Hanya kolom name yang diubah; slug (dipakai middleware dan izin) tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menu') || !Schema::hasColumn('menu', 'name')) {
            return;
        }

        DB::table('menu')
            ->where(function ($q) {
                $q->where('name', 'like', '%—%')->orWhere('name', 'like', '%–%');
            })
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($row) {
                $name = preg_replace('/\s+[—–]\s+/u', ' - ', $row->name);
                $name = str_replace(['—', '–'], '-', $name);
                DB::table('menu')->where('id', $row->id)->update(['name' => $name]);
            });
    }

    public function down(): void
    {
        // Tidak dapat dikembalikan: teks asli tidak disimpan.
    }
};
