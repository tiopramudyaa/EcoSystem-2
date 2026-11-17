<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legal Basis: PEMILIK aturan (orang yang bertanggung jawab menjaga aturan itu tetap mutakhir).
 *
 *  legal_basis_register.owner_employee_id   karyawan penanggung jawab; boleh kosong. Pemilik menerima pengingat di lonceng notifikasi
 *                                           saat ulasan 12 bulan hampir jatuh tempo atau terlambat (perintah legal-basis:review-reminders).
 *
 * Hanya menambah satu kolom yang boleh kosong; data lama tidak berubah. down() hanya melepas kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('legal_basis_register') && !Schema::hasColumn('legal_basis_register', 'owner_employee_id')) {
            Schema::table('legal_basis_register', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_employee_id')->nullable()->after('reviewed_at')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('legal_basis_register', 'owner_employee_id')) {
            Schema::table('legal_basis_register', function (Blueprint $table) {
                $table->dropIndex(['owner_employee_id']);
                $table->dropColumn('owner_employee_id');
            });
        }
    }
};
