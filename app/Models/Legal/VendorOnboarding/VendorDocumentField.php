<?php

namespace App\Models\Legal\VendorOnboarding;

use Illuminate\Database\Eloquent\Model;

/** Jenis dokumen yang diminta dari vendor (konfigurasi). */
class VendorDocumentField extends Model
{
    protected $table = 'vendor_document_fields';

    protected $guarded = [];

    protected $casts = ['is_required' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Jenis yang berlaku untuk tipe vendor itu: yang khusus tipenya ditambah yang berlaku untuk semua. */
    public function scopeForType($query, string $vendorType)
    {
        return $query->whereIn('vendor_type', ['all', $vendorType]);
    }
}
