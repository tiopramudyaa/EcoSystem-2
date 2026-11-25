<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template pilihan kolom untuk tombol Export di tiap list (Ticket, Timesheet,
 * Delivery Project, Delivery Support, Employee). Milik pribadi satu karyawan:
 * `columns` berisi daftar nama kolom (sama dengan header di file Excel) yang
 * dipilih. Nama template unik per karyawan per jenis export.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('export_templates')) {
            return;
        }

        Schema::create('export_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('export_type', 40);
            $table->string('name', 80);
            $table->json('columns');
            $table->timestamps();

            $table->unique(['employee_id', 'export_type', 'name'], 'export_templates_owner_type_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_templates');
    }
};
