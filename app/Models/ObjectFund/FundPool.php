<?php

namespace App\Models\ObjectFund;

use Illuminate\Database\Eloquent\Model;

/**
 * Kantong dana: alokasi untuk satu Object (office, project, atau tiket support)
 * pada satu kategori. Sisa dihitung oleh FundService, bukan disimpan di sini.
 *
 * Data biaya project (`delivery_project_costs`) sengaja tidak dipakai: nilai
 * aktualnya dihitung ulang otomatis sehingga tidak aman ditulis fitur lain.
 */
class FundPool extends Model
{
    protected $table = 'object_fund_pools';

    protected $fillable = [
        'object_type', 'ref_id', 'ref_label', 'category', 'period',
        'amount', 'is_active', 'status', 'proposed_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected $casts = [
        'ref_id'      => 'integer',
        'amount'      => 'decimal:2',
        'is_active'   => 'boolean',
        'approved_at' => 'datetime',
    ];

    public const TYPE_OFFICE  = 'office';
    public const TYPE_PROJECT = 'project';
    public const TYPE_SUPPORT = 'support';

    public const TYPES = [self::TYPE_OFFICE, self::TYPE_PROJECT, self::TYPE_SUPPORT];

    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_APPROVED = 'approved';

    public function entries()
    {
        return $this->hasMany(FundEntry::class, 'pool_id');
    }

    /** Hanya kantong yang disetujui dan aktif yang dipakai untuk memeriksa sisa. */
    public function scopeUsable($query)
    {
        return $query->where('is_active', true)->where('status', self::STATUS_APPROVED);
    }

    public function label(): string
    {
        return trim(($this->ref_label ?: ucfirst($this->object_type) . ' #' . $this->ref_id)
            . ($this->category !== '' ? ', ' . $this->category : ''));
    }
}
