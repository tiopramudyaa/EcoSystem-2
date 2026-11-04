<?php

use App\Support\MenuRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Impor kontrak yang sudah berlaku (hasil scan kontrak fisik) — HR & General → Contract.
 *
 *  employee_contract.origin        system (dibuat lewat formulir/template, bawaan) | imported (dicatat dari kontrak fisik)
 *  employee_contract.imported_at   kapan dicatat
 *  employee_contract.imported_by   siapa yang mencatat
 *  slug izin general.contracts.import  ("Import Existing Contract"): lahir aktif HANYA untuk EC Administrator
 *                                      (aturan baku MenuRegistrar); pemilik sistem membagikannya lewat Menu Access.
 *
 * Semua kolom baru boleh kosong / punya bawaan; kontrak yang sudah ada tidak berubah (origin = system). down() hanya melepas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_contract', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_contract', 'origin')) {
                $table->string('origin', 20)->default('system');
                $table->timestamp('imported_at')->nullable();
                $table->unsignedBigInteger('imported_by')->nullable();
            }
        });

        MenuRegistrar::register('general.contracts', ['general.contracts.import' => 'Import Existing Contract'], 12, 'function');
    }

    public function down(): void
    {
        MenuRegistrar::remove(['general.contracts.import']);

        Schema::table('employee_contract', function (Blueprint $table) {
            foreach (['origin', 'imported_at', 'imported_by'] as $col) {
                if (Schema::hasColumn('employee_contract', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
