<?php

namespace App\Models\Legal\VendorOnboarding;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu pendaftaran vendor. Status: draft → submitted → in_review → verified | rejected.
 * `submitted` = lengkap dan menunggu peninjau memulai tinjauan (langkah persetujuan baru dibuat saat tinjauan dimulai).
 * Setelah `verified`, `customer_id` menautkan ke Business Partner bertipe Vendor.
 */
class VendorRegistration extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_VERIFIED  = 'verified';
    public const STATUS_REJECTED  = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT     => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_IN_REVIEW => 'In Review',
        self::STATUS_VERIFIED  => 'Verified',
        self::STATUS_REJECTED  => 'Rejected',
    ];

    public const TYPE_B2B      = 'b2b';
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_LABELS   = [self::TYPE_B2B => 'Company (B2B)', self::TYPE_PERSONAL => 'Individual'];

    protected $table = 'vendor_registrations';

    protected $guarded = [];

    protected $casts = [
        'round'              => 'integer',
        'current_step_order' => 'integer',
        'submitted_at'       => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function documents()
    {
        return $this->hasMany(VendorDocument::class, 'registration_id')->orderBy('field_id')->orderBy('id');
    }

    public function answers()
    {
        return $this->hasMany(VendorAnswer::class, 'registration_id');
    }

    public function approvals()
    {
        return $this->hasMany(VendorApproval::class, 'registration_id')->orderBy('round')->orderBy('order_seq');
    }

    public function logs()
    {
        return $this->hasMany(VendorStatusLog::class, 'registration_id')->latest('id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_IN_REVIEW;
    }

    /** Data dan dokumen masih boleh diubah selama belum masuk tinjauan. */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true);
    }

    public function isPersonal(): bool
    {
        return $this->vendor_type === self::TYPE_PERSONAL;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function currentApproval(): ?VendorApproval
    {
        if (!$this->isOpen() || $this->current_step_order === null) {
            return null;
        }

        return $this->approvals->first(
            fn ($a) => (int) $a->round === (int) $this->round && (int) $a->order_seq === (int) $this->current_step_order
        );
    }

    public function roundApprovals()
    {
        return $this->approvals->where('round', $this->round)->values();
    }
}
