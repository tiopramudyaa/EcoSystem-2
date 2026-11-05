<?php

namespace App\Models\Compliance;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Legal → Compliance: a "critical rule" card on the portal home page. */
class ComplianceAlert extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    protected $fillable = ['icon', 'title', 'description', 'color_type', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
