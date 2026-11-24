<?php

namespace Tests\Feature\Letters;

use App\Models\Letters\Letter;
use App\Models\Letters\LetterRequest;
use App\Services\Letters\LetterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Letter requests and the register: a request is rejected once, a numbered letter is voided once (its number is never
 * reused), voiding a letter sends its open request back to HR, and the requester is told. In-memory SQLite.
 */
class LetterDecisionTest extends TestCase
{
    private const REQUESTER = 11;
    private const HR = 99;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded (run: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit).');
        }
        parent::setUp();

        Schema::create('letter_requests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id');
            $t->unsignedBigInteger('request_type_id')->nullable();
            $t->string('request_type_name')->nullable();
            $t->boolean('is_other')->default(false);
            $t->string('template_key')->nullable();
            $t->string('language')->nullable();
            $t->text('purpose')->nullable();
            $t->date('needed_by')->nullable();
            $t->text('notes')->nullable();
            $t->string('status')->default('pending');
            $t->text('reject_reason')->nullable();
            $t->unsignedBigInteger('handled_by')->nullable();
            $t->timestamp('handled_at')->nullable();
            $t->unsignedBigInteger('letter_id')->nullable();
            $t->timestamps();
        });
        Schema::create('letters', function (Blueprint $t) {
            $t->id();
            $t->string('direction')->default('outgoing');
            $t->string('source')->default('template');
            $t->string('template_key')->nullable();
            $t->string('letter_number')->nullable();
            $t->string('status')->default('generated');
            $t->unsignedBigInteger('letter_request_id')->nullable();
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->text('void_reason')->nullable();
            $t->unsignedBigInteger('voided_by')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('type');
            $t->unsignedBigInteger('ticket_id')->nullable();
            $t->unsignedBigInteger('from_employee_id')->nullable();
            $t->string('from_name')->nullable();
            $t->text('preview')->nullable();
            $t->string('link')->nullable();
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });
    }

    private function service(): LetterService
    {
        return app(LetterService::class);
    }

    private function request(string $status = 'pending'): LetterRequest
    {
        $id = DB::table('letter_requests')->insertGetId([
            'employee_id' => self::REQUESTER, 'request_type_name' => 'Employment letter', 'template_key' => 'employment',
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return LetterRequest::find($id);
    }

    private function letter(?int $requestId = null, string $status = 'generated'): Letter
    {
        $id = DB::table('letters')->insertGetId([
            'letter_number' => '001/HR/XI/2026', 'status' => $status, 'letter_request_id' => $requestId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Letter::find($id);
    }

    public function test_rejecting_an_open_request_records_the_reason_the_handler_and_tells_the_requester(): void
    {
        $request = $this->request();

        $this->assertTrue($this->service()->reject($request, 'Not eligible yet', self::HR));

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame('Not eligible yet', $request->reject_reason);
        $this->assertSame(self::HR, (int) $request->handled_by);
        $this->assertSame(1, DB::table('notifications')->where('employee_id', self::REQUESTER)->where('type', 'letter_request_rejected')->count());
    }

    public function test_a_request_that_is_already_closed_cannot_be_rejected_again(): void
    {
        foreach (['rejected', 'done', 'cancelled'] as $status) {
            $request = $this->request($status);

            $this->assertFalse($this->service()->reject($request, 'Late', self::HR), "reject from {$status}");
            $this->assertSame($status, $request->fresh()->status);
        }

        $this->assertSame(0, DB::table('notifications')->count(), 'a refused rejection tells nobody');
    }

    public function test_voiding_marks_the_letter_void_and_keeps_the_number_in_the_register(): void
    {
        $letter = $this->letter();

        $this->assertTrue($this->service()->void($letter, 'Wrong name', self::HR));

        $letter->refresh();
        $this->assertSame('void', $letter->status);
        $this->assertSame('Wrong name', $letter->void_reason);
        $this->assertSame(self::HR, (int) $letter->voided_by);
        $this->assertSame('001/HR/XI/2026', $letter->letter_number, 'the number stays on the voided letter');
        $this->assertNull($letter->deleted_at, 'a numbered letter is voided, never deleted');
    }

    public function test_voiding_twice_keeps_the_first_reason(): void
    {
        $letter = $this->letter();
        $this->service()->void($letter, 'First reason', self::HR);

        $this->assertFalse($this->service()->void($letter->fresh(), 'Second reason', 1234));

        $letter->refresh();
        $this->assertSame('First reason', $letter->void_reason);
        $this->assertSame(self::HR, (int) $letter->voided_by);
    }

    public function test_voiding_the_letter_of_an_open_request_sends_the_request_back_to_pending(): void
    {
        $request = $this->request('in_progress');
        $letter  = $this->letter($request->id);
        DB::table('letter_requests')->where('id', $request->id)->update(['letter_id' => $letter->id]);

        $this->service()->void($letter->fresh(), 'Needs a new letter', self::HR);

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->letter_id);
    }
}
