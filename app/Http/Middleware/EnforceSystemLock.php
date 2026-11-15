<?php

namespace App\Http\Middleware;

use App\Services\SystemLockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * While the system lock is on, signs out any session that is not the
 * protected administrator, on its next request. Requests without a signed-in
 * user (login page, login POST, password reset) pass through here; the login
 * endpoints refuse non-admin sign-ins themselves.
 */
class EnforceSystemLock
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!SystemLockService::isLocked()) {
            return $next($request);
        }

        $user = $request->hasSession() ? $request->session()->get('user') : null;

        if (!is_array($user) || SystemLockService::isExempt($user['eci'] ?? null)) {
            return $next($request);
        }

        // Let a locked-out user finish a normal logout.
        if ($request->isMethod('POST') && $request->is('logout', 'api/auth/logout', 'api/lite/auth/logout')) {
            return $next($request);
        }

        $message = SystemLockService::message();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'locked'  => true,
                'message' => $message,
            ], 423);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
