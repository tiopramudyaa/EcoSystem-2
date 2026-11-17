<?php

namespace App\Models\Compliance;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Legal → Compliance: one "do" or "don't" rule of the Do & Don't page. */
class ComplianceRule extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    protected $fillable = ['category_id', 'type', 'title', 'description', 'severity', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function category()
    {
        return $this->belongsTo(ComplianceCategory::class, 'category_id');
    }
}
