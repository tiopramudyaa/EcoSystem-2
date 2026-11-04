<?php

use App\Http\Controllers\WebReport\ManagementController;
use App\Http\Controllers\WebReport\PublicController;
use App\Http\Middleware\CheckAuthToken;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * WEB REPORT — berkas rute BARU, aditif (dimuat web.php). Modul sendiri, terpisah dari Compliance (Legal).
 * ============================================================================
 *  PUBLIK (tanpa login, sengaja; tombolnya ada di halaman login): throttle + captcha sekali pakai + honeypot
 *   /web-report                 formulir laporan · /web-report/status cek status dengan kode pelacak
 *   /lapor                      alamat lama → diarahkan ke /web-report
 *  ADMIN (Control Center → Web Report Management, slug dari config/web-report.php) V baca · E ubah status / catatan (tidak ada C dan D)
 *   /admin/web-reports          inbox · /{id} detail · /{id}/evidence unduh bukti · /{id}/update simpan
 * Seperti modul lain: HANYA GET dan POST (aksi: /update). Rute statis ditaruh SEBELUM rute berparameter.
 */

// ---- PUBLIC (no login) — the only routes of the module outside CheckAuthToken ----
Route::redirect('/lapor', '/web-report');
Route::get('/web-report', [PublicController::class, 'form'])->name('web-report.form');
Route::post('/web-report', [PublicController::class, 'submit'])->name('web-report.submit')->middleware('throttle:' . config('web-report.throttle_submit'));
Route::match(['get', 'post'], '/web-report/status', [PublicController::class, 'status'])->name('web-report.status')->middleware('throttle:' . config('web-report.throttle_status'));

// ---- Control Center ----
Route::middleware(CheckAuthToken::class)->group(function () {
    Route::prefix('admin/web-reports')->name('admin.web-reports.')->group(function () {
        $slug = config('web-report.slug');

        Route::middleware("menu:{$slug}")->group(function () use ($slug) {
            Route::get('/', [ManagementController::class, 'index'])->name('index');
            Route::whereNumber('id')->group(function () use ($slug) {
                Route::get('/{id}', [ManagementController::class, 'show'])->name('show');
                Route::get('/{id}/evidence', [ManagementController::class, 'evidence'])->name('evidence');
                Route::post('/{id}/update', [ManagementController::class, 'update'])->name('update')->middleware("menu.can:{$slug},edit");
            });
        });
    });
});
