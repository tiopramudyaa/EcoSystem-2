<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ticket_sla_events.event_at adalah kolom TIMESTAMP pertama di tabel, sehingga
 * MySQL/MariaDB (explicit_defaults_for_timestamp = OFF saat tabel dibuat) memberinya
 * "ON UPDATE CURRENT_TIMESTAMP". Akibatnya setiap UPDATE baris event (mis. syncPolicy
 * memperbarui catatan ticket_validated, atau promosi staging mengisi ticket_id di
 * email_received) ikut menggeser event_at ke waktu update. Di SLA Log, "Ticket Created"
 * jadi muncul paling bawah dengan tanggal hari policy diterapkan.
 *
 * 1. Hapus ON UPDATE dari event_at.
 * 2. Kembalikan event_at yang sudah tergeser:
 *    - ticket_validated  -> ticket_sla.first_responded_at (keduanya selalu diisi
 *      dari nilai yang sama di SlaService::attachToTicket).
 *    - email_received yang tercatat setelah tiket divalidasi -> waktu staging
 *      diterima, atau sla_start_at bila tidak ada staging.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // DEFAULT eksplisit mencegah MySQL/MariaDB menambahkan ON UPDATE lagi.
        DB::statement('ALTER TABLE ticket_sla_events MODIFY event_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        DB::statement("
            UPDATE ticket_sla_events e
            JOIN ticket_sla s ON s.ticket_id = e.ticket_id
            SET e.event_at = s.first_responded_at
            WHERE e.event_type = 'ticket_validated'
              AND s.first_responded_at IS NOT NULL
              AND e.event_at <> s.first_responded_at
        ");

        DB::statement("
            UPDATE ticket_sla_events e
            JOIN ticket_sla s ON s.ticket_id = e.ticket_id
            LEFT JOIN staging_tickets st ON st.id = e.staging_ticket_id
            SET e.event_at = COALESCE(st.created_at, s.sla_start_at)
            WHERE e.event_type = 'email_received'
              AND s.first_responded_at IS NOT NULL
              AND e.event_at > s.first_responded_at
              AND COALESCE(st.created_at, s.sla_start_at) < e.event_at
        ");
    }

    public function down(): void
    {
        // Tidak dikembalikan: ON UPDATE pada event_at adalah bug.
    }
};
