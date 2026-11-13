<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * KONFIGURASI satu level approval Customer Mandays (Management > Flow).
 *
 * Hanya berlaku untuk proposal yang disubmit SETELAH disimpan; proposal yang
 * sedang berjalan memegang salinannya sendiri di CustomerMandaysApproval.
 */
class CustomerMandaysApprovalStep extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Mandays';

    public const TYPE_ROLE     = 'role';
    public const TYPE_EMPLOYEE = 'employee';

    /** Jumlah level tetap (keputusan produk: tepat 2 level). */
    public const LEVELS = 2;

    protected $table = 'customer_mandays_approval_steps';

    protected $fillable = [
        'order_seq',
        'name',
        'approver_type',
        'approver_role_id',
        'approver_employee_ids',
    ];

    protected $casts = [
        'order_seq'             => 'integer',
        'approver_role_id'      => 'integer',
        'approver_employee_ids' => 'array',
    ];

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'approver_role_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_seq');
    }

    /** employee_id yang boleh bertindak pada langkah ini (untuk notifikasi & tampilan). */
    public function candidateEmployeeIds(): array
    {
        if ($this->approver_type === self::TYPE_EMPLOYEE) {
            return array_values(array_unique(array_map('intval', $this->approver_employee_ids ?? [])));
        }

        if ($this->approver_type === self::TYPE_ROLE && $this->approver_role_id) {
            return DB::table('employee_role_assignment')
                ->where('role_id', $this->approver_role_id)
                ->pluck('employee_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }
}
