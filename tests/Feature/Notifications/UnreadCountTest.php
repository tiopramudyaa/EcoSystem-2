<?php

namespace Tests\Feature\Notifications;

use App\Http\Controllers\NotificationController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The bell badge polls this endpoint every 15 seconds for every open tab. It once built its query with fewer placeholders
 * than values (SQLSTATE HY093 in the log), which broke the badge for everybody. This pins the counts.
 */
class UnreadCountTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not loaded (run: php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit).');
        }
        parent::setUp();

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

    private function notify(int $employeeId, string $type, bool $read = false): void
    {
        DB::table('notifications')->insert(['employee_id' => $employeeId, 'type' => $type, 'is_read' => $read, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_it_counts_unread_notifications_and_the_chat_subset_for_the_signed_in_employee_only(): void
    {
        $this->notify(5, 'ticket_reply');
        $this->notify(5, 'ticket_internal_note');
        $this->notify(5, 'note_reply');
        $this->notify(5, 'leave_permit_approved');
        $this->notify(5, 'ticket_reply', true);   // already read: not counted
        $this->notify(6, 'ticket_reply');         // somebody else's: not counted
        session(['user' => ['id' => 5]]);

        $data = app(NotificationController::class)->unreadCount()->getData();

        $this->assertTrue($data->success);
        $this->assertSame(4, $data->count);
        $this->assertSame(3, $data->message_sound_count);
    }

    public function test_it_answers_zero_when_there_is_nothing_unread(): void
    {
        session(['user' => ['id' => 5]]);

        $data = app(NotificationController::class)->unreadCount()->getData();

        $this->assertSame(0, $data->count);
        $this->assertSame(0, $data->message_sound_count);
    }

    public function test_it_refuses_a_request_with_no_signed_in_user(): void
    {
        $response = app(NotificationController::class)->unreadCount();

        $this->assertSame(401, $response->getStatusCode());
    }
}
