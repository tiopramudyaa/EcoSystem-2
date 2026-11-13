<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SNAPSHOT + RIWAYAT satu level approval untuk satu versi Customer Mandays.
 *
 * Disalin dari CustomerMandaysApprovalStep saat PIC submit. Model ini hanya
 * memeriksa DEFINISI langkah (allows()); keadaan proposal (level aktif, status)
 * diperiksa di CustomerMandaysApprovalService::canAct().
 */
class CustomerMandaysApproval extends Model
{
    public const STATUS_WAITING  = 'waiting';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SKIPPED  = 'skipped';

    protected $table = 'customer_mandays_approvals';

    protected $fillable = [
        'customer_mandays_id',
        'order_seq',
        'step_name',
        'approver_type',
        'approver_role_id',
        'approver_employee_ids',
        'status',
        'acted_by',
        'acted_at',
        'notes',
    ];

    protected $casts = [
        'order_seq'             => 'integer',
        'approver_role_id'      => 'integer',
        'approver_employee_ids' => 'array',
        'acted_at'              => 'datetime',
    ];

    public function proposal()
    {
        return $this->belongsTo(CustomerMandays::class, 'customer_mandays_id');
    }

    public function actor()
    {
        return $this->belongsTo(Employee::class, 'acted_by', 'employee_id');
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'approver_role_id');
    }

    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /** Apakah karyawan ini termasuk penyetuju langkah ini (menurut definisinya saja). */
    public function allows(int $employeeId, array $roleIds): bool
    {
        return match ($this->approver_type) {
            CustomerMandaysApprovalStep::TYPE_ROLE
                => $this->approver_role_id !== null
                    && in_array((int) $this->approver_role_id, array_map('intval', $roleIds), true),
            CustomerMandaysApprovalStep::TYPE_EMPLOYEE
                => in_array($employeeId, array_map('intval', $this->approver_employee_ids ?? []), true),
            default => false,
        };
    }

    /** Label penyetuju untuk tampilan: nama role, nama orang, atau "N selected people". */
    public function approverLabel(): string
    {
        if ($this->approver_type === CustomerMandaysApprovalStep::TYPE_EMPLOYEE) {
            $ids = array_map('intval', $this->approver_employee_ids ?? []);
            if (count($ids) === 1) {
                $emp = Employee::with('basicData')->find($ids[0]);
                $name = trim(($emp?->basicData?->first_name ?? '') . ' ' . ($emp?->basicData?->last_name ?? ''));
                return $name !== '' ? $name : 'Selected person';
            }
            return count($ids) . ' selected people';
        }

        return $this->role?->name ?? 'Unknown position';
    }
}
