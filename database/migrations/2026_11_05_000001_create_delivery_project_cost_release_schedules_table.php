<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal bulanan Release per cost item Plan Cost (report Monthly Cost vs TOP
 * Invoiced). Total release tetap di delivery_project_costs.release_amount;
 * baris di sini membaginya ke bulan. Selisih total − terjadwal = "Belum
 * dijadwalkan" (boleh minus bila jadwal melebihi total).
 *
 * schedule_date disimpan utuh dari kalender; period ("YYYY-MM") diturunkan
 * darinya supaya pengelompokan per bulan tidak perlu konversi tanggal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_project_cost_release_schedules')) {
            return;
        }

        Schema::create('delivery_project_cost_release_schedules', function (Blueprint $t) {
            $t->id();
            // Nama FK eksplisit: nama otomatis Laravel melebihi batas 64 karakter MySQL.
            $t->foreignId('delivery_project_cost_id')
              ->constrained('delivery_project_costs', 'id', 'dpc_release_schedules_cost_id_fk')
              ->onDelete('cascade');
            $t->date('schedule_date');
            $t->string('period', 7)->index();
            $t->decimal('amount', 20, 2);
            $t->string('notes', 255)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_project_cost_release_schedules');
    }
};
