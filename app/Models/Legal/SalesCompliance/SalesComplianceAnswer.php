<?php

namespace App\Models\Legal\SalesCompliance;

use Illuminate\Database\Eloquent\Model;

class SalesComplianceAnswer extends Model
{
    protected $table = 'sales_compliance_answers';

    protected $guarded = [];

    public function question()
    {
        return $this->belongsTo(SalesComplianceQuestion::class, 'question_id');
    }
}
