<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class TicketDeliverable extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Ticket';

    protected $table = 'ticket_deliverables';

    protected $fillable = [
        'ticket_id',
        'doc_type',
        'description',
        'file_name',
        'onedrive_file_id',
        'onedrive_file_url',
        'status',
        'sent_at',
        'uploaded_by',
        'upload_date',
    ];

    protected $casts = [
        'upload_date' => 'date',
        'sent_at'     => 'datetime',
    ];

    /**
     * Doc types whose first send counts as the ticket's "First Resolution" in the
     * SLA Report (SlaService::firstResolutionDocAt).
     */
    public const FIRST_RESOLUTION_DOC_TYPES = ['IR', 'RCA'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function uploader()
    {
        return $this->belongsTo(Employee::class, 'uploaded_by', 'employee_id');
    }
}
