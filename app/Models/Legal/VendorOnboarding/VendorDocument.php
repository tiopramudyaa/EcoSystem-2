<?php

namespace App\Models\Legal\VendorOnboarding;

use Illuminate\Database\Eloquent\Model;

class VendorDocument extends Model
{
    protected $table = 'vendor_documents';

    protected $guarded = [];

    public function registration()
    {
        return $this->belongsTo(VendorRegistration::class, 'registration_id');
    }

    public function field()
    {
        return $this->belongsTo(VendorDocumentField::class, 'field_id');
    }
}
