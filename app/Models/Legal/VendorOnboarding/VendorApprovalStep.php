<?php

namespace App\Models\Legal\VendorOnboarding;

use App\Models\Legal\WorkflowApprovalStep;

class VendorApprovalStep extends WorkflowApprovalStep
{
    public const MODULE = 'vendor_onboarding';

    protected $table = 'vendor_approval_steps';
}
