<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "History" tab on a record: who changed what and when, from the audit trail (AuditLog).
 *
 * Only the record types listed in TYPES are exposed, each behind the menu that already lets the person open that record.
 * The tab covers the record and the child tables listed under `children` (class => foreign key). Child tables holding pay,
 * bank or ID data are deliberately left out, so the tab never shows more than the page's own sections do.
 */
class RecordHistoryController extends Controller
{
    private const TYPES = [
        'employee' => [
            'model'    => \App\Models\Employee::class,
            'menu'     => 'master.employee',
            'children' => [
                \App\Models\EmployeeBasicData::class    => 'employee_id',
                \App\Models\EmployeeAddress::class      => 'employee_id',
                \App\Models\EmployeeEducation::class    => 'employee_id',
                \App\Models\EmployeeQualification::class => 'employee_id',
                \App\Models\EmployeeEngagement::class   => 'employee_id',
            ],
        ],
        'project' => [
            'model'    => \App\Models\DeliveryProject::class,
            'menu'     => 'delivery.project',
            'children' => [
                \App\Models\DeliveryProjectIssue::class => 'delivery_projects_id',
                \App\Models\DeliveryProjectRisk::class  => 'delivery_projects_id',
                \App\Models\DeliveryProjectUpdate::class => 'delivery_projects_id',
            ],
        ],
        'ticket' => [
            'model'    => \App\Models\Ticket::class,
            'menu'     => 'tickets.inbox',
            'children' => [
                \App\Models\TicketMember::class      => 'ticket_id',
                \App\Models\TicketDeliverable::class => 'ticket_id',
            ],
        ],
    ];

    public function __invoke(Request $request, string $type, int $id)
    {
        $cfg = self::TYPES[$type] ?? null;
        abort_if($cfg === null, 404);

        $viewer = Employee::find(session('user.id'));
        abort_unless($viewer && $viewer->canAccessMenu($cfg['menu']), 403);

        $record = $cfg['model']::findOrFail($id);

        $logs = AuditLog::query()
            ->where(function ($q) use ($cfg, $record) {
                $q->where(fn ($r) => $r->where('auditable_type', class_basename($cfg['model']))->where('auditable_id', $record->getKey()));
                foreach ($cfg['children'] as $child => $fk) {
                    $ids = $child::query()->where($fk, $record->getKey())->pluck((new $child)->getKeyName());
                    if ($ids->isNotEmpty()) {
                        $q->orWhere(fn ($r) => $r->where('auditable_type', class_basename($child))->whereIn('auditable_id', $ids));
                    }
                }
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $logs->map(fn (AuditLog $l) => [
                'when'    => $l->created_at?->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                'who'     => $l->actor_name ?: 'System',
                'event'   => $l->getEventLabel(),
                'color'   => $l->getEventColor(),
                'what'    => $l->record_label,
                'section' => Str::headline(Str::after(class_basename($l->auditable_type), class_basename($cfg['model']))) ?: 'General',
                'changes' => $this->changes($l),
            ])->values(),
            'truncated' => $logs->count() === 100,
        ]);
    }

    /** @return list<array{field:string,from:?string,to:?string}> the fields that changed (all fields for a create / delete). */
    private function changes(AuditLog $log): array
    {
        $old = (array) $log->old_values;
        $new = (array) $log->new_values;
        $rows = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            if (in_array($field, ['updated_at', 'created_at', 'deleted_at'], true)) {
                continue;
            }
            $from = $old[$field] ?? null;
            $to = $new[$field] ?? null;
            if ($log->event === 'updated' && $from == $to) {
                continue;
            }
            $rows[] = ['field' => Str::headline($field), 'from' => $this->show($from), 'to' => $this->show($to)];
        }

        return $rows;
    }

    private function show(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 120);
    }
}
