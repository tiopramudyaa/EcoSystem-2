<?php

use App\Http\Controllers\Legal\Compliance\ContentController;
use App\Http\Controllers\Legal\Compliance\DashboardController;
use App\Http\Controllers\Legal\Compliance\MyComplianceController;
use App\Http\Controllers\Legal\Compliance\MyReportController;
use App\Http\Controllers\Legal\Compliance\ReportController;
use App\Http\Controllers\Legal\Compliance\SettingsController;
use App\Http\Middleware\CheckAuthToken;
use App\Services\Compliance\ComplianceCatalog;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * LEGAL → COMPLIANCE — berkas rute BARU, aditif (dimuat web.php). Semua rute hanya setelah login.
 * ============================================================================
 *  My Compliance — SEMUA pengguna login, tanpa slug (menu "My Compliance" di My Workspace). Satu view per halaman: views/legal/compliance/my/
 *   /compliance                 index (home) · /knowledge · /article/{id} · /rules (Do & Don't) · /training · /search · /report (Report a Concern: kirim laporan + status laporan sendiri)
 *  Compliance Management — Legal, slug dari config/compliance.php (V buka · C tambah · E ubah · D hapus). Views: views/legal/compliance/management/
 *   /legal/compliance           → diarahkan ke Reports
 *   /dashboard · /reports (daftar) · /reports/{id} (detail & tindak lanjut) · /reports/export · /content (4 daftar) · /settings (status penanganan, batas tindak lanjut, kategori)
 * Web Report (laporan tanpa login + inbox admin) adalah modul LAIN: routes/web-report.php.
 * Seperti modul lain: HANYA GET dan POST (aksi: /update, /delete). Rute statis ditaruh SEBELUM rute berparameter.
 */
Route::middleware(CheckAuthToken::class)->group(function () {

    Route::prefix('compliance')->name('compliance.')->group(function () {
        Route::get('/', [MyComplianceController::class, 'index'])->name('index');
        Route::get('/knowledge', [MyComplianceController::class, 'knowledge'])->name('knowledge');
        Route::get('/rules', [MyComplianceController::class, 'rules'])->name('rules');
        Route::get('/training', [MyComplianceController::class, 'training'])->name('training');
        Route::get('/search', [MyComplianceController::class, 'search'])->name('search');
        Route::get('/report', [MyReportController::class, 'index'])->name('report');
        Route::post('/report', [MyReportController::class, 'store'])->name('report.store')->middleware('throttle:' . config('web-report.throttle_submit'));
        Route::get('/article/{id}', [MyComplianceController::class, 'article'])->name('article')->whereNumber('id');
    });

    Route::prefix('legal/compliance')->name('compliance.manage.')->group(function () {
        $slug = config('compliance.slugs.manage');
        $view = "menu:{$slug}";
        $gate = fn (string $action) => [$view, "menu.can:{$slug},{$action}"];
        $content = implode('|', array_diff(ComplianceCatalog::SECTIONS, ComplianceCatalog::SETTINGS));
        $settings = implode('|', ComplianceCatalog::SETTINGS);

        Route::redirect('/', '/legal/compliance/reports')->name('index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware($view);

        // Reports — the table, its export and one report
        Route::get('/reports', [ReportController::class, 'index'])->name('reports')->middleware($view);
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports-export')->middleware([$view, 'throttle:20,1']);
        Route::whereNumber('id')->group(function () use ($view, $gate) {
            Route::get('/reports/{id}', [ReportController::class, 'show'])->name('report')->middleware($view);
            Route::get('/reports/{id}/evidence', [ReportController::class, 'evidence'])->name('report-evidence')->middleware($view);
            Route::post('/reports/{id}/update', [ReportController::class, 'update'])->name('report-update')->middleware($gate('edit'));
        });

        // Content and Settings — editable lists
        foreach ([['content', ContentController::class, $content], ['settings', SettingsController::class, $settings]] as [$tab, $controller, $sections]) {
            Route::get("/{$tab}", [$controller, 'index'])->name($tab)->middleware($view);
            if ($tab === 'settings') {
                Route::post('/settings/follow-up', [SettingsController::class, 'followUp'])->name('settings.follow-up')->middleware($gate('edit'));
            }
            Route::post("/{$tab}/{section}", [$controller, 'store'])->name("{$tab}.store")->where('section', $sections)->middleware($gate('create'));
            Route::post("/{$tab}/{section}/{id}/update", [$controller, 'update'])->name("{$tab}.update")->where(['section' => $sections, 'id' => '[0-9]+'])->middleware($gate('edit'));
            Route::post("/{$tab}/{section}/reorder", [$controller, 'reorder'])->name("{$tab}.reorder")->where('section', $sections)->middleware($gate('edit'));
            Route::post("/{$tab}/{section}/{id}/toggle", [$controller, 'toggle'])->name("{$tab}.toggle")->where(['section' => $sections, 'id' => '[0-9]+'])->middleware($gate('edit'));
            Route::post("/{$tab}/{section}/{id}/delete", [$controller, 'destroy'])->name("{$tab}.destroy")->where(['section' => $sections, 'id' => '[0-9]+'])->middleware($gate('delete'));
        }
    });
});
