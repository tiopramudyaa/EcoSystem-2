<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Forgot password: tanpa pembatasan laju siapa pun bisa membanjiri satu karyawan dengan email
 * reset dan menghabiskan kuota mailbox pengirim. Perilaku lengkapnya (jeda per akun, log kegagalan
 * Graph, sesi berakhir setelah reset) diuji manual terhadap data nyata; tes ini menjaga pagar
 * route-nya tidak lepas.
 */
class ForgotPasswordRouteTest extends TestCase
{
    public function test_forgot_password_submit_is_rate_limited(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'forgot-password' && in_array('POST', $r->methods(), true));

        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->gatherMiddleware());
    }
}
