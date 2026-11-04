<?php

namespace App\Models\Legal\VendorOnboarding;

use App\Models\Legal\WorkflowApproval;

class VendorApproval extends WorkflowApproval
{
    protected $table = 'vendor_approvals';

    public function registration()
    {
        return $this->belongsTo(VendorRegistration::class, 'registration_id', 'id');
    }
}
