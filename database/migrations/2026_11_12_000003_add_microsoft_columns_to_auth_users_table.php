<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login dengan Microsoft (Microsoft Entra ID / akun M365, termasuk yang dipakai
 * di Teams).
 *
 * microsoft_oid = object id akun di tenant (klaim `oid`). Inilah pengenal yang
 * dipercaya saat login berikutnya, BUKAN email: email bisa berganti, oid tidak.
 * microsoft_email hanya untuk ditampilkan di Settings > Security.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            $table->string('microsoft_oid', 64)->nullable()->unique();
            $table->string('microsoft_email')->nullable();
            $table->timestamp('microsoft_linked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('auth_users', function (Blueprint $table) {
            $table->dropUnique(['microsoft_oid']);
            $table->dropColumn(['microsoft_oid', 'microsoft_email', 'microsoft_linked_at']);
        });
    }
};
