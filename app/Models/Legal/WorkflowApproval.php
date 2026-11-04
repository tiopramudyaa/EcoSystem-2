<?php

namespace App\Models\Legal;

use App\Models\Employee;
use App\Models\EmployeeRole;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu langkah persetujuan pada SATU dokumen (Sales Compliance atau Vendor Onboarding).
 *
 * Barisnya salinan dari langkah konfigurasi saat dokumen diajukan, jadi mengubah konfigurasi tidak pernah mengubah dokumen
 * yang sedang berjalan. Bentuk kolomnya sama dengan snapshot Purchase Request — ApprovalEngine hanya mengandalkan bentuk itu.
 */
abstract class WorkflowApproval extends Model
{
    public const STATUS_WAITING  = 'waiting';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SKIPPED  = 'skipped';

    protected $guarded = [];

    protected $casts = [
        'round'                 => 'integer',
        'order_seq'             => 'integer',
        'approver_role_id'      => 'integer',
        'approver_employee_ids' => 'array',
        'chosen_by_requester'   => 'boolean',
        'acted_at'              => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'approver_role_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(Employee::class, 'acted_by', 'employee_id');
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Hanya memeriksa DEFINISI langkah. Apakah langkah ini yang sedang menunggu, dan larangan menyetujui dokumen sendiri,
     * diputuskan service-nya karena keduanya menyangkut keadaan dokumen.
     *
     * @param  array<int>  $roleIds  role yang dipegang karyawan itu
     */
    public function allows(int $employeeId, array $roleIds): bool
    {
        return match ($this->approver_type) {
            'role'     => $this->approver_role_id !== null && in_array((int) $this->approver_role_id, $roleIds, true),
            'employee' => in_array($employeeId, array_map('intval', $this->approver_employee_ids ?? []), true),
            default    => false,
        };
    }

    public function approverLabel(): string
    {
        return match ($this->approver_type) {
            'role'     => $this->role?->name ?? 'Role #' . $this->approver_role_id,
            'employee' => count($this->approver_employee_ids ?? []) . ' selected employee(s)',
            default    => (string) $this->approver_type,
        };
    }
}
