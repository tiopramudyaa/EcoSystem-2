<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    /**
     * Satu atau beberapa slug (`menu:a,b`): cukup punya salah satu untuk lolos.
     */
    public function handle(Request $request, Closure $next, string ...$menuSlugs): Response
    {
        $user = session('user');

        if (!$user || ($user['type'] ?? null) !== 'employee') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $employee = Employee::find($user['id'] ?? null);
        $canAccess = false;
        if ($employee) {
            foreach ($menuSlugs as $slug) {
                if ($employee->canAccessMenu($slug)) {
                    $canAccess = true;
                    break;
                }
            }
        }

        if (!$canAccess) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Akun Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator.',
                ], 403);
            }

            // Jangan redirect ke dashboard jika route saat ini IS dashboard (akan looping)
            if ($request->routeIs('dashboard')) {
                abort(403, 'Akun Anda tidak memiliki izin untuk mengakses dashboard. Hubungi administrator.');
            }

            return redirect()->route('dashboard')
                ->with('warning', 'Akses ditolak. Akun Anda tidak memiliki izin untuk mengakses halaman tersebut. Hubungi administrator jika Anda membutuhkan akses.');
        }

        return $next($request);
    }
}
