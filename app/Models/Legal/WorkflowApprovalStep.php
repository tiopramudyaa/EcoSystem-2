<?php

namespace App\Models\Legal;

use App\Models\Employee;
use App\Models\EmployeeRole;
use Illuminate\Database\Eloquent\Model;

/** Langkah KONFIGURASI alur persetujuan (apa yang berlaku untuk dokumen baru). Salinan per dokumen: WorkflowApproval. */
abstract class WorkflowApprovalStep extends Model
{
    public const TYPE_ROLE     = 'role';
    public const TYPE_EMPLOYEE = 'employee';
    public const TYPES = [self::TYPE_ROLE, self::TYPE_EMPLOYEE];

    protected $guarded = [];

    protected $casts = [
        'order_seq'             => 'integer',
        'approver_role_id'      => 'integer',
        'approver_employee_ids' => 'array',
        'is_active'             => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'approver_role_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Ringkasan "menurut konfigurasi hari ini, langkah ini dipegang siapa". */
    public function approverLabel(): string
    {
        if ($this->approver_type === self::TYPE_EMPLOYEE) {
            $ids = array_map('intval', $this->approver_employee_ids ?? []);
            $names = Employee::with('basicData')->whereIn('employee_id', $ids)->get()
                ->map(fn ($e) => $e->fullNameLabel())->filter()->all();

            return $names ? implode(', ', $names) : count($ids) . ' selected employee(s)';
        }

        return $this->role?->name ?? ($this->approver_role_id ? 'Role #' . $this->approver_role_id : 'No role set');
    }
}
