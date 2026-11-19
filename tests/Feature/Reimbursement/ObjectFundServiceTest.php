<?php

namespace Tests\Feature\Reimbursement;

use App\Models\CashAdvance\CashAdvance;
use App\Models\ObjectFund\FundEntry;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\ObjectFund\OfficeSetting;
use App\Models\Reimbursement\ReimbursementRequest;
use App\Services\ObjectFund\FundService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Dana per Object: dana berkurang saat disetujui (bukan saat dikirim), kebijakan
 * Peringatan dan Blokir, Office nonaktif tidak diperiksa, pemeriksaan ulang, dan
 * pengembalian saat void. SQLite in-memory dengan hanya tabel yang dibaca.
 */
class ObjectFundServiceTest extends TestCase
{
    private FundService $funds;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded.');
        }
        parent::setUp();

        Schema::create('object_fund_pools', function (Blueprint $t) {
            $t->id();
            $t->string('object_type', 10);
            $t->unsignedBigInteger('ref_id');
            $t->string('ref_label', 200)->nullable();
            $t->string('category', 60)->default('');
            $t->string('period', 7)->default('');
            $t->decimal('amount', 20, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->string('status', 10)->default('approved');
            $t->unsignedBigInteger('proposed_by')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->string('notes', 255)->nullable();
            $t->timestamps();
            $t->unique(['object_type', 'ref_id', 'category', 'period']);
        });
        Schema::create('object_fund_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('pool_id');
            $t->string('document_type', 30)->default('reimbursement');
            $t->unsignedBigInteger('document_id');
            $t->unsignedBigInteger('item_id')->nullable();
            $t->decimal('amount', 20, 2);
            $t->string('status', 10)->default('committed');
            $t->boolean('over_allocation')->default(false);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->unsignedBigInteger('voided_by')->nullable();
            $t->string('void_reason', 255)->nullable();
            $t->timestamps();
        });
        Schema::create('object_office_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('branch_id')->unique();
            $t->boolean('fund_active')->default(false);
            $t->decimal('monthly_amount', 20, 2)->default(0);
            $t->unsignedBigInteger('approver_role_id')->nullable();
            $t->unsignedBigInteger('approver_employee_id')->nullable();
            $t->timestamps();
        });
        Schema::create('reimbursement_requests', function (Blueprint $t) {
            $t->id();
            $t->string('request_no');
            $t->unsignedBigInteger('employee_id');
            $t->date('request_date');
            $t->string('status');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('reimbursement_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('reimbursement_request_id');
            $t->unsignedSmallInteger('line_no')->default(1);
            $t->string('description')->nullable();
            $t->string('cost_center_type', 20)->default('branch');
            $t->unsignedBigInteger('branch_id')->nullable();
            $t->unsignedBigInteger('delivery_project_id')->nullable();
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->string('cost_center_label')->nullable();
            $t->string('fund_category', 60)->nullable();
            $t->decimal('amount', 20, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('cash_advances', function (Blueprint $t) {
            $t->id();
            $t->string('request_no')->default('CA/TEST');
            $t->unsignedBigInteger('employee_id')->default(1);
            $t->date('request_date');
            $t->string('description')->default('Test');
            $t->string('currency', 3)->default('IDR');
            $t->decimal('amount', 20, 2)->default(0);
            $t->string('cost_center_type', 20)->nullable();
            $t->unsignedBigInteger('charged_branch_id')->nullable();
            $t->unsignedBigInteger('charged_project_id')->nullable();
            $t->unsignedBigInteger('charged_ticket_id')->nullable();
            $t->string('charged_to_label')->nullable();
            $t->string('fund_category', 60)->nullable();
            $t->string('status')->default('approved');
            $t->string('settlement_status')->default('unreported');
            $t->decimal('reported_amount', 20, 2)->nullable();
            $t->unsignedSmallInteger('car_count')->default(0);
            $t->json('flags')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        $this->funds = new FundService();
    }

    private function projectPool(float $amount, string $category = 'Transport'): FundPool
    {
        return FundPool::create([
            'object_type' => 'project', 'ref_id' => 7, 'ref_label' => 'ERP Alpha',
            'category' => $category, 'amount' => $amount,
        ]);
    }

    private function item(float $amount, string $category = 'Transport'): array
    {
        return [
            'cost_center_type' => 'project', 'delivery_project_id' => 7,
            'fund_category' => $category, 'amount' => $amount,
        ];
    }

    private function settings(string $policy = 'warn'): ObjectFlowSetting
    {
        return new ObjectFlowSetting(['policy_project' => $policy, 'policy_office' => $policy, 'policy_support' => $policy]);
    }

    private function document(array $items): ReimbursementRequest
    {
        $request = ReimbursementRequest::create([
            'request_no' => 'RB/2026/11/00001', 'employee_id' => 1,
            'request_date' => '2026-11-19', 'status' => 'approved',
        ]);

        foreach ($items as $i => $item) {
            $request->items()->create($item + ['line_no' => $i + 1]);
        }

        return $request->fresh('items');
    }

    public function test_an_amount_within_the_remaining_fund_passes(): void
    {
        $this->projectPool(10000000);

        $result = $this->funds->evaluate([$this->item(2500000)], Carbon::parse('2026-11-19'), $this->settings());

        $this->assertNull($result['blocked']);
        $this->assertSame([], $result['warnings']);
        $this->assertFalse($result['needs_reason']);
    }

    public function test_warn_policy_allows_the_overage_but_asks_for_a_reason(): void
    {
        $this->projectPool(1000000);

        $result = $this->funds->evaluate([$this->item(1300000)], Carbon::parse('2026-11-19'), $this->settings('warn'));

        $this->assertNull($result['blocked']);
        $this->assertTrue($result['needs_reason']);
        $this->assertEquals(300000.0, $result['warnings'][0]['over']);
    }

    public function test_block_policy_refuses_the_overage_with_a_clear_message(): void
    {
        $this->projectPool(1000000);

        $result = $this->funds->evaluate([$this->item(1300000)], Carbon::parse('2026-11-19'), $this->settings('block'));

        $this->assertStringContainsString('exceeds the remaining fund', (string) $result['blocked']);
        $this->assertStringContainsString('ERP Alpha', (string) $result['blocked']);
    }

    public function test_an_object_without_a_fund_is_not_checked(): void
    {
        // Project 7 tidak punya kantong dana.
        $result = $this->funds->evaluate([$this->item(99999999)], Carbon::parse('2026-11-19'), $this->settings('block'));

        $this->assertNull($result['blocked']);
        $this->assertSame([], $result['checked']);
    }

    public function test_an_inactive_office_fund_is_not_checked_and_an_active_one_is_monthly(): void
    {
        OfficeSetting::create(['branch_id' => 3, 'fund_active' => false, 'monthly_amount' => 5000000]);
        $office = ['cost_center_type' => 'branch', 'branch_id' => 3, 'cost_center_label' => 'JKT - Jakarta', 'amount' => 6000000];

        $off = $this->funds->evaluate([$office], Carbon::parse('2026-11-19'), $this->settings('block'));
        $this->assertNull($off['blocked']);

        OfficeSetting::where('branch_id', 3)->update(['fund_active' => true]);
        $on = $this->funds->evaluate([$office], Carbon::parse('2026-11-19'), $this->settings('block'));
        $this->assertNotNull($on['blocked']);

        $pool = FundPool::where('object_type', 'office')->first();
        $this->assertSame('2026-11', $pool->period);
    }

    public function test_funds_decrease_on_commit_not_on_submit(): void
    {
        $pool = $this->projectPool(10000000);
        $document = $this->document([$this->item(2500000)]);

        $this->assertEquals(10000000.0, $this->funds->balance($pool)['available']);

        $this->funds->commit($document, 9);

        $balance = $this->funds->balance($pool->fresh());
        $this->assertEquals(7500000.0, $balance['available']);
        $this->assertEquals(2500000.0, $balance['committed']);
    }

    public function test_commit_is_idempotent(): void
    {
        $pool = $this->projectPool(10000000);
        $document = $this->document([$this->item(2500000)]);

        $this->assertSame(1, $this->funds->commit($document, 9));
        $this->assertSame(0, $this->funds->commit($document, 9));
        $this->assertEquals(7500000.0, $this->funds->balance($pool->fresh())['available']);
    }

    public function test_an_overage_at_commit_is_flagged(): void
    {
        $pool = $this->projectPool(1000000);
        $document = $this->document([$this->item(1300000)]);

        $this->funds->commit($document, 9);

        $this->assertTrue(FundEntry::first()->over_allocation);
        $this->assertEquals(-300000.0, $this->funds->balance($pool->fresh())['available']);
    }

    public function test_recheck_blocks_when_the_remaining_fund_changed_under_block_policy(): void
    {
        $pool = $this->projectPool(1000000);
        $first  = $this->document([$this->item(800000)]);
        $second = $this->document([$this->item(800000)]);

        // Dokumen pertama disetujui lebih dulu dan menghabiskan sebagian besar sisa.
        $this->funds->commit($first, 9);

        $message = $this->funds->recheckForApproval($second, $this->settings('block'));
        $this->assertStringContainsString('remaining fund changed', (string) $message);

        // Kebijakan Peringatan tidak memblokir pemeriksaan ulang.
        $this->assertNull($this->funds->recheckForApproval($second, $this->settings('warn')));
    }

    public function test_release_returns_the_fund_and_is_recorded(): void
    {
        $pool = $this->projectPool(10000000);
        $document = $this->document([$this->item(2500000)]);
        $this->funds->commit($document, 9);

        $released = $this->funds->release($document, 31, 'Duplicate receipt');

        $this->assertSame(1, $released);
        $this->assertEquals(10000000.0, $this->funds->balance($pool->fresh())['available']);
        $entry = FundEntry::first();
        $this->assertSame('voided', $entry->status);
        $this->assertSame('Duplicate receipt', $entry->void_reason);
        $this->assertSame(31, (int) $entry->voided_by);
    }

    public function test_a_general_pool_is_used_when_the_category_has_no_pool(): void
    {
        $this->projectPool(500000, '');

        $result = $this->funds->evaluate([$this->item(800000, 'Meals')], Carbon::parse('2026-11-19'), $this->settings('block'));

        $this->assertNotNull($result['blocked']);
    }
    // ── Cash Advance ────────────────────────────────────────────────────────

    private function advance(float $amount, string $status = 'approved'): CashAdvance
    {
        return CashAdvance::create([
            'request_date' => '2026-11-19', 'amount' => $amount, 'status' => $status,
            'cost_center_type' => 'project', 'charged_project_id' => 7, 'fund_category' => 'Transport',
            'charged_to_label' => 'IO-001 - ERP Alpha',
        ]);
    }

    public function test_a_cash_advance_reduces_the_fund_when_committed(): void
    {
        $pool = $this->projectPool(10000000);
        $advance = $this->advance(2500000);

        $this->assertSame(1, $this->funds->commitAdvance($advance, 9));
        $this->assertSame(0, $this->funds->commitAdvance($advance, 9), 'commit is idempotent');
        $this->assertEquals(7500000.0, $this->funds->balance($pool->fresh())['available']);
    }

    public function test_the_fund_settles_to_the_reported_amount_once_the_advance_is_settled(): void
    {
        $pool = $this->projectPool(10000000);
        $advance = $this->advance(2500000);
        $this->funds->commitAdvance($advance, 9);

        // Belum settled: tetap nominal uang muka.
        $advance->update(['reported_amount' => 2000000, 'settlement_status' => 'reporting']);
        $this->funds->syncAdvanceEntry($advance->fresh());
        $this->assertEquals(7500000.0, $this->funds->balance($pool->fresh())['available']);

        // Settled: nominal yang sungguh dibelanjakan.
        $advance->update(['settlement_status' => 'settled']);
        $this->funds->syncAdvanceEntry($advance->fresh());
        $this->assertEquals(8000000.0, $this->funds->balance($pool->fresh())['available']);

        // Laporan dibatalkan sehingga tidak lagi settled: kembali ke uang muka.
        $advance->update(['settlement_status' => 'unreported', 'reported_amount' => null]);
        $this->funds->syncAdvanceEntry($advance->fresh());
        $this->assertEquals(7500000.0, $this->funds->balance($pool->fresh())['available']);
    }

    public function test_a_claim_above_the_advance_can_push_the_fund_over_its_allocation(): void
    {
        $pool = $this->projectPool(3000000);
        $advance = $this->advance(2500000);
        $this->funds->commitAdvance($advance, 9);

        $advance->update(['reported_amount' => 3400000, 'settlement_status' => 'settled']);
        $this->funds->syncAdvanceEntry($advance->fresh());

        $this->assertEquals(-400000.0, $this->funds->balance($pool->fresh())['available']);
        $this->assertTrue(FundEntry::first()->over_allocation);
    }

    public function test_release_returns_a_cash_advance_fund_and_pending_advances_are_only_information(): void
    {
        $pool = $this->projectPool(10000000);
        $waiting = $this->advance(1000000, 'submitted');

        $this->assertEquals(10000000.0, $this->funds->balance($pool)['available']);
        $this->assertEquals(1000000.0, $this->funds->balance($pool)['pending']);

        $approved = $this->advance(2500000);
        $this->funds->commitAdvance($approved, 9);
        $this->assertSame(1, $this->funds->releaseDocument('cash_advance', $approved->id, 31, 'No longer needed'));
        $this->assertEquals(10000000.0, $this->funds->balance($pool->fresh())['available']);
    }

    public function test_recheck_blocks_a_cash_advance_when_another_document_used_the_fund(): void
    {
        $pool = $this->projectPool(1000000);
        $first  = $this->advance(800000);
        $second = $this->advance(800000);

        $this->funds->commitAdvance($first, 9);

        $this->assertStringContainsString('remaining fund changed', (string) $this->funds->recheckAdvanceForApproval($second, $this->settings('block')));
        $this->assertNull($this->funds->recheckAdvanceForApproval($second, $this->settings('warn')));
    }
}
