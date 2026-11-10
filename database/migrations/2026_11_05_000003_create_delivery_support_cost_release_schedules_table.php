<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal bulanan Release per cost item Plan Cost Delivery Support — mirror
 * delivery_project_cost_release_schedules. Total release tetap di
 * delivery_support_costs.release_amount; baris di sini membaginya ke bulan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_support_cost_release_schedules')) {
            return;
        }

        Schema::create('delivery_support_cost_release_schedules', function (Blueprint $t) {
            $t->id();
            // Nama FK eksplisit: nama otomatis Laravel melebihi batas 64 karakter MySQL.
            $t->foreignId('delivery_support_cost_id')
              ->constrained('delivery_support_costs', 'id', 'dsc_release_schedules_cost_id_fk')
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
        Schema::dropIfExists('delivery_support_cost_release_schedules');
    }
};
