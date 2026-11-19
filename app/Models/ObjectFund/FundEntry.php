<?php

namespace App\Models\ObjectFund;

use Illuminate\Database\Eloquent\Model;

/** Satu pemakaian dana oleh satu baris dokumen. `committed` mengurangi sisa; `voided` mengembalikannya. */
class FundEntry extends Model
{
    protected $table = 'object_fund_entries';

    protected $fillable = [
        'pool_id', 'document_type', 'document_id', 'item_id', 'amount', 'status',
        'over_allocation', 'created_by', 'voided_at', 'voided_by', 'void_reason',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'over_allocation' => 'boolean',
        'voided_at'       => 'datetime',
    ];

    public const STATUS_COMMITTED = 'committed';
    public const STATUS_VOIDED    = 'voided';

    public function pool()
    {
        return $this->belongsTo(FundPool::class, 'pool_id');
    }
}
