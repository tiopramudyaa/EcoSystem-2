<?php

namespace App\Models\Legal\SalesCompliance;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu deal yang harus lolos tinjauan compliance. Status: draft → in_review → approved | rejected; draft/in_review → cancelled.
 * Dokumen yang ditolak dibuka kembali (reopen) menjadi draft pada putaran berikutnya.
 */
class SalesComplianceRequest extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT     => 'Draft',
        self::STATUS_IN_REVIEW => 'In Review',
        self::STATUS_APPROVED  => 'Approved',
        self::STATUS_REJECTED  => 'Rejected',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $table = 'sales_compliance_requests';

    protected $guarded = [];

    protected $casts = [
        'has_meeting'        => 'boolean',
        'has_hospitality'    => 'boolean',
        'documents_received' => 'array',
        'final_price'        => 'decimal:2',
        'mrp_price'          => 'decimal:2',
        'contract_value'     => 'decimal:2',
        'contract_date'      => 'date',
        'round'              => 'integer',
        'current_step_order' => 'integer',
        'submitted_at'       => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function approvals()
    {
        return $this->hasMany(SalesComplianceApproval::class, 'request_id')->orderBy('round')->orderBy('order_seq');
    }

    public function answers()
    {
        return $this->hasMany(SalesComplianceAnswer::class, 'request_id');
    }

    public function attachments()
    {
        return $this->hasMany(SalesComplianceAttachment::class, 'request_id');
    }

    public function logs()
    {
        return $this->hasMany(SalesComplianceLog::class, 'request_id')->latest('id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_IN_REVIEW;
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Langkah yang sedang menunggu, pada putaran ini. */
    public function currentApproval(): ?SalesComplianceApproval
    {
        if (!$this->isOpen() || $this->current_step_order === null) {
            return null;
        }

        return $this->approvals->first(
            fn ($a) => (int) $a->round === (int) $this->round && (int) $a->order_seq === (int) $this->current_step_order
        );
    }

    /** Langkah-langkah putaran ini saja (putaran lama tetap tersimpan sebagai jejak). */
    public function roundApprovals()
    {
        return $this->approvals->where('round', $this->round)->values();
    }

    /** Selisih harga final terhadap MRP dalam persen (negatif = di bawah MRP); null bila belum lengkap. */
    public function priceVariancePct(): ?float
    {
        $mrp = (float) $this->mrp_price;

        return $this->final_price === null || $this->mrp_price === null || $mrp == 0.0
            ? null
            : round(((float) $this->final_price - $mrp) / $mrp * 100, 2);
    }
}
