<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak reminder "tiket sudah Closed tapi dokumen deliverable mandatory belum
 * lengkap" yang dikirim ke group chat Teams tiket lewat Power Automate.
 *
 * Tabel terpisah dari `ticket` dengan alasan yang sama seperti
 * ticket_open_reminders: tabel `ticket` dipakai bersama repo JARVIES.
 *
 * last_sent_at yang menjaga "sekali sehari": command-nya dijadwalkan tiap jam,
 * dan tiket yang sudah dikirimi hari ini dilewati sampai besok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_deliverable_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->unique();
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('first_sent_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index('last_sent_at');

            $table->foreign('ticket_id')
                  ->references('ticket_id')->on('ticket')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_deliverable_reminders');
    }
};
