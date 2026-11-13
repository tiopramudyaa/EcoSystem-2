<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris per tiket: kapan reminder "dokumen deliverable belum lengkap"
 * terakhir dikirim ke group chat Teams tiket dan sudah berapa kali. Lihat
 * migration create_ticket_deliverable_reminders_table.
 */
class TicketDeliverableReminder extends Model
{
    protected $table = 'ticket_deliverable_reminders';

    protected $fillable = [
        'ticket_id',
        'sent_count',
        'first_sent_at',
        'last_sent_at',
    ];

    protected $casts = [
        'sent_count'    => 'integer',
        'first_sent_at' => 'datetime',
        'last_sent_at'  => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }
}
