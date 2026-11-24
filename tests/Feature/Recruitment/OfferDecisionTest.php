<?php

namespace Tests\Feature\Recruitment;

use App\Http\Controllers\HR_General\RecruitmentOfferController;
use App\Models\Recruitment\Offer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * An offering letter is decided ONCE: the candidate accepts (which creates the employee account) or the offer is marked
 * rejected. These pin the rejection path and that a decided offer cannot be decided again. In-memory SQLite.
 */
class OfferDecisionTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded (run: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit).');
        }
        parent::setUp();

        Schema::create('recruitment_offers', function (Blueprint $t) {
            $t->id();
            $t->string('letter_number')->nullable();
            $t->string('language')->nullable();
            $t->unsignedInteger('number_sequence')->nullable();
            $t->date('offer_date')->nullable();
            $t->unsignedBigInteger('candidate_id')->nullable();
            $t->string('candidate_name')->nullable();
            $t->string('candidate_email')->nullable();
            $t->string('candidate_phone')->nullable();
            $t->string('position_title')->nullable();
            $t->text('job_description')->nullable();
            $t->text('benefits')->nullable();
            $t->date('joining_date')->nullable();
            $t->boolean('has_probation')->default(false);
            $t->string('salary_type')->nullable();
            $t->text('compensation')->nullable();
            $t->decimal('total_compensation', 15, 2)->nullable();
            $t->text('notes')->nullable();
            $t->string('signatory_name')->nullable();
            $t->string('signatory_title')->nullable();
            $t->unsignedBigInteger('signatory_employee_id')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('signed_at')->nullable();
            $t->string('decision')->default('pending');
            $t->timestamp('decided_at')->nullable();
            $t->unsignedBigInteger('created_by_employee_id')->nullable();
            $t->unsignedBigInteger('hired_employee_id')->nullable();
            $t->timestamps();
        });
        // Every offer save mirrors the offer into the Letter Register (Offer::booted), so the register tables must exist.
        Schema::create('letter_codes', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->timestamps();
        });
        Schema::create('letters', function (Blueprint $t) {
            $t->id();
            $t->string('direction')->nullable();
            $t->string('source')->nullable();
            $t->unsignedBigInteger('source_id')->nullable();
            $t->string('template_key')->nullable();
            $t->string('letter_number')->nullable();
            $t->unsignedInteger('number_sequence')->nullable();
            $t->unsignedInteger('number_year')->nullable();
            $t->unsignedBigInteger('letter_code_id')->nullable();
            $t->date('letter_date')->nullable();
            $t->string('counterparty')->nullable();
            $t->string('recipient_email')->nullable();
            $t->string('subject')->nullable();
            $t->string('language')->nullable();
            $t->string('signatory_name')->nullable();
            $t->string('signatory_title')->nullable();
            $t->unsignedBigInteger('signatory_employee_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('signed_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->string('email_status')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function offer(string $decision = 'pending'): Offer
    {
        $id = DB::table('recruitment_offers')->insertGetId([
            'candidate_name' => 'Sari Dewi', 'candidate_email' => 'sari@example.test', 'position_title' => 'Analyst',
            'decision' => $decision, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Offer::find($id);
    }

    private function controller(): RecruitmentOfferController
    {
        return app(RecruitmentOfferController::class);
    }

    public function test_rejecting_a_pending_offer_marks_it_rejected_with_a_decision_time(): void
    {
        $offer = $this->offer();

        $this->controller()->reject($offer);

        $offer->refresh();
        $this->assertSame(Offer::DECISION_REJECTED, $offer->decision);
        $this->assertNotNull($offer->decided_at);
    }

    public function test_an_offer_that_is_already_decided_cannot_be_rejected(): void
    {
        foreach ([Offer::DECISION_ACCEPTED, Offer::DECISION_REJECTED] as $decision) {
            $offer = $this->offer($decision);

            try {
                $this->controller()->reject($offer);
                $this->fail("reject from {$decision} must be refused");
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            $this->assertSame($decision, $offer->fresh()->decision);
        }
    }

    public function test_the_row_lock_refuses_a_decision_taken_by_someone_else_a_moment_earlier(): void
    {
        // The controller pre-check passes on a stale copy that still says "pending"...
        $stale = $this->offer();
        // ...but another HR user rejected the same offer in the meantime.
        DB::table('recruitment_offers')->where('id', $stale->id)->update(['decision' => Offer::DECISION_REJECTED, 'decided_at' => now()->subMinute()]);
        $decidedAt = DB::table('recruitment_offers')->where('id', $stale->id)->value('decided_at');

        $response = $this->controller()->reject($stale);

        $this->assertSame($decidedAt, DB::table('recruitment_offers')->where('id', $stale->id)->value('decided_at'), 'the first decision time is untouched');
        $this->assertStringContainsString('already been decided', (string) session('error'));
        $this->assertSame(302, $response->getStatusCode());
    }
}
