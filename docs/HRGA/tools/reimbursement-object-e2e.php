<?php
// End-to-end check of the Reimbursement Object / fund flow. Runs ONLY on a scratch database named rb_scratch.
// Usage: create the database, then  DB_DATABASE=rb_scratch php artisan migrate --force
//        DB_DATABASE=rb_scratch php artisan tinker --execute="require (base_path('docs/HRGA/tools/reimbursement-object-e2e.php'));"
use App\Models\ObjectFund\FundEntry;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Reimbursement\ReimbursementApprovalStep;
use App\Services\ObjectFund\FundService;
use App\Services\Reimbursement\ReimbursementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

if (DB::getDatabaseName() !== 'rb_scratch') { echo "REFUSING: not the scratch database\n"; return; }

$ok = 0; $bad = 0;
$check = function (string $name, bool $cond, $extra = '') use (&$ok, &$bad) {
    $cond ? $ok++ : $bad++;
    echo ($cond ? 'PASS ' : 'FAIL ') . $name . ($cond ? '' : '  -> ' . (is_string($extra) ? $extra : json_encode($extra))) . "\n";
};

DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach (['reimbursement_items','reimbursement_request_approvals','reimbursement_requests','object_fund_entries','object_fund_pools','object_office_settings','delivery_projects','ticket','branches','employee_basic_data','employee','employee_hr_profile','customer'] as $t) {
    try { DB::table($t)->truncate(); } catch (\Throwable $e) { /* table name may differ */ }
}
DB::statement('SET FOREIGN_KEY_CHECKS=1');

$mk = function (int $id, string $eci, string $nick, int $active = 1) {
    DB::table('employee')->insert(['employee_id' => $id, 'eci' => $eci, 'is_active' => $active]);
    DB::table('employee_basic_data')->insert(['employee_id' => $id, 'nick_name' => $nick, 'first_name' => $nick]);
};
foreach ([[1,'E1','Budi'],[2,'E2','Sari'],[3,'E3','Rudi'],[4,'E4','Gita'],[5,'E5','Dewi'],[6,'E6','Finance'],[8,'E8','Cashier']] as $e) { $mk(...$e); }
$mk(7, 'E7', 'Nonaktif', 0);

Storage::disk('local')->put('rb-scratch/sig.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
$profileTable = (new \App\Models\EmployeeHrProfile())->getTable();
foreach ([2,3,4,5,6,8] as $id) { DB::table($profileTable)->insert(['employee_id' => $id, 'signature_path' => 'rb-scratch/sig.png']); }

DB::table('branches')->insert(['id' => 1, 'code' => 'JKT', 'name' => 'Jakarta', 'latitude' => 0, 'longitude' => 0, 'is_active' => 1]);
DB::statement('SET FOREIGN_KEY_CHECKS=0');
DB::table('delivery_projects')->insert(['id' => 1, 'name' => 'ERP Alpha', 'io_number' => 'IO-001', 'client_id' => 1, 'project_admin_id' => 2, 'project_manager_id' => 3, 'is_closed' => 0]);
DB::table('delivery_projects')->insert(['id' => 2, 'name' => 'No Admin', 'io_number' => 'IO-002', 'client_id' => 1, 'project_manager_id' => 3, 'is_closed' => 0]);
DB::table('delivery_projects')->insert(['id' => 3, 'name' => 'Closed', 'io_number' => 'IO-003', 'client_id' => 1, 'project_admin_id' => 2, 'project_manager_id' => 3, 'is_closed' => 1]);
DB::table('delivery_projects')->insert(['id' => 4, 'name' => 'Inactive Admin', 'io_number' => 'IO-004', 'client_id' => 1, 'project_admin_id' => 7, 'project_manager_id' => 3, 'is_closed' => 0]);
DB::table('ticket')->insert(['ticket_id' => 10, 'ticket_number' => 'T-0412', 'ticket_lead_id' => 4]);

ReimbursementApprovalStep::query()->delete();
ReimbursementApprovalStep::create(['module' => 'reimbursement', 'order_seq' => 1, 'name' => 'Finance Approval', 'approver_type' => 'employee', 'approver_employee_ids' => [6], 'signs_as' => 'approver', 'is_active' => 1]);

ObjectFlowSetting::query()->delete();
ObjectFlowSetting::create(['enabled' => true]); ObjectFlowSetting::forgetCache();

FundPool::create(['object_type' => 'project', 'ref_id' => 1, 'ref_label' => 'IO-001 - ERP Alpha', 'category' => 'Transport', 'amount' => 10000000]);
OfficeSetting::create(['branch_id' => 1, 'fund_active' => true, 'monthly_amount' => 1500000, 'approver_employee_id' => 5]);

$svc   = app(ReimbursementService::class);
$funds = app(FundService::class);
$today = now()->toDateString();
$item  = fn (array $o) => $o + ['description' => 'Taxi fare to client office', 'amount' => 1000000, 'receipt_no' => 'R1', 'receipt_date_from' => $today, 'receipt_date_to' => $today];
$doc   = fn (array $items, array $extra = []) => $extra + ['request_date' => $today, 'title' => 'Client visit', 'supporting_url' => 'https://drive.google.com/x', 'items' => $items];
$proj  = fn (float $amt, int $pid = 1, string $cat = 'Transport') => $item(['cost_center_type' => 'project', 'delivery_project_id' => $pid, 'fund_category' => $cat, 'amount' => $amt]);
$avail = fn () => $funds->balance(FundPool::where('object_type', 'project')->first())['available'];

// 1. Project submit
$r = $svc->submit(1, $doc([$proj(2500000)]));
$check('project submit allowed', $r['allowed'], $r['reason']);
$req = $r['request'];
$names = $req->approvals->sortBy('order_seq')->pluck('step_name')->values()->all();
$check('steps are Project Admin, Project Manager, Finance Approval', count($names) === 3 && str_starts_with($names[0], 'Project Admin') && str_starts_with($names[1], 'Project Manager') && $names[2] === 'Finance Approval', $names);
$check('object_flow flag set', $req->hasFlag('object_flow'));
$check('current step is 1', (int) $req->current_step_order === 1);
$check('charged label is the project', $req->charged_to_label === 'Project: IO-001 - ERP Alpha', $req->charged_to_label);
$check('fund unchanged after submit', $avail() == 10000000.0, $avail());

// 2. approvals in order, wrong person refused
$res = $svc->approve($req->fresh('approvals'), 3);
$check('manager cannot act before admin', !$res['allowed'], $res['reason']);
$res = $svc->approve($req->fresh('approvals'), 2);
$check('admin approves', $res['allowed'], $res['reason']);
$check('fund still unchanged mid-flow', $avail() == 10000000.0);
$res = $svc->approve($req->fresh('approvals'), 3);
$check('manager approves', $res['allowed'], $res['reason']);
$res = $svc->approve($req->fresh('approvals'), 6);
$check('final approval completes', $res['allowed'] && $res['completed'], $res);
$check('fund reduced on final approval', $avail() == 7500000.0, $avail());
$check('request approved', $req->fresh()->status === 'approved');

// 3. void by Finance returns the fund
$v = $svc->voidApproved($req->fresh(), 8, 'short');
$check('void needs a real reason', !$v['allowed'], $v['reason']);
$v = $svc->voidApproved($req->fresh(), 8, 'Duplicate receipt submitted twice');
$check('void allowed', $v['allowed'], $v['reason']);
$check('fund returned after void', $avail() == 10000000.0, $avail());
$check('voided document cannot be paid', !$svc->markPaid($req->fresh(), 8)['allowed']);
$check('second void refused', !$svc->voidApproved($req->fresh(), 8, 'Duplicate receipt submitted twice')['allowed']);
$check('status label is Void', $req->fresh()->statusLabel() === 'Void');

// 4. over allocation: warn needs a reason
$r = $svc->submit(1, $doc([$proj(12000000)]));
$check('warn policy: reason required', !$r['allowed'] && str_contains($r['reason'], 'at least 10 characters'), $r['reason']);
$r = $svc->submit(1, $doc([$proj(12000000)], ['over_allocation_reason' => 'Urgent client workshop travel']));
$check('warn policy: submits with reason', $r['allowed'], $r['reason']);
$check('reason stored', $r['request']->over_allocation_reason === 'Urgent client workshop travel');

// 5. block policy refuses with a clear message
ObjectFlowSetting::query()->update(['policy_project' => 'block']); ObjectFlowSetting::forgetCache();
$r = $svc->submit(1, $doc([$proj(12000000)]));
$check('block policy refuses', !$r['allowed'] && str_contains($r['reason'], 'exceeds the remaining fund'), $r['reason']);
ObjectFlowSetting::query()->update(['policy_project' => 'warn']); ObjectFlowSetting::forgetCache();

// 6. project edge cases
$r = $svc->submit(1, $doc([$proj(100000, 3)]));
$check('closed project refused', !$r['allowed'] && str_contains($r['reason'], 'closed'), $r['reason']);
$r = $svc->submit(1, $doc([$proj(100000, 4)]));
$check('inactive approver refused', !$r['allowed'] && str_contains($r['reason'], 'no longer active'), $r['reason']);
$r = $svc->submit(1, $doc([$proj(100000, 2)]));
$check('missing admin without fallback refused', !$r['allowed'] && str_contains($r['reason'], 'no fallback approver'), $r['reason']);
ObjectFlowSetting::query()->update(['project_fallback_employee_id' => 5]); ObjectFlowSetting::forgetCache();
$r = $svc->submit(1, $doc([$proj(100000, 2)]));
$check('missing admin uses fallback person', $r['allowed'], $r['reason']);
ObjectFlowSetting::query()->update(['project_fallback_employee_id' => null]); ObjectFlowSetting::forgetCache();

// 7. requester who is the approver is skipped
$r = $svc->submit(2, $doc([$proj(100000)]));
$names = $r['allowed'] ? $r['request']->approvals->sortBy('order_seq')->pluck('step_name')->values()->all() : [];
$check('requester as admin: admin step skipped', $r['allowed'] && !str_starts_with($names[0], 'Project Admin'), $names ?: $r['reason']);

// 8. mixed document: project + support + office, deduplicated
$r = $svc->submit(1, $doc([
    $proj(400000),
    $item(['cost_center_type' => 'support', 'ticket_id' => 10, 'fund_category' => 'Accommodation', 'amount' => 1200000]),
    $item(['cost_center_type' => 'branch', 'branch_id' => 1, 'amount' => 50000]),
]));
$check('mixed document submits', $r['allowed'], $r['reason']);
$names = $r['allowed'] ? $r['request']->approvals->sortBy('order_seq')->pluck('step_name')->values()->all() : [];
$check('mixed steps: admin, manager, ticket lead, branch approver, finance', count($names) === 5 && str_starts_with($names[2], 'Ticket Lead') && str_starts_with($names[3], 'Branch Approver'), $names);
$check('mixed label', $r['allowed'] && $r['request']->charged_to_label === 'Multiple objects', $r['request']->charged_to_label ?? '');

// 9. office fund (1.5M monthly): block when over
ObjectFlowSetting::query()->update(['policy_office' => 'block']); ObjectFlowSetting::forgetCache();
$r = $svc->submit(1, $doc([$item(['cost_center_type' => 'branch', 'branch_id' => 1, 'amount' => 2000000])]));
$check('office fund block', !$r['allowed'] && str_contains($r['reason'], 'exceeds the remaining fund'), $r['reason']);
OfficeSetting::where('branch_id', 1)->update(['fund_active' => false]);
$r = $svc->submit(1, $doc([$item(['cost_center_type' => 'branch', 'branch_id' => 1, 'amount' => 2000000])]));
$check('inactive office fund is not checked', $r['allowed'], $r['reason']);
OfficeSetting::where('branch_id', 1)->update(['approver_employee_id' => null, 'approver_role_id' => null]);
$r = $svc->submit(1, $doc([$item(['cost_center_type' => 'branch', 'branch_id' => 1, 'amount' => 10000])]));
$check('office without responsible person refused', !$r['allowed'] && str_contains($r['reason'], 'no responsible person'), $r['reason']);

// 9b. A workflow step added later lands AFTER the object steps on an in-progress object document
ObjectFlowSetting::query()->update(['enabled' => true, 'policy_office' => 'warn']); ObjectFlowSetting::forgetCache();
$r = $svc->submit(1, $doc([$proj(100000)]));
$open = $r['request'];
$newStep = ReimbursementApprovalStep::create(['module' => 'reimbursement', 'order_seq' => 2, 'name' => 'Cashier Payment', 'approver_type' => 'employee', 'approver_employee_ids' => [8], 'signs_as' => 'cashier', 'is_active' => 1]);
$applied = $svc->applyStepToOpenRequests($newStep, 6);
$seqs = $open->fresh('approvals')->approvals->sortBy('order_seq')->map(fn ($a) => $a->order_seq . ':' . $a->step_name . ':' . $a->origin)->values()->all();
$check('late workflow step is applied to the open object document', $applied['applied'] >= 1, $applied);
$check('late step lands after Finance (seq 4), object steps keep origin', count($seqs) === 4 && str_starts_with($seqs[3], '4:Cashier Payment:workflow') && str_ends_with($seqs[0], ':object'), $seqs);
$newStep->delete();
// 10. concept disabled: old behaviour (branch only, no object steps)
ObjectFlowSetting::query()->update(['enabled' => false]); ObjectFlowSetting::forgetCache();
$r = $svc->submit(1, $doc([$item(['cost_center_type' => 'project', 'delivery_project_id' => 1, 'branch_id' => 1, 'amount' => 100000])], ['step_approver' => []]));
if ($r['allowed']) {
    $check('flag off: object type ignored, stored as branch', $r['request']->items->first()->cost_center_type === 'branch');
    $check('flag off: only configured steps', $r['request']->approvals->count() === 1);
    $check('flag off: no object_flow flag', !$r['request']->hasFlag('object_flow'));
} else {
    $check('flag off submit', false, $r['reason']);
}

echo "\nRESULT: {$ok} passed, {$bad} failed\n";
Storage::disk('local')->delete('rb-scratch/sig.png');
