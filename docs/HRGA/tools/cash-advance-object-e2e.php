<?php
// End-to-end check of the Cash Advance / Cash Advance Report Object and fund flow.
// Runs ONLY on a scratch database named rb_scratch (never on a working database).
// Usage: DB_DATABASE=rb_scratch php artisan migrate --force
//        DB_DATABASE=rb_scratch php artisan tinker --execute="require base_path('docs/HRGA/tools/cash-advance-object-e2e.php');"
use App\Models\CashAdvance\CashAdvance;
use App\Models\CashAdvance\CashAdvanceApprovalStep;
use App\Models\CashAdvance\CashAdvanceSetting;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Services\CashAdvance\CashAdvanceReportService;
use App\Services\CashAdvance\CashAdvanceService;
use App\Services\ObjectFund\FundService;
use Illuminate\Support\Facades\DB;

if (DB::getDatabaseName() !== 'rb_scratch') { echo "REFUSING: not the scratch database\n"; return; }

$ok = 0; $bad = 0;
$check = function (string $name, bool $cond, $extra = '') use (&$ok, &$bad) {
    $cond ? $ok++ : $bad++;
    echo ($cond ? 'PASS ' : 'FAIL ') . $name . ($cond ? '' : '  -> ' . (is_string($extra) ? $extra : json_encode($extra))) . "\n";
};

// ── seed ────────────────────────────────────────────────────────────────
DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach (['cash_advance_report_items','cash_advance_report_approvals','cash_advance_reports','cash_advance_approvals','cash_advances','object_fund_entries','object_fund_pools','object_office_settings','delivery_projects','ticket','branches','employee_basic_data','employee','customer'] as $t) {
    try { DB::table($t)->truncate(); } catch (\Throwable $e) {}
}
DB::statement('SET FOREIGN_KEY_CHECKS=1');

$mk = function (int $id, string $eci, string $nick, int $active = 1) {
    DB::table('employee')->insert(['employee_id' => $id, 'eci' => $eci, 'is_active' => $active]);
    DB::table('employee_basic_data')->insert(['employee_id' => $id, 'nick_name' => $nick, 'first_name' => $nick]);
};
foreach ([[1,'E1','Budi'],[2,'E2','Sari'],[3,'E3','Rudi'],[4,'E4','Gita'],[5,'E5','Dewi'],[6,'E6','Finance'],[8,'E8','Cashier']] as $e) { $mk(...$e); }

DB::table('branches')->insert(['id' => 1, 'code' => 'JKT', 'name' => 'Jakarta', 'latitude' => 0, 'longitude' => 0, 'is_active' => 1]);
DB::table('customer')->insert(['customer_id' => 1, 'email' => 'client@example.test']);
DB::table('delivery_projects')->insert(['id' => 1, 'name' => 'ERP Alpha', 'io_number' => 'IO-001', 'client_id' => 1, 'project_admin_id' => 2, 'project_manager_id' => 3, 'is_closed' => 0]);
DB::table('delivery_projects')->insert(['id' => 3, 'name' => 'Closed', 'io_number' => 'IO-003', 'client_id' => 1, 'project_admin_id' => 2, 'project_manager_id' => 3, 'is_closed' => 1]);
DB::table('ticket')->insert(['ticket_id' => 10, 'ticket_number' => 'T-0412', 'ticket_lead_id' => 4]);

CashAdvanceApprovalStep::query()->delete();
CashAdvanceApprovalStep::create(['module' => 'cash_advance', 'order_seq' => 1, 'name' => 'Finance Approval', 'approver_type' => 'employee', 'approver_employee_ids' => [6], 'actor_role' => 'approver', 'requester_selectable' => 0, 'is_active' => 1]);
CashAdvanceApprovalStep::create(['module' => 'cash_advance_report', 'order_seq' => 1, 'name' => 'Report Review', 'approver_type' => 'employee', 'approver_employee_ids' => [6], 'actor_role' => 'approver', 'requester_selectable' => 0, 'is_active' => 1]);

CashAdvanceSetting::current()->update(['require_car_before_new_ca' => false, 'require_detail_url' => false, 'require_cost_center' => false, 'require_description_min_chars' => 5]);

ObjectFlowSetting::query()->delete(); ObjectFlowSetting::forgetCache();
ObjectFlowSetting::create(['enabled' => false, 'cash_advance_enabled' => true]); ObjectFlowSetting::forgetCache();

FundPool::create(['object_type' => 'project', 'ref_id' => 1, 'ref_label' => 'IO-001 - ERP Alpha', 'category' => 'Transport', 'amount' => 10000000]);
OfficeSetting::create(['branch_id' => 1, 'fund_active' => true, 'monthly_amount' => 1500000, 'approver_employee_id' => 5]);

$ca    = app(CashAdvanceService::class);
$car   = app(CashAdvanceReportService::class);
$funds = app(FundService::class);
$today = now()->toDateString();

$caPayload = fn (array $o = []) => $o + ['request_date' => $today, 'description' => 'Client workshop travel advance', 'amount' => '2500000', 'cost_center_type' => 'project', 'charged_project_id' => 1, 'fund_category' => 'Transport'];
$avail = fn () => $funds->balance(FundPool::where('object_type', 'project')->first())['available'];
$names = fn ($doc) => $doc->approvals->sortBy('order_seq')->pluck('step_name')->values()->all();

// 1. Project CA
$r = $ca->submit(1, $caPayload());
$check('project CA submit allowed', $r['allowed'], $r['reason']);
$doc = $r['request'];
$n = $names($doc->fresh('approvals'));
$check('steps: Project Admin, Project Manager, Finance Approval', count($n) === 3 && str_starts_with($n[0], 'Project Admin') && str_starts_with($n[1], 'Project Manager') && $n[2] === 'Finance Approval', $n);
$check('object_flow flag + frozen label', $doc->hasFlag('object_flow') && $doc->charged_to_label === 'IO-001 - ERP Alpha', $doc->charged_to_label);
$check('fund unchanged after submit', $avail() == 10000000.0, $avail());

$x = $ca->approve($doc->fresh('approvals'), 3);
$check('manager cannot act before admin', !$x['allowed'], $x['reason']);
$check('admin approves', $ca->approve($doc->fresh('approvals'), 2)['allowed']);
$check('fund unchanged mid-flow', $avail() == 10000000.0);
$check('manager approves', $ca->approve($doc->fresh('approvals'), 3)['allowed']);
$x = $ca->approve($doc->fresh('approvals'), 6);
$check('final approval completes', $x['allowed'] && $x['completed'], $x);
$check('fund reduced by the advance on final approval', $avail() == 7500000.0, $avail());

// 2. CAR for that CA: follows the CA's Object, settles the fund to the real spend
$carItems = [['description' => 'Taxi and parking', 'amount' => '2000000', 'expense_date' => $today]];
$r = $car->submit($doc->fresh(), 1, ['report_date' => $today, 'description' => 'Workshop expense report', 'items' => $carItems]);
$check('CAR submit allowed', $r['allowed'], $r['reason']);
$rep = $r['report'];
$n = $rep->approvals->sortBy('order_seq')->pluck('step_name')->values()->all();
$check('CAR steps follow the CA Object, then Report Review', count($n) === 3 && str_starts_with($n[0], 'Project Admin') && $n[2] === 'Report Review', $n);
$check('CAR flagged object_flow', $rep->hasFlag('object_flow'));
$check('fund still at the advance while the report is pending', $avail() == 7500000.0, $avail());
$check('CAR admin approves', $car->approve($rep->fresh('approvals'), 2)['allowed']);
$check('CAR manager approves', $car->approve($rep->fresh('approvals'), 3)['allowed']);
$x = $car->approve($rep->fresh('approvals'), 6);
$check('CAR final approval completes', $x['allowed'] && $x['completed'], $x);
$fresh = $doc->fresh();
$check('CA is settled', $fresh->settlement_status === 'settled', $fresh->settlement_status);
$check('fund settles to the real spend (10M - 2M)', $avail() == 8000000.0, $avail());

// 3. void
$v = $ca->voidApproved($doc->fresh(), 8, 'Duplicate request entered twice');
$check('void refused when a report exists', !$v['allowed'] && str_contains($v['reason'], 'already has a report'), $v['reason']);

$r = $ca->submit(1, $caPayload(['amount' => '1000000']));
$doc2 = $r['request'];
foreach ([2, 3, 6] as $actor) { $ca->approve($doc2->fresh('approvals'), $actor); }
$check('second CA approved, fund 8M - 1M', $avail() == 7000000.0, $avail());
$v = $ca->voidApproved($doc2->fresh(), 8, 'short');
$check('void needs a real reason', !$v['allowed'], $v['reason']);
$v = $ca->voidApproved($doc2->fresh(), 8, 'Cash was not needed after all');
$check('void allowed', $v['allowed'], $v['reason']);
$check('fund returned after void', $avail() == 8000000.0, $avail());
$check('voided CA label', $doc2->fresh()->statusLabel() === 'Void');
$e = $car->checkEligibility($doc2->fresh());
$check('voided CA cannot be reported', !$e['allowed'] && str_contains($e['reason'], 'voided'), $e['reason']);

// 4. policy
$r = $ca->submit(1, $caPayload(['amount' => '9000000']));
$check('warn: reason required', !$r['allowed'] && str_contains($r['reason'], 'at least 10 characters'), $r['reason']);
$r = $ca->submit(1, $caPayload(['amount' => '9000000', 'over_allocation_reason' => 'Urgent multi-city workshop']));
$check('warn: submits with a reason', $r['allowed'], $r['reason']);
ObjectFlowSetting::query()->update(['policy_project' => 'block']); ObjectFlowSetting::forgetCache();
$r = $ca->submit(1, $caPayload(['amount' => '9000000']));
$check('block: refused with a clear message', !$r['allowed'] && str_contains($r['reason'], 'exceeds the remaining fund'), $r['reason']);
ObjectFlowSetting::query()->update(['policy_project' => 'warn']); ObjectFlowSetting::forgetCache();

// 5. validation of the Object
$r = $ca->submit(1, $caPayload(['cost_center_type' => null]));
$check('Object is required', !$r['allowed'] && str_contains($r['reason'], 'Choose the Object'), $r['reason']);
$r = $ca->submit(1, $caPayload(['charged_project_id' => 3]));
$check('closed project refused', !$r['allowed'] && str_contains($r['reason'], 'still running'), $r['reason']);
$r = $ca->submit(1, ['request_date' => $today, 'description' => 'Hotel for site support', 'amount' => '800000', 'cost_center_type' => 'support', 'charged_ticket_id' => 10]);
$check('support CA submits and goes to the Ticket Lead', $r['allowed'] && str_starts_with($names($r['request']->fresh('approvals'))[0], 'Ticket Lead'), $r['allowed'] ? $names($r['request']->fresh('approvals')) : $r['reason']);
$r = $ca->submit(1, ['request_date' => $today, 'description' => 'Hotel for site support', 'amount' => '800000', 'cost_center_type' => 'support', 'charged_ticket_id' => 999]);
$check('unknown ticket refused', !$r['allowed'] && str_contains($r['reason'], 'support ticket'), $r['reason']);
$r = $ca->submit(1, ['request_date' => $today, 'description' => 'Office supplies advance', 'amount' => '400000', 'cost_center_type' => 'branch', 'charged_branch_id' => 1]);
$check('office CA goes to the branch approver', $r['allowed'] && str_starts_with($names($r['request']->fresh('approvals'))[0], 'Branch Approver'), $r['allowed'] ? $names($r['request']->fresh('approvals')) : $r['reason']);

// 6. a workflow step added later lands after the object steps
$open = $ca->submit(1, $caPayload(['amount' => '100000']))['request'];
$step = CashAdvanceApprovalStep::create(['module' => 'cash_advance', 'order_seq' => 2, 'name' => 'Cashier Release', 'approver_type' => 'employee', 'approver_employee_ids' => [8], 'actor_role' => 'cashier', 'requester_selectable' => 0, 'is_active' => 1]);
$applied = $ca->applyStepToOpenRequests($step, 6);
$seqs = $open->fresh('approvals')->approvals->sortBy('order_seq')->map(fn ($a) => $a->order_seq . ':' . $a->step_name . ':' . $a->origin)->values()->all();
$check('late step applied after Finance (seq 4)', $applied['applied'] >= 1 && count($seqs) === 4 && str_starts_with($seqs[3], '4:Cashier Release:workflow') && str_ends_with($seqs[0], ':object'), $seqs);
$step->delete();

// 7. concept off for Cash Advance: old behaviour (cost center = branch or project only)
ObjectFlowSetting::query()->update(['cash_advance_enabled' => false]); ObjectFlowSetting::forgetCache();
$r = $ca->submit(1, $caPayload(['cost_center_type' => 'project']));
$check('flag off: submits with the configured steps only', $r['allowed'] && $r['request']->approvals->count() === 1 && !$r['request']->hasFlag('object_flow'), $r['reason']);

echo "\nRESULT: {$ok} passed, {$bad} failed\n";
