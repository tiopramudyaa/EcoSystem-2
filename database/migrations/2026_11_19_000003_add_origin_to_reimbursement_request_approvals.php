<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai langkah persetujuan yang dibentuk dari Object (project, tiket,
 * cabang) dan membedakannya dari langkah Approval Workflow. Diperlukan supaya
 * langkah workflow yang ditambahkan kemudian ke dokumen berjalan mendapat nomor
 * urut yang benar: pada dokumen dengan konsep Object, nomor langkah workflow
 * digeser sebanyak langkah Object. Tambahan murni, bawaan 'workflow' sehingga
 * dokumen lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursement_request_approvals', function (Blueprint $table) {
            $table->string('origin', 10)->default('workflow')->after('signs_as'); // workflow | object
        });
    }

    public function down(): void
    {
        Schema::table('reimbursement_request_approvals', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
