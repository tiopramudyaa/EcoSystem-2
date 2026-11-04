<?php

use App\Http\Controllers\Legal\SalesComplianceController;
use App\Http\Controllers\Legal\VendorOnboardingController;
use App\Http\Controllers\Legal\WorkflowSettingsController;
use App\Http\Middleware\CheckAuthToken;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * SALES COMPLIANCE APPROVAL dan VENDOR ONBOARDING (Business & Legal) — berkas rute BARU, aditif.
 * ============================================================================
 *   /legal/sales-compliance          Requests (V) · buat / ubah draft (C/E) · hapus draft (D) · setujui / tolak (slug `.approve` ATAU ditunjuk pada langkah)
 *   /legal/sales-compliance/settings Langkah persetujuan & pertanyaan (V) · C tambah · E ubah · D hapus langkah
 *   /legal/vendor-onboarding         Pendaftaran vendor (V) · buat / ubah (C/E) · hapus draft (D) · tinjau & verifikasi · buat Business Partner
 *   /legal/vendor-onboarding/settings Langkah persetujuan, pertanyaan, jenis dokumen
 * Seperti modul HR lain: HANYA GET dan POST. Rute statis (create, settings) ditaruh SEBELUM rute berparameter.
 * Dokumen & lampiran tidak punya URL publik — diunduh lewat controller yang memeriksa izin.
 */
Route::middleware(CheckAuthToken::class)->prefix('legal')->group(function () {

    // ── SALES COMPLIANCE ────────────────────────────────────────────────
    Route::prefix('sales-compliance')->name('legal.sales-compliance.')->group(function () {
        $page = 'legal.sales-compliance';
        $set = 'legal.sales-compliance.settings';
        $approve = 'legal.sales-compliance.approve';
        $can = fn (string $action) => ["menu:{$page}", "menu.can:{$page},{$action}"];
        $setCan = fn (string $action) => ["menu:{$set}", "menu.can:{$set},{$action}"];

        // Settings (statis) lebih dulu.
        Route::prefix('settings')->name('settings.')->group(function () use ($set, $setCan) {
            Route::get('/', [WorkflowSettingsController::class, 'index'])->name('index')->middleware("menu:{$set}");
            Route::post('/steps', [WorkflowSettingsController::class, 'storeStep'])->name('steps.store')->middleware($setCan('create'));
            Route::post('/questions', [WorkflowSettingsController::class, 'storeQuestion'])->name('questions.store')->middleware($setCan('create'));
            Route::whereNumber(['step', 'question'])->group(function () use ($setCan) {
                Route::post('/steps/{step}/update', [WorkflowSettingsController::class, 'updateStep'])->name('steps.update')->middleware($setCan('edit'));
                Route::post('/steps/{step}/delete', [WorkflowSettingsController::class, 'destroyStep'])->name('steps.destroy')->middleware($setCan('delete'));
                Route::post('/questions/{question}/update', [WorkflowSettingsController::class, 'updateQuestion'])->name('questions.update')->middleware($setCan('edit'));
                Route::post('/questions/{question}/toggle', [WorkflowSettingsController::class, 'toggleQuestion'])->name('questions.toggle')->middleware($setCan('edit'));
            });
        });

        Route::get('/', [SalesComplianceController::class, 'index'])->name('index')->middleware("menu:{$page},{$approve}");
        Route::get('/create', [SalesComplianceController::class, 'create'])->name('create')->middleware($can('create'));
        Route::post('/', [SalesComplianceController::class, 'store'])->name('store')->middleware($can('create'));

        // Lampiran: dibuka siapa pun yang boleh membuka dokumennya (diperiksa di controller).
        Route::get('/attachments/{attachment}', [SalesComplianceController::class, 'downloadAttachment'])->whereNumber('attachment')
            ->name('attachments.download')->middleware("menu.approver:sales-compliance,{$page}");
        Route::post('/attachments/{attachment}/delete', [SalesComplianceController::class, 'deleteAttachment'])->whereNumber('attachment')
            ->name('attachments.destroy')->middleware($can('edit'));

        Route::whereNumber('salesComplianceRequest')->group(function () use ($page, $approve, $can) {
            Route::get('/{salesComplianceRequest}', [SalesComplianceController::class, 'show'])->name('show')
                ->middleware("menu.approver:sales-compliance,{$page}");
            Route::get('/{salesComplianceRequest}/edit', [SalesComplianceController::class, 'edit'])->name('edit')->middleware($can('edit'));
            Route::post('/{salesComplianceRequest}/update', [SalesComplianceController::class, 'update'])->name('update')->middleware($can('edit'));
            Route::post('/{salesComplianceRequest}/submit', [SalesComplianceController::class, 'submit'])->name('submit')->middleware($can('edit'));
            Route::post('/{salesComplianceRequest}/attachments', [SalesComplianceController::class, 'uploadAttachment'])->name('attachments.store')->middleware($can('edit'));
            Route::post('/{salesComplianceRequest}/reopen', [SalesComplianceController::class, 'reopen'])->name('reopen')->middleware($can('edit'));
            Route::post('/{salesComplianceRequest}/cancel', [SalesComplianceController::class, 'cancel'])->name('cancel')->middleware($can('edit'));
            Route::post('/{salesComplianceRequest}/delete', [SalesComplianceController::class, 'destroy'])->name('destroy')->middleware($can('delete'));
            Route::post('/{salesComplianceRequest}/approve', [SalesComplianceController::class, 'approve'])->name('approve')
                ->middleware("menu.approver:sales-compliance,{$approve}");
            Route::post('/{salesComplianceRequest}/reject', [SalesComplianceController::class, 'reject'])->name('reject')
                ->middleware("menu.approver:sales-compliance,{$approve}");
        });
    });

    // ── VENDOR ONBOARDING ───────────────────────────────────────────────
    Route::prefix('vendor-onboarding')->name('legal.vendor-onboarding.')->group(function () {
        $page = 'legal.vendor-onboarding';
        $set = 'legal.vendor-onboarding.settings';
        $approve = 'legal.vendor-onboarding.approve';
        $can = fn (string $action) => ["menu:{$page}", "menu.can:{$page},{$action}"];
        $setCan = fn (string $action) => ["menu:{$set}", "menu.can:{$set},{$action}"];

        Route::prefix('settings')->name('settings.')->group(function () use ($set, $setCan) {
            Route::get('/', [WorkflowSettingsController::class, 'index'])->name('index')->middleware("menu:{$set}");
            Route::post('/steps', [WorkflowSettingsController::class, 'storeStep'])->name('steps.store')->middleware($setCan('create'));
            Route::post('/questions', [WorkflowSettingsController::class, 'storeQuestion'])->name('questions.store')->middleware($setCan('create'));
            Route::post('/fields', [WorkflowSettingsController::class, 'storeField'])->name('fields.store')->middleware($setCan('create'));
            Route::whereNumber(['step', 'question', 'field'])->group(function () use ($setCan) {
                Route::post('/steps/{step}/update', [WorkflowSettingsController::class, 'updateStep'])->name('steps.update')->middleware($setCan('edit'));
                Route::post('/steps/{step}/delete', [WorkflowSettingsController::class, 'destroyStep'])->name('steps.destroy')->middleware($setCan('delete'));
                Route::post('/questions/{question}/update', [WorkflowSettingsController::class, 'updateQuestion'])->name('questions.update')->middleware($setCan('edit'));
                Route::post('/questions/{question}/toggle', [WorkflowSettingsController::class, 'toggleQuestion'])->name('questions.toggle')->middleware($setCan('edit'));
                Route::post('/fields/{field}/update', [WorkflowSettingsController::class, 'updateField'])->name('fields.update')->middleware($setCan('edit'));
                Route::post('/fields/{field}/toggle', [WorkflowSettingsController::class, 'toggleField'])->name('fields.toggle')->middleware($setCan('edit'));
            });
        });

        Route::get('/', [VendorOnboardingController::class, 'index'])->name('index')->middleware("menu:{$page},{$approve}");
        Route::get('/create', [VendorOnboardingController::class, 'create'])->name('create')->middleware($can('create'));
        Route::post('/', [VendorOnboardingController::class, 'store'])->name('store')->middleware($can('create'));

        Route::get('/documents/{document}', [VendorOnboardingController::class, 'downloadDocument'])->whereNumber('document')
            ->name('documents.download')->middleware("menu.approver:vendor-onboarding,{$page}");
        Route::post('/documents/{document}/delete', [VendorOnboardingController::class, 'deleteDocument'])->whereNumber('document')
            ->name('documents.destroy')->middleware($can('edit'));

        Route::whereNumber('vendorRegistration')->group(function () use ($page, $approve, $can) {
            Route::get('/{vendorRegistration}', [VendorOnboardingController::class, 'show'])->name('show')
                ->middleware("menu.approver:vendor-onboarding,{$page}");
            Route::get('/{vendorRegistration}/edit', [VendorOnboardingController::class, 'edit'])->name('edit')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/update', [VendorOnboardingController::class, 'update'])->name('update')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/submit', [VendorOnboardingController::class, 'submit'])->name('submit')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/start-review', [VendorOnboardingController::class, 'startReview'])->name('start-review')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/reopen', [VendorOnboardingController::class, 'reopen'])->name('reopen')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/create-partner', [VendorOnboardingController::class, 'createPartner'])->name('create-partner')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/link-partner', [VendorOnboardingController::class, 'linkPartner'])->name('link-partner')->middleware($can('edit'));
            Route::post('/{vendorRegistration}/delete', [VendorOnboardingController::class, 'destroy'])->name('destroy')->middleware($can('delete'));
            Route::post('/{vendorRegistration}/approve', [VendorOnboardingController::class, 'approve'])->name('approve')
                ->middleware("menu.approver:vendor-onboarding,{$approve}");
            Route::post('/{vendorRegistration}/reject', [VendorOnboardingController::class, 'reject'])->name('reject')
                ->middleware("menu.approver:vendor-onboarding,{$approve}");
        });
    });
});
