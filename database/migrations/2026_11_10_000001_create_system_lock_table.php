<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row switch that, while on, stops everyone except the protected
 * administrator account from signing in or using the system (used during
 * deployments and maintenance windows). See App\Services\SystemLockService.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_lock')) {
            return;
        }

        Schema::create('system_lock', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('is_locked')->default(false);
            $table->string('message', 255)->nullable();
            $table->string('locked_by', 150)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        \Illuminate\Support\Facades\DB::table('system_lock')->insert([
            'id'         => 1,
            'is_locked'  => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_lock');
    }
};
