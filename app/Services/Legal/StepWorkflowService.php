<?php

namespace App\Services\Legal;

use App\Models\Notification;
use App\Services\Approval\ApprovalEngine;
use App\Support\ApprovalRecipients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mesin langkah persetujuan bertingkat yang dipakai Sales Compliance Approval dan Vendor Onboarding.
 *
 * Bentuknya sama dengan Purchase Request (konfigurasi langkah → salinan per dokumen → approve/reject berurutan), tetapi ditulis
 * SEKALI untuk dua modul yang memang identik, bukan disalin dua kali. Perbedaan modul (model, kolom kunci, status akhir, jejak
 * log, penerima notifikasi) disodorkan lewat method abstrak di bawah.
 *
 * Aturan yang ditegakkan di sini, bukan di controller:
 *  - hanya dokumen terbuka (`isOpen`) dan langkah yang SEDANG menunggu yang boleh diputuskan;
 *  - pemutus harus tercantum pada langkah itu (role / nama) — atau pemegang slug `.approve` (`$canManage`);
 *  - pengaju tidak boleh memutuskan dokumennya sendiri, termasuk pemegang slug `.approve`: tinjauan compliance atas deal
 *    sendiri meniadakan tujuannya (tidak ada pengaturan untuk melonggarkan ini);
 *  - menolak satu langkah menutup seluruh dokumen; langkah sisanya ditandai `skipped`.
 */
abstract class StepWorkflowService
{
    /** @return class-string<Model> */
    abstract protected function approvalModel(): string;

    /** @return class-string<Model> */
    abstract protected function stepModel(): string;

    /** @return class-string<Model> */
    abstract protected function documentModel(): string;

    /** Kolom kunci di tabel salinan langkah yang menunjuk dokumen (`request_id` / `registration_id`). */
    abstract protected function foreignKey(): string;

    /** Nilai `module` pada langkah konfigurasi. */
    abstract protected function moduleKey(): string;

    /** Status dokumen setelah semua langkah disetujui (`approved` / `verified`). */
    abstract protected function approvedStatus(): string;

    /** Nama modul untuk teks notifikasi, mis. "Sales compliance request". */
    abstract protected function label(): string;

    /** Nomor dokumen yang tampil di notifikasi. */
    abstract protected function numberOf(Model $document): string;

    /** Karyawan yang diberi tahu hasilnya, bila ada (pengaju). */
    abstract protected function requesterOf(Model $document): ?int;

    /** Link halaman dokumen untuk pengaju. */
    abstract protected function linkFor(Model $document): string;

    /** Catat perpindahan status pada jejak modul. */
    abstract protected function logTransition(Model $document, ?string $from, string $to, ?string $note, ?int $actorId): void;

    /**
     * Apakah memutuskan dokumen ini sama dengan memutuskan dokumen sendiri (dilarang). Bawaannya: pengaju. Vendor Onboarding
     * menimpanya menjadi false — staf yang mengetik data vendor bukan pihak yang berkepentingan atas vendornya.
     */
    protected function decidesOwnDocument(Model $document, int $actorId): bool
    {
        return $actorId > 0 && $this->requesterOf($document) === $actorId;
    }

    /** Kunci tipe notifikasi, mis. `sales_compliance`. */
    protected function notificationPrefix(): string
    {
        return $this->moduleKey();
    }

    /** Langkah KONFIGURASI yang berlaku untuk dokumen baru. */
    public function activeSteps(): Collection
    {
        $step = $this->stepModel();

        return $step::query()->where('module', $this->moduleKey())->where('is_active', true)->orderBy('order_seq')->get();
    }

    /**
     * Mulai tinjauan: salin langkah aktif ke dokumen pada putaran ini lalu letakkan dokumen di langkah pertama.
     *
     * @return array{ok: bool, reason: string}
     */
    protected function beginReview(Model $document, string $fromStatus, ?int $actorId, ?string $note = null): array
    {
        $steps = $this->activeSteps();
        if ($steps->isEmpty()) {
            return ['ok' => false, 'reason' => 'No active approval step is configured. Add one in Settings first.'];
        }

        $approval = $this->approvalModel();

        DB::transaction(function () use ($document, $steps, $approval, $fromStatus, $actorId, $note) {
            // Idempoten: bila putaran ini sudah punya salinan (mis. permintaan ganda), jangan ditulis dua kali.
            $document->approvals()->where('round', $document->round)->delete();

            foreach ($steps as $step) {
                $approval::create([
                    $this->foreignKey()     => $document->getKey(),
                    'round'                 => $document->round,
                    'order_seq'             => $step->order_seq,
                    'step_name'             => $step->name,
                    'approver_type'         => $step->approver_type,
                    'approver_role_id'      => $step->approver_role_id,
                    'approver_employee_ids' => $step->approver_employee_ids,
                    'chosen_by_requester'   => false,
                    'status'                => 'waiting',
                ]);
            }

            $document->update([
                'status'             => 'in_review',
                'current_step_order' => $steps->first()->order_seq,
                'completed_at'       => null,
            ]);

            $this->logTransition($document, $fromStatus, 'in_review', $note, $actorId);
        });

        $document->load('approvals');
        $first = $document->currentApproval();
        if ($first) {
            $this->notifyApprovers($document, $first, true);
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Boleh tidaknya orang ini memutuskan dokumen sekarang.
     *
     * @return array{allowed: bool, reason: string, approval: ?Model}
     */
    public function canAct(Model $document, int $actorId, bool $canManage = false): array
    {
        $document->loadMissing('approvals');

        if (!$document->isOpen()) {
            return ['allowed' => false, 'reason' => 'This document is not under review.', 'approval' => null];
        }

        $approval = $document->currentApproval();
        if (!$approval || !$approval->isWaiting()) {
            return ['allowed' => false, 'reason' => 'No step is waiting for a decision.', 'approval' => null];
        }

        if ($this->decidesOwnDocument($document, $actorId)) {
            return ['allowed' => false, 'reason' => 'You cannot decide a request you submitted yourself.', 'approval' => null];
        }

        $named = $approval->allows($actorId, app(ApprovalEngine::class)->roleIdsOf($actorId));
        if (!$named && !$canManage) {
            return ['allowed' => false, 'reason' => 'You are not an approver of the current step.', 'approval' => null];
        }

        return ['allowed' => true, 'reason' => '', 'approval' => $approval];
    }

    /** @return array{allowed: bool, reason: string, completed: bool} */
    public function approve(Model $document, int $actorId, ?string $notes = null, bool $canManage = false): array
    {
        $gate = $this->canAct($document, $actorId, $canManage);
        if (!$gate['allowed']) {
            return ['allowed' => false, 'reason' => $gate['reason'], 'completed' => false];
        }

        $approval = $gate['approval'];

        $completed = DB::transaction(function () use ($document, $approval, $actorId, $notes) {
            $approval->update(['status' => 'approved', 'acted_by' => $actorId, 'acted_at' => now(), 'notes' => $notes]);

            $next = $document->approvals()
                ->where('round', $document->round)
                ->where('order_seq', '>', $approval->order_seq)
                ->where('status', 'waiting')
                ->orderBy('order_seq')
                ->first();

            if ($next) {
                $document->update(['current_step_order' => $next->order_seq]);
                $this->logTransition($document, 'in_review', 'in_review', 'Approved at "' . $approval->step_name . '"' . ($notes ? ': ' . $notes : ''), $actorId);

                return false;
            }

            $document->update(['status' => $this->approvedStatus(), 'current_step_order' => null, 'completed_at' => now()]);
            $this->logTransition($document, 'in_review', $this->approvedStatus(), $notes, $actorId);

            return true;
        });

        $document->load('approvals');
        $this->notifyRequester($document, $completed ? 'approved' : 'progressed', $notes);
        if (!$completed && ($next = $document->currentApproval())) {
            $this->notifyApprovers($document, $next, false);
        }

        return ['allowed' => true, 'reason' => '', 'completed' => $completed];
    }

    /** @return array{allowed: bool, reason: string} */
    public function reject(Model $document, int $actorId, string $notes, bool $canManage = false): array
    {
        if (trim($notes) === '') {
            return ['allowed' => false, 'reason' => 'A note is required when rejecting.'];
        }

        $gate = $this->canAct($document, $actorId, $canManage);
        if (!$gate['allowed']) {
            return ['allowed' => false, 'reason' => $gate['reason']];
        }

        $approval = $gate['approval'];

        DB::transaction(function () use ($document, $approval, $actorId, $notes) {
            $approval->update(['status' => 'rejected', 'acted_by' => $actorId, 'acted_at' => now(), 'notes' => $notes]);

            $document->approvals()
                ->where('round', $document->round)
                ->where('order_seq', '>', $approval->order_seq)
                ->where('status', 'waiting')
                ->update(['status' => 'skipped']);

            $document->update(['status' => 'rejected', 'current_step_order' => null, 'completed_at' => now()]);
            $this->logTransition($document, 'in_review', 'rejected', $notes, $actorId);
        });

        $this->notifyRequester($document, 'rejected', $notes);

        return ['allowed' => true, 'reason' => ''];
    }

    /** Tandai langkah yang belum dijalani sebagai `skipped` (dokumen dibatalkan). */
    protected function skipOpenSteps(Model $document): void
    {
        $document->approvals()->where('round', $document->round)->where('status', 'waiting')->update(['status' => 'skipped']);
    }

    /** @return array<int> id dokumen yang sedang menunggu orang ini (dipakai Command Center). */
    public function pendingIdsFor(int $actorId): array
    {
        $roleIds = app(ApprovalEngine::class)->roleIdsOf($actorId);
        $model   = $this->documentModel();

        return $model::query()->where('status', 'in_review')->with('approvals')->get()
            ->filter(function (Model $document) use ($actorId, $roleIds) {
                $current = $document->currentApproval();

                return $current && $current->isWaiting()
                    && !$this->decidesOwnDocument($document, $actorId)
                    && $current->allows($actorId, $roleIds);
            })
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function notifyRequester(Model $document, string $outcome, ?string $notes): void
    {
        try {
            $employeeId = $this->requesterOf($document);
            if (!$employeeId) {
                return;
            }

            $number  = $this->numberOf($document);
            $message = match ($outcome) {
                'approved'   => "Your {$this->label()} {$number} was approved.",
                'rejected'   => "Your {$this->label()} {$number} was rejected.",
                'progressed' => "Your {$this->label()} {$number} passed a review step.",
                default      => "Your {$this->label()} {$number} was updated.",
            };

            Notification::create([
                'employee_id'      => $employeeId,
                'type'             => $this->notificationPrefix() . '_' . $outcome,
                'from_employee_id' => session('user.id'),
                'preview'          => $message . ($notes ? ' Note: ' . $notes : ''),
                'link'             => $this->linkFor($document),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to notify the requester of a legal workflow document.', ['id' => $document->getKey(), 'message' => $e->getMessage()]);
        }
    }

    protected function notifyApprovers(Model $document, Model $step, bool $isFirstStep): void
    {
        try {
            $number  = $this->numberOf($document);
            $message = $isFirstStep
                ? ucfirst($this->label()) . " {$number} needs your review."
                : ucfirst($this->label()) . " {$number} passed the previous step and now needs your review.";

            foreach (ApprovalRecipients::forStep($step) as $employeeId) {
                if ($this->decidesOwnDocument($document, $employeeId)) {
                    continue; // pengaju tidak memutuskan dokumennya sendiri
                }
                Notification::create([
                    'employee_id'      => $employeeId,
                    'type'             => $this->notificationPrefix() . '_pending_approval',
                    'from_employee_id' => $this->requesterOf($document),
                    'preview'          => $message,
                    'link'             => '/general/approvals',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify the approvers of a legal workflow document.', ['id' => $document->getKey(), 'message' => $e->getMessage()]);
        }
    }

    /** Nomor urut berikutnya per tahun, mis. SCA-2026-0007. */
    protected function nextNumber(string $prefix, string $table, string $column): string
    {
        $year   = now()->format('Y');
        $stem   = "{$prefix}-{$year}-";
        $latest = DB::table($table)->where($column, 'like', $stem . '%')->orderByDesc($column)->value($column);
        $next   = $latest ? ((int) substr($latest, strlen($stem))) + 1 : 1;

        return $stem . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
