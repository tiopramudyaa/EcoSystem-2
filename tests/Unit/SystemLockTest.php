<?php

namespace Tests\Unit;

use App\Http\Middleware\EnforceSystemLock;
use App\Services\SystemLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * System lock: while on, only ECI_ADMIN may sign in or keep using the system.
 */
class SystemLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('system_lock');
        Schema::create('system_lock', function ($t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->boolean('is_locked')->default(false);
            $t->string('message', 255)->nullable();
            $t->string('locked_by', 150)->nullable();
            $t->timestamp('locked_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
        DB::table('system_lock')->insert(['id' => 1, 'is_locked' => false]);
    }

    private function run_through(?array $user, string $path = 'dashboard', string $method = 'GET', bool $json = false): array
    {
        $request = Request::create('/' . $path, $method, [], [], [], $json ? ['HTTP_ACCEPT' => 'application/json'] : []);
        $session = new Store('test', new ArraySessionHandler(120));
        $session->start();
        if ($user !== null) {
            $session->put('user', $user);
        }
        $request->setLaravelSession($session);

        $reachedNext = false;
        $response = (new EnforceSystemLock())->handle($request, function () use (&$reachedNext) {
            $reachedNext = true;
            return response('ok');
        });

        return [$reachedNext, $response, $session];
    }

    public function test_unlocked_system_lets_everyone_through(): void
    {
        [$reached] = $this->run_through(['eci' => 'ECI_0001']);
        $this->assertTrue($reached);
        $this->assertFalse(SystemLockService::blocks('ECI_0001'));
    }

    public function test_locked_system_blocks_everyone_except_protected_admin(): void
    {
        SystemLockService::lock('Deploying', 'Tester');

        $this->assertTrue(SystemLockService::blocks('ECI_0001'));
        $this->assertTrue(SystemLockService::blocks(null));
        $this->assertFalse(SystemLockService::blocks('ECI_ADMIN'));
        $this->assertSame('Deploying', SystemLockService::message());
    }

    public function test_locked_middleware_signs_out_regular_user_and_keeps_admin(): void
    {
        SystemLockService::lock(null, 'Tester');

        [$reached, $response, $session] = $this->run_through(['eci' => 'ECI_0001']);
        $this->assertFalse($reached);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertNull($session->get('user'));

        [$reached, $response] = $this->run_through(['eci' => 'ECI_0001'], 'api/tickets', 'GET', true);
        $this->assertFalse($reached);
        $this->assertSame(423, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['locked']);

        [$reached] = $this->run_through(['eci' => 'ECI_ADMIN']);
        $this->assertTrue($reached);
    }

    public function test_locked_middleware_ignores_requests_without_a_signed_in_user_and_allows_logout(): void
    {
        SystemLockService::lock(null, 'Tester');

        [$reached] = $this->run_through(null, 'auth/login');
        $this->assertTrue($reached);

        [$reached] = $this->run_through(['eci' => 'ECI_0001'], 'logout', 'POST');
        $this->assertTrue($reached);
    }

    public function test_unlock_restores_access_and_missing_table_fails_open(): void
    {
        SystemLockService::lock(null, 'Tester');
        SystemLockService::unlock();
        $this->assertFalse(SystemLockService::blocks('ECI_0001'));

        Schema::drop('system_lock');
        $this->assertFalse(SystemLockService::isLocked());
    }
}
