<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class CustomerMandays extends Model
{
    use HasFactory, Auditable;

    protected static ?string $auditModule = 'Mandays';

    protected $table = 'customer_mandays';

    protected $fillable = [
        'ticket_id',
        'version',
        'description',
        'proposal_notes',
        'proposed_by_agent_id',
        'proposed_at',
        'submitted_to_customer_at',
        'sent_to_chat_at',
        'status',
        'current_step_order',
        'customer_notes',
        'rejection_reason',
        'notes',
        'canceled_by_id',
        'canceled_at',
        'total_mandays',
        'customer_response_at',
    ];

    protected $casts = [
        'proposed_at'              => 'datetime',
        'submitted_to_customer_at' => 'datetime',
        'sent_to_chat_at'          => 'datetime',
        'customer_response_at'     => 'datetime',
        'canceled_at'              => 'datetime',
        'total_mandays'            => 'decimal:2',
        'current_step_order'       => 'integer',
    ];

    public const STATUS_DRAFT            = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_PENDING_HELPDESK = 'pending_helpdesk'; // lolos 2 level, siap dikirim ke customer
    public const STATUS_SENT_TO_CHAT     = 'sent_to_chat';
    public const STATUS_APPROVED         = 'approved';
    public const STATUS_REJECTED         = 'rejected';         // ditolak di approval internal
    public const STATUS_CANCELED         = 'canceled';

    /** Snapshot approval internal (Level 1 & 2) milik versi ini. */
    public function approvals()
    {
        return $this->hasMany(CustomerMandaysApproval::class, 'customer_mandays_id')->orderBy('order_seq');
    }

    public function isInApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL && $this->current_step_order !== null;
    }

    /** Langkah yang sedang menunggu, atau null bila proposal tidak sedang di approval internal. */
    public function currentApproval(): ?CustomerMandaysApproval
    {
        if (!$this->isInApproval()) {
            return null;
        }

        return $this->approvals->firstWhere('order_seq', $this->current_step_order);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'ticket_id');
    }

    public function proposedByAgent()
    {
        return $this->belongsTo(Employee::class, 'proposed_by_agent_id', 'employee_id');
    }

    public function canceledBy()
    {
        return $this->belongsTo(Employee::class, 'canceled_by_id', 'employee_id');
    }

    public function details()
    {
        return $this->hasMany(CustomerMandaysDetail::class, 'customer_mandays_id');
    }

    public function calculateTotalMandays()
    {
        return $this->details()->sum('mandays');
    }

    // Status scopes
    public function scopeDraft($query)           { return $query->where('status', 'draft'); }
    public function scopePendingHelpdesk($query) { return $query->where('status', 'pending_helpdesk'); }
    public function scopeSentToChat($query)      { return $query->where('status', 'sent_to_chat'); }
    public function scopeApproved($query)        { return $query->where('status', 'approved'); }
    public function scopeCanceled($query)        { return $query->where('status', 'canceled'); }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    /** Pivot detail ke array Activity → Module → Mandays */
    public function getMatrix(): array
    {
        $matrix = [];
        foreach ($this->details as $d) {
            $act = $d->activity ?? 'General';
            $matrix[$act][$d->module] = (float) $d->mandays;
        }
        return $matrix;
    }
}
