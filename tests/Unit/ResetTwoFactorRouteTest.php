<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Reset 2FA oleh administrator: jalurnya harus POST (WAF production memblokir DELETE),
 * dijaga izin menu sendiri, dan dibatasi lajunya. Perilaku lengkapnya diuji manual terhadap
 * data nyata (lihat ringkasan pekerjaan), tes ini menjaga pagar route-nya tidak lepas.
 */
class ResetTwoFactorRouteTest extends TestCase
{
    public function test_reset_two_factor_route_is_post_only_and_permission_guarded(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/employees/{id}/reset-two-factor');

        $this->assertNotNull($route, 'reset-two-factor route is missing');
        $this->assertSame(['POST'], $route->methods());

        $middleware = $route->gatherMiddleware();
        $this->assertContains('menu:master.employee.reset-two-factor', $middleware);
        $this->assertContains('throttle:10,1', $middleware);
    }

    public function test_reset_two_factor_is_not_hidden_behind_the_generic_employee_action_permission(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/employees/{id}/reset-two-factor');

        $this->assertNotContains('menu:master.employee.action', $route->gatherMiddleware(),
            'resetting 2FA needs its own permission, separate from the generic employee action');
    }
}
