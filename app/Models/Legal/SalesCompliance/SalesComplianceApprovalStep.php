<?php

namespace App\Models\Legal\SalesCompliance;

use App\Models\Legal\WorkflowApprovalStep;

class SalesComplianceApprovalStep extends WorkflowApprovalStep
{
    public const MODULE = 'sales_compliance';

    protected $table = 'sales_compliance_approval_steps';
}
