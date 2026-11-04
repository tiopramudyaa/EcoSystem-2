<?php

namespace App\Models\Legal\SalesCompliance;

use Illuminate\Database\Eloquent\Model;

class SalesComplianceQuestion extends Model
{
    public const TYPES = ['radio', 'checkbox', 'text', 'textarea'];

    protected $table = 'sales_compliance_questions';

    protected $guarded = [];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
