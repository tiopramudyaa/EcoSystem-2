<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\PeriodAuditLog;
use App\Models\PeriodLateExceptionRequest;
use Carbon\Carbon;
use App\Models\ReportingPeriod;
use App\Services\PeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodManagementController extends Controller
{
    public function __construct(private PeriodService $svc) {}

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sessionUser(): array
    {
        return session('user', []);
    }

    private function actorId(): int
    {
        return (int) ($this->sessionUser()['id'] ?? 0);
    }

    private function actorRoleId(): int
    {
        return (int) ($this->sessionUser()['role']['id'] ?? 0);
    }

    // ── Permissions ───────────────────────────────────────────────────────────
    // Nothing here knows a role ID or name: what a person may do comes from the function slugs ticked for their role(s) in
    // Management → Roles (Menu Access → Period Management). The role ID is kept only to stamp the audit log.

    private const PERM_GLOBAL = 'rpmo.periods.manage-global';   // create / dates / delete / open+close globally / force-close
    private const PERM_FINAL  = 'rpmo.periods.approve-final';   // Level-2 late-access decision
    private const DOMAINS     = [PeriodService::DOMAIN_PROJECT, PeriodService::DOMAIN_SUPPORT];

    private ?Employee $viewer = null;

    private function can(string $slug): bool
    {
        $this->viewer ??= Employee::find($this->actorId());

        return $this->viewer?->hasPermission($slug) ?? false;
    }

    private function domainPerm(string $domain): string
    {
        return "rpmo.periods.manage-domain.{$domain}";
    }

    /** @return list<string> the domains this person may open / close and decide Level-1 requests for */
    private function managedDomains(): array
    {
        return array_values(array_filter(self::DOMAINS, fn (string $d) => $this->can($this->domainPerm($d))));
    }

    /**
     * The domain a domain action applies to: the one in the request, or the only one the person manages.
     * Returns a JsonResponse (422 / 403) when it cannot be settled.
     */
    private function actionDomain(Request $request): string|JsonResponse
    {
        $managed = $this->managedDomains();
        if ($managed === []) {
            return $this->unauthorized('You are not allowed to open or close a domain period.');
        }

        $domain = $request->input('domain', count($managed) === 1 ? $managed[0] : null);
        if (!in_array($domain, self::DOMAINS, true)) {
            return $this->invalid('Domain parameter must be "project" or "support".');
        }

        return in_array($domain, $managed, true)
            ? $domain
            : $this->unauthorized("You are not allowed to manage the {$domain} domain.");
    }

    private function unauthorized(string $msg = 'Unauthorized.'): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg], 403);
    }

    private function invalid(string $msg): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $msg], 422);
    }

    // ── Notification helpers ───────────────────────────────────────────────────

    /** Resolve the display name of the current actor. */
    private function actorName(): string
    {
        $actor = Employee::with('basicData')->where('employee_id', $this->actorId())->first();
        return $actor?->basicData?->full_name ?? "EMP#{$this->actorId()}";
    }

    /**
     * Create a notification record for one recipient.
     *
     * @param int    $toId      recipient employee_id
     * @param string $type      notification type constant
     * @param string $fromName  sender display name
     * @param string $preview   short message body
     * @param string $link      URL the user is taken to on click
     */
    private function notify(int $toId, string $type, string $fromName, string $preview, string $link): void
    {
        Notification::create([
            'employee_id'      => $toId,
            'type'             => $type,
            'from_employee_id' => $this->actorId(),
            'from_name'        => $fromName,
            'preview'          => $preview,
            'link'             => $link,
            'is_read'          => false,
        ]);
    }

    /** Notify every employee whose role holds the given permission slug. */
    private function notifyHolders(string $slug, string $type, string $fromName, string $preview, string $link): void
    {
        Employee::withMenuPermission($slug)
            ->pluck('employee_id')
            ->each(fn ($id) => $this->notify($id, $type, $fromName, $preview, $link));
    }

    // ── Web view ──────────────────────────────────────────────────────────────

    public function index()
    {
        // The page itself is gated by the `rpmo.periods` menu (route middleware); the rest of the screen follows the slugs below.
        $periods = ReportingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();
        $active  = ReportingPeriod::getActive();

        // Period created but not yet opened globally (waiting for someone with global control to open)
        $pending = ReportingPeriod::where('global_status', 'not_open')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        $managedDomains  = $this->managedDomains();
        $canManageGlobal = $this->can(self::PERM_GLOBAL);
        $canApproveFinal = $this->can(self::PERM_FINAL);

        // Employees a late exception can be raised for: members of roles held to the domain(s) this person manages.
        $domainEmployees = collect();
        if ($managedDomains !== []) {
            $domainEmployees = Employee::whereHas('roles', fn ($q) => $q->whereIn('employee_role.period_domain', $managedDomains))
                ->with('basicData')
                ->orderBy('employee_id')
                ->get(['employee_id']);
        }

        return view('rpmo.periods.index', compact('periods', 'active', 'pending', 'managedDomains', 'canManageGlobal', 'canApproveFinal', 'domainEmployees'));
    }

    // ── API: Create period (needs manage-global) ─────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        if (!$this->can(self::PERM_GLOBAL)) {
            return $this->unauthorized('You are not allowed to create periods.');
        }

        $data = $request->validate([
            'year'       => 'required|integer|min:2020|max:2100',
            'month'      => 'required|integer|min:1|max:12',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        try {
            $period = $this->svc->createPeriod(
                $data['year'],
                $data['month'],
                $data['start_date'],
                $data['end_date'],
                $this->actorId(),
                $this->actorRoleId()
            );
            return response()->json([
                'success' => true,
                'message' => 'Period created successfully.',
                'data'    => $period,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Open global (needs manage-global) ──────────────────────────────────────

    public function openGlobal(ReportingPeriod $period): JsonResponse
    {
        if (!$this->can(self::PERM_GLOBAL)) {
            return $this->unauthorized('You are not allowed to open periods globally.');
        }

        try {
            $this->svc->openGlobal($period, $this->actorId(), $this->actorRoleId());
            return response()->json([
                'success' => true,
                'message' => "Period {$period->getLabel()} opened globally.",
                'data'    => $period->fresh(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Close global (needs manage-global) ─────────────────────────────────────

    public function closeGlobal(ReportingPeriod $period): JsonResponse
    {
        if (!$this->can(self::PERM_GLOBAL)) {
            return $this->unauthorized('You are not allowed to close periods globally.');
        }

        try {
            $this->svc->closeGlobal($period, $this->actorId(), $this->actorRoleId());
            return response()->json([
                'success' => true,
                'message' => "Period {$period->getLabel()} closed globally.",
                'data'    => $period->fresh(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Force close domain (needs manage-global) ───────────────────────────────

    public function forceCloseDomain(Request $request, ReportingPeriod $period): JsonResponse
    {
        if (!$this->can(self::PERM_GLOBAL)) {
            return $this->unauthorized('You are not allowed to force-close domains.');
        }

        $data = $request->validate(['domain' => 'required|in:project,support']);

        try {
            $this->svc->forceCloseDomain($period, $data['domain'], $this->actorId(), $this->actorRoleId());
            return response()->json([
                'success' => true,
                'message' => ucfirst($data['domain']) . " domain force-closed for {$period->getLabel()}.",
                'data'    => $period->fresh(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Open domain (needs manage-domain for that domain) ──────────────────────

    public function openDomain(Request $request, ReportingPeriod $period): JsonResponse
    {
        $roleId = $this->actorRoleId();
        $domain = $this->actionDomain($request);
        if ($domain instanceof JsonResponse) {
            return $domain;
        }

        try {
            $this->svc->openDomain($period, $domain, $this->actorId(), $roleId);
            return response()->json([
                'success' => true,
                'message' => ucfirst($domain) . " domain opened for {$period->getLabel()}.",
                'data'    => $period->fresh(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Close domain (needs manage-domain for that domain) ─────────────────────

    public function closeDomain(Request $request, ReportingPeriod $period): JsonResponse
    {
        $roleId = $this->actorRoleId();
        $domain = $this->actionDomain($request);
        if ($domain instanceof JsonResponse) {
            return $domain;
        }

        try {
            $this->svc->closeDomain($period, $domain, $this->actorId(), $roleId);
            return response()->json([
                'success' => true,
                'message' => ucfirst($domain) . " domain closed for {$period->getLabel()}.",
                'data'    => $period->fresh(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Audit logs ───────────────────────────────────────────────────────

    public function auditLogs(ReportingPeriod $period): JsonResponse
    {
        // Same rule as the page: this route has no menu middleware of its own.
        if (!Employee::find($this->actorId())?->canAccessMenu('rpmo.periods')) {
            return $this->unauthorized();
        }

        $logs = PeriodAuditLog::where('period_id', $period->id)
            ->with(['actor.basicData'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($log) => [
                'id'           => $log->id,
                'action'       => $log->action,
                'action_label' => $log->getActionLabel(),
                'action_color' => $log->getActionColor(),
                'is_force'     => $log->is_force,
                'actor_name'   => $log->actor?->basicData?->full_name ?? 'System',
                'actor_role'   => $log->actor_role_id,
                'metadata'     => $log->metadata,
                'created_at'   => $log->created_at?->format('d M Y, H:i'),
            ]);

        return response()->json(['success' => true, 'data' => $logs]);
    }

    // ── Legacy grant/revoke endpoints removed ─────────────────────────────────
    // All late exception access is now request-based (2-level approval).
    // Use POST /api/periods/exception-requests to submit a request.

    // ── API: Update period dates (needs manage-global) ───────────────────────────────

    public function updateDates(Request $request, ReportingPeriod $period): JsonResponse
    {
        if (!$this->can(self::PERM_GLOBAL)) {
            return $this->unauthorized('You are not allowed to edit period dates.');
        }

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        if (ReportingPeriod::hasOverlap($data['start_date'], $data['end_date'], $period->id)) {
            return $this->invalid('Period dates overlap with an existing period. Please choose a non-overlapping range.');
        }

        $period->start_date = $data['start_date'];
        $period->end_date   = $data['end_date'];
        $period->save();

        PeriodAuditLog::create([
            'period_id'     => $period->id,
            'action'        => 'date_updated',
            'actor_id'      => $this->actorId(),
            'actor_role_id' => $this->actorRoleId(),
            'is_force'      => false,
            'metadata'      => ['start_date' => $data['start_date'], 'end_date' => $data['end_date']],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Period {$period->getLabel()} dates updated.",
            'data'    => $period->fresh(),
        ]);
    }

    // ── API: Delete period (needs manage-global) ─────────────────────────────────────

    public function destroy(ReportingPeriod $period): JsonResponse
    {
        if (!$this->can(self::PERM_GLOBAL)) {
            return $this->unauthorized('You are not allowed to delete periods.');
        }

        $label = $period->getLabel();

        try {
            // Remove related records before deleting the period itself
            $period->auditLogs()->delete();
            $period->lateExceptions()->delete();
            $period->lateExceptionRequests()->delete();
            $period->delete();
        } catch (\Exception $e) {
            return $this->invalid('Cannot delete period: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => "Period {$label} has been deleted."]);
    }

    // ── API: Active period (used by other pages) ──────────────────────────────

    public function activePeriod(): JsonResponse
    {
        $period = ReportingPeriod::getActive();
        return response()->json(['success' => true, 'data' => $period]);
    }

    // ── API: Closed periods employee can request late access for ─────────────

    public function closedPeriods(): JsonResponse
    {
        $periods = ReportingPeriod::where('global_status', 'closed')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'label'      => $p->getLabel(),
                'start_date' => $p->start_date?->format('d M Y'),
                'end_date'   => $p->end_date?->format('d M Y'),
            ]);

        return response()->json(['success' => true, 'data' => $periods]);
    }

    // ── API: Employee submits late exception request ───────────────────────────

    public function createExceptionRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period_id' => 'required|integer|exists:reporting_periods,id',
            'notes'     => 'required|string|max:1000',
        ]);

        $employeeId = $this->actorId();
        if (!$employeeId) {
            return $this->unauthorized();
        }

        $domain = $this->svc->getDomainForEmployee($employeeId);
        if ($domain === null) {
            return $this->invalid('Your role does not require late exception access.');
        }

        try {
            $req = $this->svc->createLateExceptionRequest(
                (int) $data['period_id'],
                $employeeId,
                $domain,
                $data['notes']
            );

            // ── Notify domain heads ───────────────────────────────────────────
            $period      = $req->period ?? ReportingPeriod::find($data['period_id']);
            $periodLabel = $period?->getLabel() ?? "Period #{$data['period_id']}";
            $senderName  = $this->actorName();
            $domainLabel = ucfirst($domain);

            $this->notifyHolders(
                $this->domainPerm($domain),
                'late_exception_submitted',
                $senderName,
                "{$senderName} submitted a late access request for {$periodLabel} ({$domainLabel} domain). Notes: {$data['notes']}",
                '/rpmo/periods'
            );

            return response()->json([
                'success' => true,
                'message' => 'Late access request submitted. Waiting for Head approval.',
                'data'    => $this->formatRequest($req),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: Employee views own exception requests ────────────────────────────

    public function myExceptionRequests(): JsonResponse
    {
        $employeeId = $this->actorId();
        if (!$employeeId) {
            return $this->unauthorized();
        }

        $requests = PeriodLateExceptionRequest::where('employee_id', $employeeId)
            ->with(['period', 'head.basicData', 'rpmo.basicData', 'rejectedBy.basicData'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => $this->formatRequest($r));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    // ── API: Head/RPMO lists pending requests ─────────────────────────────────

    public function listExceptionRequests(Request $request): JsonResponse
    {
        $domains = $this->managedDomains();
        $final   = $this->can(self::PERM_FINAL);

        if ($domains === [] && !$final) {
            return $this->unauthorized();
        }

        $query = PeriodLateExceptionRequest::with([
            'period', 'employee.basicData', 'head.basicData', 'rpmo.basicData', 'rejectedBy.basicData',
        ]);

        // What waits on this person: Level-1 requests of the domains they manage, Level-2 requests if they decide those.
        $query->where(function ($q) use ($domains, $final) {
            if ($domains !== []) {
                $q->orWhere(fn ($r) => $r->whereIn('domain', $domains)->where('status', 'pending_head'));
            }
            if ($final) {
                $q->orWhere('status', 'pending_rpmo');
            }
        });

        $requests = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => $this->formatRequest($r));

        return response()->json(['success' => true, 'data' => $requests]);
    }

    // ── API: Head approve/reject (Level 1) ────────────────────────────────────

    public function headDecideRequest(Request $request, PeriodLateExceptionRequest $exRequest): JsonResponse
    {
        $roleId = $this->actorRoleId();

        // Level 1 belongs to whoever manages the request's own domain.
        if (!$this->can($this->domainPerm((string) $exRequest->domain))) {
            return $this->unauthorized('You are not allowed to decide requests of the ' . $exRequest->domain . ' domain.');
        }

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'notes'    => 'required|string|max:1000',
        ]);

        try {
            $exRequest->load(['period', 'employee.basicData']);
            $periodLabel = $exRequest->period?->getLabel() ?? "Period #{$exRequest->period_id}";
            $empId       = $exRequest->employee_id;
            $empName     = $exRequest->employee?->basicData?->full_name ?? "EMP#{$empId}";
            $headName    = $this->actorName();

            if ($data['decision'] === 'approve') {
                $this->svc->headApproveLateRequest($exRequest, $this->actorId(), $roleId, $data['notes']);
                $msg = 'Request approved. Waiting for RPMO approval.';

                // Notify the employee
                $this->notify(
                    $empId,
                    'late_exception_head_approved',
                    $headName,
                    "Your late access request for {$periodLabel} was approved by {$headName}. Waiting for RPMO approval.",
                    '/calendar/timesheets'
                );

                // Notify everyone who decides at Level 2
                $this->notifyHolders(
                    self::PERM_FINAL,
                    'late_exception_pending_rpmo',
                    $headName,
                    "{$empName}'s late access request for {$periodLabel} was approved by Head {$headName}. Your review is needed.",
                    '/rpmo/periods'
                );
            } else {
                $this->svc->headRejectLateRequest($exRequest, $this->actorId(), $roleId, $data['notes']);
                $msg = 'Request rejected.';

                // Notify the employee
                $this->notify(
                    $empId,
                    'late_exception_head_rejected',
                    $headName,
                    "Your late access request for {$periodLabel} was rejected by {$headName}. Reason: {$data['notes']}",
                    '/calendar/timesheets'
                );
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $this->formatRequest($exRequest->fresh()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── API: RPMO approve/reject (Level 2) ───────────────────────────────────

    public function rpmoDecideRequest(Request $request, PeriodLateExceptionRequest $exRequest): JsonResponse
    {
        $roleId = $this->actorRoleId();

        if (!$this->can(self::PERM_FINAL)) {
            return $this->unauthorized('You are not allowed to give final approval.');
        }

        $data = $request->validate([
            'decision'   => 'required|in:approve,reject',
            'notes'      => 'required|string|max:1000',
            // expires_at is required when approving
            'expires_at' => 'required_if:decision,approve|nullable|date|after:now',
        ]);

        try {
            $exRequest->load(['period', 'employee.basicData']);
            $periodLabel = $exRequest->period?->getLabel() ?? "Period #{$exRequest->period_id}";
            $empId       = $exRequest->employee_id;
            $rpmoName    = $this->actorName();

            if ($data['decision'] === 'approve') {
                // Parse the local-time string with the app timezone so the stored value and display both reflect what the user picked
                $expiresAt = Carbon::parse($data['expires_at'], config('app.timezone'));
                $this->svc->rpmoApproveLateRequest(
                    $exRequest,
                    $this->actorId(),
                    $roleId,
                    $expiresAt,
                    $data['notes']
                );
                $msg = 'Late access approved. Access is active until ' . $expiresAt->format('d M Y, H:i') . '.';

                // Notify the employee
                $this->notify(
                    $empId,
                    'late_exception_approved',
                    $rpmoName,
                    "Your late access request for {$periodLabel} has been approved! Access is active until {$expiresAt->format('d M Y, H:i')}.",
                    '/calendar/timesheets'
                );
            } else {
                $this->svc->rpmoRejectLateRequest($exRequest, $this->actorId(), $roleId, $data['notes']);
                $msg = 'Request rejected.';

                // Notify the employee
                $this->notify(
                    $empId,
                    'late_exception_rejected',
                    $rpmoName,
                    "Your late access request for {$periodLabel} was rejected by RPMO {$rpmoName}. Reason: {$data['notes']}",
                    '/calendar/timesheets'
                );
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $this->formatRequest($exRequest->fresh()),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->invalid($e->getMessage());
        }
    }

    // ── Private: format a request record for API response ────────────────────

    private function formatRequest(PeriodLateExceptionRequest $r): array
    {
        return [
            'id'               => $r->id,
            'period_id'        => $r->period_id,
            'period_label'     => $r->period?->getLabel(),
            'period_start'     => $r->period?->start_date?->format('d M Y'),
            'period_end'       => $r->period?->end_date?->format('d M Y'),
            'employee_id'      => $r->employee_id,
            'employee_name'    => $r->employee?->basicData?->full_name ?? "EMP#{$r->employee_id}",
            'domain'           => $r->domain,
            'notes'            => $r->notes,
            'status'           => $r->status,
            'status_label'     => $r->getStatusLabel(),
            'status_color'     => $r->getStatusColor(),
            'is_access_active' => $r->isAccessActive(),
            'is_expired'       => $r->isExpired(),
            'expires_at'       => $r->expires_at?->setTimezone(config('app.timezone'))->format('d M Y, H:i'),
            'expires_at_iso'   => $r->expires_at?->setTimezone(config('app.timezone'))->toIso8601String(),
            'head_name'        => $r->head?->basicData?->full_name,
            'head_approved_at' => $r->head_approved_at?->format('d M Y, H:i'),
            'head_notes'       => $r->head_notes,
            'rpmo_name'        => $r->rpmo?->basicData?->full_name,
            'rpmo_approved_at' => $r->rpmo_approved_at?->format('d M Y, H:i'),
            'rpmo_notes'       => $r->rpmo_notes,
            'rejected_by'      => $r->rejectedBy?->basicData?->full_name,
            'rejected_at'      => $r->rejected_at?->format('d M Y, H:i'),
            'rejection_notes'  => $r->rejection_notes,
            'created_at'       => $r->created_at?->format('d M Y, H:i'),
        ];
    }
}
