<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ConsultantMandaysDetail extends Model
{
    use HasFactory, Auditable;

    protected static ?string $auditModule = 'Mandays';

    protected $table = 'consultant_mandays_detail';

    protected $fillable = [
        'consultant_mandays_id',
        'employee_id',
        'module',
        'mandays',
        'approved_mandays',
        'additional_mandays',
        'approved_additional',
        'notes',
        'progress_percentage',
        'progress_note',
        'progress_updated_at',
        'progress_updated_by',
    ];

    protected $casts = [
        'mandays'              => 'decimal:2',
        'approved_mandays'     => 'decimal:2',
        'additional_mandays'   => 'decimal:2',
        'approved_additional'  => 'decimal:2',
        'progress_percentage'  => 'decimal:2',
        'progress_updated_at'  => 'datetime',
    ];

    public function consultantMandays()
    {
        return $this->belongsTo(ConsultantMandays::class, 'consultant_mandays_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * MD quota per employee per ticket, read straight from the approved columns
     * (approved_mandays + approved_additional) and NOT from the proposal's status.
     *
     * A proposal that goes back to draft / pending after an edit keeps the numbers
     * Head already approved, so the quota stays usable while it is being revised.
     * A row counts only once Head has approved something for that employee:
     * approved_mandays is not NULL, or approved_additional > 0.
     *
     * @param  iterable<int>  $ticketIds
     * @return array<string,float>  keyed "ticketId_employeeId"
     */
    public static function approvedQuotas(iterable $ticketIds, ?int $employeeId = null): array
    {
        $ticketIds = collect($ticketIds)->filter()->unique()->values();
        if ($ticketIds->isEmpty()) {
            return [];
        }

        $rows = static::query()
            ->join('consultant_mandays as cm', 'cm.id', '=', 'consultant_mandays_detail.consultant_mandays_id')
            ->whereIn('cm.ticket_id', $ticketIds)
            ->when($employeeId, fn ($q) => $q->where('consultant_mandays_detail.employee_id', $employeeId))
            ->where(function ($q) {
                $q->whereNotNull('consultant_mandays_detail.approved_mandays')
                  ->orWhere('consultant_mandays_detail.approved_additional', '>', 0);
            })
            // Most recently approved proposal first; the first row per key wins.
            ->orderByRaw('cm.approved_at IS NULL')
            ->orderByDesc('cm.approved_at')
            ->orderByDesc('cm.id')
            ->get([
                'cm.ticket_id as q_ticket_id',
                'consultant_mandays_detail.employee_id as q_employee_id',
                'consultant_mandays_detail.approved_mandays as q_days',
                'consultant_mandays_detail.approved_additional as q_add',
            ]);

        $quotas = [];
        foreach ($rows as $r) {
            $key = $r->q_ticket_id . '_' . $r->q_employee_id;
            if (!array_key_exists($key, $quotas)) {
                $quotas[$key] = round((float) ($r->q_days ?? 0) + (float) ($r->q_add ?? 0), 2);
            }
        }

        return $quotas;
    }
}
