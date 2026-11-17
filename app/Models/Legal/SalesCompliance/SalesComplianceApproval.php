<?php

namespace App\Models\Legal\SalesCompliance;

use App\Models\Legal\WorkflowApproval;

class SalesComplianceApproval extends WorkflowApproval
{
    protected $table = 'sales_compliance_approvals';

    public function request()
    {
        return $this->belongsTo(SalesComplianceRequest::class, 'request_id', 'id');
    }
}
