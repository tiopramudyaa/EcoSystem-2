<?php

namespace App\Models\Legal\SalesCompliance;

use Illuminate\Database\Eloquent\Model;

class SalesComplianceAttachment extends Model
{
    public const TYPE_PRICE = 'price';
    public const TYPE_MRP   = 'mrp';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_PRICE => 'Agreed price document',
        self::TYPE_MRP   => 'MRP document',
        self::TYPE_OTHER => 'Other supporting document',
    ];

    protected $table = 'sales_compliance_attachments';

    protected $guarded = [];

    public function request()
    {
        return $this->belongsTo(SalesComplianceRequest::class, 'request_id');
    }
}
