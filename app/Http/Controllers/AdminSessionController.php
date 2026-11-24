<?php

namespace App\Http\Controllers;

use App\Enums\RoleId;
use App\Services\IpLocationService;
use App\Services\LoginSecurityService;
use App\Services\SystemLockService;
use App\Support\SessionPayloadDecoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ChecksMenuAccess;

class AdminSessionController extends Controller
{
    use ChecksMenuAccess;

    private function assertAdmin(): bool
    {
        return $this->menuAllowed('control-center.sessions');
    }

    private function decodeSessionUser(?string $payload): ?array
    {
        return SessionPayloadDecoder::decode($payload);
    }

    /** Audit Log entry; never lets an audit failure block the force logout itself. */
    private function auditForceLogout(int|string $targetId, ?string $label, string $description, array $new): void
    {
        try {
            \App\Models\AuditLog::recordAction(
                module: 'Session',
                auditableType: 'session',
                auditableId: $targetId,
                event: 'force_logout',
                recordLabel: $label,
                description: $description,
                old: null,
                new: $new
            );
        } catch (\Throwable $e) {
            Log::warning('AdminSessionController: audit write failed', ['error' => $e->getMessage()]);
        }
    }

    public function index(Request $request)
    {
        if (!$this->assertAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $lifetime = config('session.lifetime', 120);
            $expiredBefore = now()->subMinutes($lifetime)->timestamp;

            $sessions = DB::table('sessions')
                ->where('last_activity', '>', $expiredBefore)
                ->select('id as session_id', 'ip_address', 'user_agent', 'last_activity', 'payload')
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($s) {
                    $user = $this->decodeSessionUser($s->payload);

                    $s->user_id       = $user['id'] ?? null;
                    $s->username      = null;
                    $s->email         = $user['email'] ?? null;
                    $s->eci           = $user['eci'] ?? null;
                    $s->full_name     = $user['name'] ?? null;
                    $s->last_activity_at = date('Y-m-d H:i:s', $s->last_activity);
                    $s->is_current    = ($s->session_id === session()->getId());
                    $s->is_protected  = ($s->eci === LoginSecurityService::PROTECTED_ECI);

                    unset($s->payload);
                    return $s;
                })
                // Drop sessions that never completed a login (failed attempts,
                // or just someone sitting on the login page) - only rows with
                // a decoded `user` payload represent an actual signed-in user.
                ->filter(fn ($s) => $s->user_id !== null)
                ->values();

            // Location, not just a raw IP: resolve once per DISTINCT ip_address
            // (cached 24h per IP in IpLocationService) rather than once per
            // session row, since the same employee/office IP often shows up
            // across several concurrent sessions.
            $locations = IpLocationService::formatMany($sessions->pluck('ip_address')->all());
            $sessions  = $sessions->map(function ($s) use ($locations) {
                $s->location = $locations[$s->ip_address] ?? 'Unknown location';
                return $s;
            });

            return response()->json([
                'success' => true,
                'data'    => $sessions,
                'total'   => $sessions->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSessionController@index error', [
                'error'    => $e->getMessage(),
                'error_at' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch sessions'], 500);
        }
    }

    public function destroy(Request $request, string $sessionId)
    {
        if (!$this->assertAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($sessionId === session()->getId()) {
            return response()->json(['success' => false, 'message' => 'Cannot force-logout your own session'], 422);
        }

        $payload = DB::table('sessions')->where('id', $sessionId)->value('payload');
        $user    = $this->decodeSessionUser($payload);

        if (($user['eci'] ?? null) === LoginSecurityService::PROTECTED_ECI) {
            return response()->json(['success' => false, 'message' => 'This account is protected and cannot be force-logged-out'], 422);
        }

        try {
            $deleted = DB::table('sessions')->where('id', $sessionId)->delete();

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Session not found'], 404);
            }

            Log::info('AdminSessionController: force logout', [
                'target_session' => $sessionId,
                'by'             => session('user.eci') ?? session('user.name') ?? 'admin',
                'ip'             => $request->ip(),
            ]);

            $this->auditForceLogout(
                $user['id'] ?? 0,
                $user['name'] ?? null,
                'force-logged-out ' . ($user['name'] ?? 'a session') . ' (' . ($user['eci'] ?? 'unknown') . ')',
                ['eci' => $user['eci'] ?? null]
            );

            return response()->json(['success' => true, 'message' => 'Session terminated']);
        } catch (\Exception $e) {
            Log::error('AdminSessionController@destroy error', [
                'error'    => $e->getMessage(),
                'error_at' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to terminate session'], 500);
        }
    }

    public function destroyAll(Request $request)
    {
        if (!$this->assertAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $currentSessionId = session()->getId();

            $expiredBefore = now()->subMinutes(config('session.lifetime', 120))->timestamp;

            // Same window as index(), so the count in the message matches
            // the rows the admin actually saw on the page.
            $candidates = DB::table('sessions')
                ->where('id', '!=', $currentSessionId)
                ->where('last_activity', '>', $expiredBefore)
                ->select('id', 'payload')
                ->get();

            $deletableIds = $candidates
                ->filter(function ($s) {
                    $user = $this->decodeSessionUser($s->payload);

                    // Only drop identified (logged-in) sessions, and never the protected admin.
                    return $user !== null && ($user['eci'] ?? null) !== LoginSecurityService::PROTECTED_ECI;
                })
                ->pluck('id');

            $count = DB::table('sessions')->whereIn('id', $deletableIds)->delete();

            Log::info('AdminSessionController: force logout ALL', [
                'count' => $count,
                'by'    => session('user.eci') ?? session('user.name') ?? 'admin',
                'ip'    => $request->ip(),
            ]);

            $this->auditForceLogout(0, 'All other sessions', "force-logged-out all other sessions ({$count})", ['count' => $count]);

            return response()->json([
                'success' => true,
                'message' => "Terminated {$count} session(s)",
                'count'   => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSessionController@destroyAll error', [
                'error'    => $e->getMessage(),
                'error_at' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to terminate sessions'], 500);
        }
    }

    private function isProtectedAdmin(): bool
    {
        return session('user.eci') === LoginSecurityService::PROTECTED_ECI;
    }

    /** GET /api/admin/sessions/lock - current system lock state (menu holders can read it). */
    public function lockStatus()
    {
        if (!$this->assertAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success'    => true,
            'data'       => SystemLockService::state(),
            'can_change' => $this->isProtectedAdmin(),
        ]);
    }

    /**
     * POST /api/admin/sessions/lock - turn the system lock on and sign out
     * everyone except the protected admin. Only the protected admin may do
     * this: any other account would lock itself out the moment it clicked.
     */
    public function lock(Request $request)
    {
        if (!$this->assertAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$this->isProtectedAdmin()) {
            return response()->json(['success' => false, 'message' => 'Only the protected administrator can lock the system'], 403);
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('system_lock')) {
            return response()->json(['success' => false, 'message' => 'System lock is not set up yet. Run the database migrations first.'], 500);
        }

        $validated = $request->validate(['message' => 'nullable|string|max:255']);

        try {
            $by = session('user.name') ?? session('user.eci') ?? 'admin';
            SystemLockService::lock($validated['message'] ?? null, $by);

            // Sign out every identified, non-protected session right now so the
            // lock takes effect immediately, not on each person's next click.
            $ids = DB::table('sessions')->select('id', 'payload')->get()
                ->filter(function ($s) {
                    $user = $this->decodeSessionUser($s->payload);
                    return $user !== null && ($user['eci'] ?? null) !== LoginSecurityService::PROTECTED_ECI;
                })
                ->pluck('id');
            $count = $ids->isEmpty() ? 0 : DB::table('sessions')->whereIn('id', $ids)->delete();

            Log::info('AdminSessionController: system LOCKED', ['by' => $by, 'signed_out' => $count, 'ip' => $request->ip()]);

            $this->auditLock('locked', "locked the system ({$count} session(s) signed out)", ['message' => $validated['message'] ?? null, 'signed_out' => $count]);

            return response()->json([
                'success' => true,
                'message' => "System locked. {$count} session(s) signed out.",
                'data'    => SystemLockService::state(),
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSessionController@lock error', ['error' => $e->getMessage(), 'error_at' => $e->getFile() . ':' . $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Failed to lock the system'], 500);
        }
    }

    /** POST /api/admin/sessions/unlock - turn the system lock off. Protected admin only. */
    public function unlock(Request $request)
    {
        if (!$this->assertAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$this->isProtectedAdmin()) {
            return response()->json(['success' => false, 'message' => 'Only the protected administrator can unlock the system'], 403);
        }

        try {
            SystemLockService::unlock();

            Log::info('AdminSessionController: system UNLOCKED', [
                'by' => session('user.name') ?? session('user.eci') ?? 'admin',
                'ip' => $request->ip(),
            ]);

            $this->auditLock('unlocked', 'unlocked the system', []);

            return response()->json([
                'success' => true,
                'message' => 'System unlocked. Users can sign in again.',
                'data'    => SystemLockService::state(),
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSessionController@unlock error', ['error' => $e->getMessage(), 'error_at' => $e->getFile() . ':' . $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Failed to unlock the system'], 500);
        }
    }

    private function auditLock(string $event, string $description, array $new): void
    {
        try {
            \App\Models\AuditLog::recordAction(
                module: 'Session',
                auditableType: 'system_lock',
                auditableId: 1,
                event: $event,
                recordLabel: 'System lock',
                description: $description,
                old: null,
                new: $new ?: null
            );
        } catch (\Throwable $e) {
            Log::warning('AdminSessionController: audit write failed', ['error' => $e->getMessage()]);
        }
    }

    public function page()
    {
        if (!$this->assertAdmin()) {
            abort(403);
        }
        return view('admin.sessions');
    }
}
