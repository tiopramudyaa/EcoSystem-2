<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot dokumen deliverable yang dikirim ke customer bersama satu versi
 * proposal Customer Mandays (Send to Customer / Revise & Resend).
 */
class CustomerMandaysDocument extends Model
{
    protected $table = 'customer_mandays_documents';

    protected $fillable = [
        'customer_mandays_id',
        'ticket_deliverable_id',
        'doc_type',
        'file_name',
        'file_url',
    ];

    public function proposal()
    {
        return $this->belongsTo(CustomerMandays::class, 'customer_mandays_id');
    }
}
