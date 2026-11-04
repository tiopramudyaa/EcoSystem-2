<?php

namespace App\Models\Legal\VendorOnboarding;

use Illuminate\Database\Eloquent\Model;

class VendorQuestion extends Model
{
    public const TYPES = ['radio', 'checkbox', 'text', 'textarea'];

    protected $table = 'vendor_questions';

    protected $guarded = [];

    protected $casts = ['options' => 'array', 'is_required' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForType($query, string $vendorType)
    {
        return $query->whereIn('vendor_type', ['all', $vendorType]);
    }
}
