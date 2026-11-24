<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\LoginSecurityService;
use App\Support\IntendedUrl;
use App\Support\MicrosoftIdToken;
use App\Support\MicrosoftLoginSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Login dengan Microsoft (Microsoft Entra ID, akun M365 yang sama dengan Teams).
 *
 * Ini OPSI TAMBAHAN: login password tetap berjalan seperti biasa. Alur:
 * authorization code + PKCE. Identitas dipercaya lewat `oid` (disimpan di
 * auth_users.microsoft_oid), bukan email. Akun yang belum tertaut dicocokkan
 * sekali lewat email ke auth_users.email, lalu ditautkan; akun yang tidak
 * terdaftar DITOLAK (tidak pernah dibuatkan akun otomatis).
 *
 * Setelah identitas terbukti, semua gerbang login normal tetap berlaku lewat
 * AuthController::completeEmployeeLogin(): akun aktif, role 'User System
 * Registered', system lock, dan 2FA (kode TOTP tetap diminta bagi akun yang
 * 2FA-nya aktif). Customer tidak boleh masuk ke EcoSystem.
 */
class MicrosoftAuthController extends Controller
{
    private const SESSION_KEY = 'ms_oauth';

    /** Batas waktu antara klik tombol dan kembalinya pengguna dari Microsoft. */
    private const FLOW_TTL_SECONDS = 600;

    private const SCOPES = 'openid profile email';

    // ── Langkah 1: kirim pengguna ke Microsoft ──────────────────────────────

    /** GET /auth/microsoft (publik): mulai login. */
    public function redirect(Request $request): RedirectResponse
    {
        if (session()->has('auth_token')) {
            return redirect()->to(IntendedUrl::pull($request));
        }

        if (!MicrosoftLoginSettings::loginAvailable()) {
            return redirect()->route('login')->with('error', 'Sign in with Microsoft is not available.');
        }

        // Tujuan deep link (mis. tiket dari kartu Teams) dibawa lewat session,
        // setelah disanitasi supaya tidak jadi open redirect.
        IntendedUrl::remember((string) $request->query(IntendedUrl::QUERY_KEY, ''), $request);

        return $this->begin('login');
    }

    /** GET /settings/microsoft/connect (sudah login): tautkan akun Microsoft. */
    public function connect(): RedirectResponse
    {
        if (!MicrosoftLoginSettings::loginAvailable()) {
            return $this->backToSettings('error', 'Sign in with Microsoft is not enabled.');
        }

        return $this->begin('link');
    }

    private function begin(string $mode): RedirectResponse
    {
        $state    = Str::random(40);
        $nonce    = Str::random(32);
        $verifier = Str::random(64);

        session()->put(self::SESSION_KEY, [
            'mode'     => $mode,
            'state'    => $state,
            'nonce'    => $nonce,
            'verifier' => $verifier,
            'created'  => time(),
        ]);

        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $url = 'https://login.microsoftonline.com/' . rawurlencode(MicrosoftLoginSettings::tenantId())
            . '/oauth2/v2.0/authorize?' . http_build_query([
                'client_id'             => MicrosoftLoginSettings::clientId(),
                'response_type'         => 'code',
                'redirect_uri'          => MicrosoftLoginSettings::redirectUri(),
                'response_mode'         => 'query',
                'scope'                 => self::SCOPES,
                'state'                 => $state,
                'nonce'                 => $nonce,
                'code_challenge'        => $challenge,
                'code_challenge_method' => 'S256',
                'prompt'                => 'select_account',
            ]);

        return redirect()->away($url);
    }

    // ── Langkah 2: Microsoft mengembalikan pengguna ─────────────────────────

    /** GET /auth/microsoft/callback (publik). */
    public function callback(Request $request): RedirectResponse
    {
        $requestId = uniqid('ms_login_', true);
        $ctx       = session()->pull(self::SESSION_KEY);
        $mode      = is_array($ctx) && ($ctx['mode'] ?? '') === 'link' ? 'link' : 'login';

        $fail = fn (string $message): RedirectResponse => $mode === 'link'
            ? $this->backToSettings('error', $message)
            : redirect()->route('login')->with('error', $message);

        if (!is_array($ctx)
            || (time() - (int) ($ctx['created'] ?? 0)) > self::FLOW_TTL_SECONDS
            || !hash_equals((string) ($ctx['state'] ?? ''), (string) $request->query('state', ''))
        ) {
            return $fail('Your Microsoft sign-in session expired. Please try again.');
        }

        if ($request->query('error')) {
            // Pesan Microsoft (AADSTS...) hanya untuk admin, tidak ditampilkan ke pengguna.
            Log::channel('daily')->warning('Microsoft sign-in returned an error', [
                'request_id'  => $requestId,
                'error'       => $request->query('error'),
                'description' => Str::limit((string) $request->query('error_description'), 300),
            ]);

            return $fail('Microsoft sign-in was cancelled or refused. Please try again.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '' || !MicrosoftLoginSettings::loginAvailable()) {
            return $fail('Microsoft sign-in could not be completed. Please try again.');
        }

        try {
            $claims = $this->exchangeCode($code, $ctx, $requestId);
        } catch (InvalidArgumentException $e) {
            Log::channel('daily')->warning('Microsoft ID token rejected', [
                'request_id' => $requestId,
                'reason'     => $e->getMessage(),
            ]);

            return $fail('Microsoft sign-in could not be verified. Please try again or use your password.');
        } catch (Throwable $e) {
            Log::channel('daily')->error('Microsoft token exchange failed', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);

            return $fail('Microsoft sign-in could not be completed. Please try again or use your password.');
        }

        return $mode === 'link'
            ? $this->linkCurrentUser($claims)
            : $this->signIn($request, $claims, $requestId);
    }

    /** @return array<string, mixed> klaim ID token yang sudah diperiksa */
    private function exchangeCode(string $code, array $ctx, string $requestId): array
    {
        $response = Http::asForm()->timeout(15)->post(
            'https://login.microsoftonline.com/' . rawurlencode(MicrosoftLoginSettings::tenantId()) . '/oauth2/v2.0/token',
            [
                'client_id'     => MicrosoftLoginSettings::clientId(),
                'client_secret' => MicrosoftLoginSettings::clientSecret(),
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => MicrosoftLoginSettings::redirectUri(),
                'code_verifier' => (string) ($ctx['verifier'] ?? ''),
                'scope'         => self::SCOPES,
            ]
        );

        if (!$response->successful()) {
            // Penyebab tersering: secret salah/kedaluwarsa, redirect URI belum
            // didaftarkan. Ditulis ke log (tanpa secret) agar admin bisa melacak.
            Log::channel('daily')->error('Microsoft token endpoint refused the code', [
                'request_id'  => $requestId,
                'status'      => $response->status(),
                'error'       => $response->json('error'),
                'description' => Str::limit((string) $response->json('error_description'), 300),
            ]);

            throw new \RuntimeException('Token endpoint returned HTTP ' . $response->status());
        }

        return MicrosoftIdToken::claims(
            (string) $response->json('id_token'),
            MicrosoftLoginSettings::tenantId(),
            MicrosoftLoginSettings::clientId(),
            (string) ($ctx['nonce'] ?? '')
        );
    }

    // ── Login ───────────────────────────────────────────────────────────────

    private function signIn(Request $request, array $claims, string $requestId): RedirectResponse
    {
        $oid   = strtolower((string) $claims['oid']);
        $email = strtolower(trim((string) ($claims['email'] ?? $claims['preferred_username'] ?? '')));
        $deny  = 'This Microsoft account cannot sign in to EcoSystem. Please contact your administrator.';

        $loginSecurity = app(LoginSecurityService::class);
        if ($loginSecurity->isIpBlocked($request->ip())) {
            return redirect()->route('login')->with('error', 'Too many failed attempts. Please try again later.');
        }

        $authUser    = DB::table('auth_users')->where('microsoft_oid', $oid)->first();
        $needsLinking = false;

        if (!$authUser && $email !== '') {
            $authUser = DB::table('auth_users')->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($authUser && !empty($authUser->microsoft_oid)) {
                // Akun ini sudah tertaut ke akun Microsoft LAIN. Jangan dialihkan diam-diam.
                $authUser = null;
            } else {
                $needsLinking = true;
            }
        }

        if (!$authUser || !$authUser->is_active) {
            AuthController::recordActivity(
                userId:    0,
                roleId:    0,
                userName:  $email !== '' ? $email : 'Microsoft account',
                userType:  'employee',
                ip:        $request->ip(),
                userAgent: $request->userAgent() ?? '',
                status:    'failed'
            );

            return redirect()->route('login')->with('error', $deny);
        }

        // Customer memakai portal Jarvies. Akun tanpa karyawan juga tidak bisa masuk.
        if (!is_null($authUser->customer_id)) {
            return redirect()->route('login')->with('error', 'Access denied. Please use the Jarvies customer portal.');
        }
        if (is_null($authUser->employee_id)) {
            return redirect()->route('login')->with('error', $deny);
        }

        if ($loginSecurity->isAccountLocked($authUser)) {
            return redirect()->route('login')->with('error', 'Too many failed attempts. Please try again later.');
        }

        // Akun baru wajib menyelesaikan penyiapan password lebih dulu (sama
        // seperti login password); alur Microsoft tidak mengirim email itu.
        if (!$authUser->is_already_cp) {
            return redirect()->route('login')->with('error', 'Your account setup is not finished yet. Use the sign-in form with your email to receive the setup email first.');
        }

        if ($needsLinking) {
            try {
                DB::table('auth_users')->where('id', $authUser->id)->update([
                    'microsoft_oid'       => $oid,
                    'microsoft_email'     => $email !== '' ? $email : null,
                    'microsoft_linked_at' => now(),
                ]);
            } catch (Throwable $e) {
                Log::channel('daily')->error('Microsoft auto-link failed', [
                    'request_id' => $requestId,
                    'error'      => $e->getMessage(),
                ]);

                return redirect()->route('login')->with('error', $deny);
            }

            $this->audit($authUser->id, 'linked Microsoft account on first Microsoft sign-in', null, ['microsoft_email' => $email], $authUser->employee_id);
        }

        $response = app(AuthController::class)->completeEmployeeLogin($authUser, false, $request, $requestId, $email);
        $data     = $response instanceof JsonResponse ? $response->getData(true) : [];

        if (empty($data['success'])) {
            return redirect()->route('login')->with('error', (string) ($data['message'] ?? 'Sign in failed. Please try again.'));
        }

        if (!empty($data['requires_2fa'])) {
            return redirect()->to('/auth/2fa/verify?token=' . rawurlencode((string) $data['two_factor_token']));
        }

        return redirect()->to(IntendedUrl::sanitize($data['redirect_url'] ?? null, $request) ?? IntendedUrl::FALLBACK);
    }

    // ── Tautkan / lepas akun (Settings > Security) ──────────────────────────

    private function linkCurrentUser(array $claims): RedirectResponse
    {
        $employeeId = $this->currentEmployeeId();
        if ($employeeId === null) {
            return redirect()->route('login')->with('error', 'Please sign in first.');
        }

        $oid   = strtolower((string) $claims['oid']);
        $email = strtolower(trim((string) ($claims['email'] ?? $claims['preferred_username'] ?? '')));

        $mine = DB::table('auth_users')->where('employee_id', $employeeId)->first();
        if (!$mine) {
            return $this->backToSettings('error', 'Your account could not be found.');
        }

        $owner = DB::table('auth_users')->where('microsoft_oid', $oid)->where('id', '!=', $mine->id)->exists();
        if ($owner) {
            return $this->backToSettings('error', 'This Microsoft account is already connected to another EcoSystem user.');
        }

        $before = ['microsoft_email' => $mine->microsoft_email];

        DB::table('auth_users')->where('id', $mine->id)->update([
            'microsoft_oid'       => $oid,
            'microsoft_email'     => $email !== '' ? $email : null,
            'microsoft_linked_at' => now(),
        ]);

        $this->audit($mine->id, 'connected a Microsoft account', $before, ['microsoft_email' => $email], $employeeId);

        return $this->backToSettings('success', 'Microsoft account connected.');
    }

    /** POST /settings/microsoft/disconnect */
    public function disconnect(): JsonResponse
    {
        $employeeId = $this->currentEmployeeId();
        $mine = $employeeId !== null
            ? DB::table('auth_users')->where('employee_id', $employeeId)->first()
            : null;

        if (!$mine) {
            return response()->json(['success' => false, 'message' => 'Your account could not be found.'], 404);
        }

        if (empty($mine->microsoft_oid)) {
            return response()->json(['success' => false, 'message' => 'No Microsoft account is connected.'], 422);
        }

        DB::table('auth_users')->where('id', $mine->id)->update([
            'microsoft_oid'       => null,
            'microsoft_email'     => null,
            'microsoft_linked_at' => null,
        ]);

        $this->audit($mine->id, 'disconnected the Microsoft account', ['microsoft_email' => $mine->microsoft_email], null, $employeeId);

        return response()->json(['success' => true, 'message' => 'Microsoft account disconnected.']);
    }

    // ── Konfigurasi (khusus EC Administrator) ───────────────────────────────

    /** POST /settings/microsoft/config */
    public function saveConfig(Request $request): JsonResponse
    {
        if (!MicrosoftLoginSettings::canManage()) {
            return response()->json(['success' => false, 'message' => 'Only an EC Administrator can change this.'], 403);
        }

        $data = $request->validate([
            'tenant_id'     => ['nullable', 'string', 'max:64'],
            'client_id'     => ['nullable', 'string', 'max:64'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'enabled'       => ['nullable', 'boolean'],
        ]);

        $tenant  = trim((string) ($data['tenant_id'] ?? ''));
        $client  = trim((string) ($data['client_id'] ?? ''));
        $secret  = trim((string) ($data['client_secret'] ?? ''));
        $enabled = (bool) ($data['enabled'] ?? false);

        foreach (['Directory (tenant) ID' => $tenant, 'Application (client) ID' => $client] as $label => $value) {
            if ($value !== '' && !MicrosoftLoginSettings::isValidGuid($value)) {
                return response()->json(['success' => false, 'message' => "$label must be a GUID like 00000000-0000-0000-0000-000000000000."], 422);
            }
        }

        // Kolom kosong jatuh balik ke app registration Graph; pastikan hasil
        // akhirnya lengkap sebelum login boleh diaktifkan.
        $effTenant = $tenant !== '' ? $tenant : trim((string) config('services.microsoft_graph.tenant_id'));
        $effClient = $client !== '' ? $client : trim((string) config('services.microsoft_graph.client_id'));
        $hasSecret = $secret !== ''
            || MicrosoftLoginSettings::hasStoredSecret()
            || trim((string) config('services.microsoft_graph.client_secret')) !== '';

        if ($enabled && (!MicrosoftLoginSettings::isValidGuid($effTenant) || !MicrosoftLoginSettings::isValidGuid($effClient) || !$hasSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'To enable Microsoft 365 login, fill in the tenant ID, the client ID and the client secret.',
            ], 422);
        }

        $before = [
            'enabled'   => MicrosoftLoginSettings::enabled(),
            'tenant_id' => MicrosoftLoginSettings::storedTenantId(),
            'client_id' => MicrosoftLoginSettings::storedClientId(),
        ];

        $result = MicrosoftLoginSettings::save($enabled, $tenant, $client, $secret);

        // Nilai secret TIDAK PERNAH masuk audit log, hanya fakta bahwa secret diganti.
        AuditLog::recordAction(
            module: 'Security',
            auditableType: 'AppConfig',
            auditableId: (int) (\App\Models\AppConfig::where('key', MicrosoftLoginSettings::KEY)->value('id') ?? 0),
            event: 'updated',
            recordLabel: 'Microsoft 365 login',
            description: 'updated Microsoft 365 login configuration' . ($result['secret_changed'] ? ' (client secret replaced)' : ''),
            old: $before,
            new: ['enabled' => $enabled, 'tenant_id' => $tenant, 'client_id' => $client],
        );

        return response()->json([
            'success' => true,
            'message' => 'Microsoft 365 configuration saved.',
            'data'    => [
                'enabled'     => $enabled,
                'has_secret'  => MicrosoftLoginSettings::hasStoredSecret(),
                'available'   => MicrosoftLoginSettings::loginAvailable(),
            ],
        ]);
    }

    // ── Pembantu ────────────────────────────────────────────────────────────

    private function currentEmployeeId(): ?int
    {
        $user = session('user');

        return (is_array($user) && ($user['type'] ?? null) === 'employee' && !empty($user['id']))
            ? (int) $user['id']
            : null;
    }

    private function backToSettings(string $type, string $message): RedirectResponse
    {
        return redirect()->route('settings.index', ['tab' => 'security'])->with('ms_flash', ['type' => $type, 'message' => $message]);
    }

    private function audit(int $authUserId, string $description, ?array $old, ?array $new, int $employeeId): void
    {
        $name = DB::table('employee_basic_data')->where('employee_id', $employeeId)->value('first_name');

        AuditLog::recordAction(
            module: 'Security',
            auditableType: 'AuthUser',
            auditableId: $authUserId,
            event: 'updated',
            recordLabel: $name ?: "User #{$employeeId}",
            description: $description,
            old: $old,
            new: $new,
        );
    }
}
