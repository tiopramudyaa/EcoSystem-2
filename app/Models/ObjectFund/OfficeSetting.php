<?php

namespace App\Models\ObjectFund;

use App\Models\Attendance\Branch;
use Illuminate\Database\Eloquent\Model;

/** Pengaturan dana dan penanggung jawab satu cabang. Master cabang tidak diubah. */
class OfficeSetting extends Model
{
    protected $table = 'object_office_settings';

    protected $fillable = [
        'branch_id', 'fund_active', 'monthly_amount', 'approver_role_id', 'approver_employee_id',
    ];

    protected $casts = [
        'fund_active'    => 'boolean',
        'monthly_amount' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }
}
