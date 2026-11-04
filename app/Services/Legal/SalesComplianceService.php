<?php

namespace App\Services\Legal;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Legal\SalesCompliance\SalesComplianceAnswer;
use App\Models\Legal\SalesCompliance\SalesComplianceApproval;
use App\Models\Legal\SalesCompliance\SalesComplianceApprovalStep;
use App\Models\Legal\SalesCompliance\SalesComplianceAttachment;
use App\Models\Legal\SalesCompliance\SalesComplianceLog;
use App\Models\Legal\SalesCompliance\SalesComplianceQuestion;
use App\Models\Legal\SalesCompliance\SalesComplianceRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sales Compliance Approval: alur dari draft sampai keputusan compliance.
 *
 * Aturan persetujuannya ada di {@see StepWorkflowService}; kelas ini memegang yang khas deal sales — kelengkapan sebelum
 * diajukan, jawaban pertanyaan, lampiran harga/MRP, dan pembukaan kembali dokumen yang ditolak.
 */
class SalesComplianceService extends StepWorkflowService
{
    /** Disk privat: lampiran berisi harga kontrak, tidak boleh punya URL publik. */
    public const DISK = 'local';

    public const ATTACHMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const ATTACHMENT_MAX_KB     = 10240; // 10 MB, sama dengan portal lama

    protected function approvalModel(): string { return SalesComplianceApproval::class; }
    protected function stepModel(): string { return SalesComplianceApprovalStep::class; }
    protected function documentModel(): string { return SalesComplianceRequest::class; }
    protected function foreignKey(): string { return 'request_id'; }
    protected function moduleKey(): string { return SalesComplianceApprovalStep::MODULE; }
    protected function approvedStatus(): string { return SalesComplianceRequest::STATUS_APPROVED; }
    protected function label(): string { return 'sales compliance request'; }
    protected function numberOf(Model $document): string { return (string) $document->request_no; }
    protected function requesterOf(Model $document): ?int { return $document->employee_id ? (int) $document->employee_id : null; }
    protected function linkFor(Model $document): string { return route('legal.sales-compliance.show', $document->getKey(), false); }

    protected function logTransition(Model $document, ?string $from, string $to, ?string $note, ?int $actorId): void
    {
        SalesComplianceLog::create([
            'request_id' => $document->getKey(), 'from_status' => $from, 'to_status' => $to, 'note' => $note,
            'actor_id' => $actorId, 'actor_name' => $actorId ? $this->actorName($actorId) : null,
        ]);
    }

    public function questions(): Collection
    {
        return SalesComplianceQuestion::active()->get();
    }

    /**
     * Simpan draft (buat baru bila $request null). Hanya draft yang boleh diubah.
     *
     * @param  array<string,mixed>  $data     kolom dokumen
     * @param  array<int,mixed>     $answers  question_id => jawaban (string, atau array untuk checkbox)
     * @return array{ok: bool, reason: string, request: ?SalesComplianceRequest}
     */
    public function saveDraft(?SalesComplianceRequest $request, array $data, array $answers, ?int $createdBy = null): array
    {
        if ($request && !$request->isEditable()) {
            return ['ok' => false, 'reason' => 'Only a draft can be edited.', 'request' => $request];
        }

        $data['customer_name'] = $this->resolveCustomerName($data);

        $request = DB::transaction(function () use ($request, $data, $answers, $createdBy) {
            if ($request) {
                $request->update($data);
            } else {
                $request = SalesComplianceRequest::create($data + [
                    'request_no' => $this->nextNumber('SCA', 'sales_compliance_requests', 'request_no'),
                    'status'     => SalesComplianceRequest::STATUS_DRAFT,
                    'created_by' => $createdBy && $createdBy !== (int) $data['employee_id'] ? $createdBy : null,
                ]);
                $this->logTransition($request, null, 'draft', 'Draft created', $createdBy ?? (int) $data['employee_id']);
            }

            $this->saveAnswers($request, $answers);

            return $request;
        });

        return ['ok' => true, 'reason' => '', 'request' => $request];
    }

    /** Bagian yang belum memenuhi syarat untuk diajukan; kosong = siap. @return string[] */
    public function readiness(SalesComplianceRequest $request): array
    {
        $missing = [];
        $blank   = fn ($v) => $v === null || trim((string) $v) === '';

        foreach (['project_name' => 'Project name', 'customer_name' => 'Customer', 'pic_name' => 'Customer PIC'] as $field => $label) {
            if ($blank($request->{$field})) {
                $missing[] = $label;
            }
        }
        if ($request->has_meeting && $blank($request->meeting_location)) {
            $missing[] = 'Meeting location';
        }
        if ($request->has_hospitality && $blank($request->hospitality_detail)) {
            $missing[] = 'Hospitality detail';
        }
        if ($request->final_price === null) {
            $missing[] = 'Final price';
        }
        if ($request->mrp_price === null) {
            $missing[] = 'MRP price';
        }
        $variance = $request->priceVariancePct();
        if ($variance !== null && $variance != 0.0 && $blank($request->price_justification)) {
            $missing[] = 'Price justification (the final price differs from the MRP)';
        }

        $types = $request->attachments()->pluck('file_type')->all();
        if (!in_array(SalesComplianceAttachment::TYPE_PRICE, $types, true)) {
            $missing[] = 'Agreed price document';
        }
        if (!in_array(SalesComplianceAttachment::TYPE_MRP, $types, true)) {
            $missing[] = 'MRP document';
        }

        $answers = $request->answers()->pluck('answer', 'question_id');
        foreach ($this->questions()->where('is_required', true) as $question) {
            $value = $answers[$question->id] ?? null;
            $empty = $value === null || $value === '' || $value === '[]';
            if ($empty) {
                $missing[] = 'Question ' . Str::limit($question->text_en, 60);
            }
        }

        return $missing;
    }

    /** @return array{ok: bool, reason: string, missing: string[]} */
    public function submit(SalesComplianceRequest $request, int $actorId): array
    {
        if (!$request->isEditable()) {
            return ['ok' => false, 'reason' => 'Only a draft can be submitted.', 'missing' => []];
        }

        $missing = $this->readiness($request);
        if ($missing) {
            return ['ok' => false, 'reason' => 'The request is not complete yet.', 'missing' => $missing];
        }

        $request->update(['submitted_at' => now()]);
        $started = $this->beginReview($request, 'draft', $actorId, 'Submitted for compliance review');

        return ['ok' => $started['ok'], 'reason' => $started['reason'], 'missing' => []];
    }

    /** Tolak → buka kembali sebagai draft pada putaran berikutnya (jejak putaran lama tetap). */
    public function reopen(SalesComplianceRequest $request, int $actorId): array
    {
        if ($request->status !== SalesComplianceRequest::STATUS_REJECTED) {
            return ['ok' => false, 'reason' => 'Only a rejected request can be reopened.'];
        }

        $request->update([
            'status' => SalesComplianceRequest::STATUS_DRAFT, 'round' => $request->round + 1,
            'current_step_order' => null, 'completed_at' => null,
        ]);
        $this->logTransition($request, 'rejected', 'draft', 'Reopened for correction (round ' . $request->round . ')', $actorId);

        return ['ok' => true, 'reason' => ''];
    }

    public function cancel(SalesComplianceRequest $request, int $actorId): array
    {
        if (!in_array($request->status, [SalesComplianceRequest::STATUS_DRAFT, SalesComplianceRequest::STATUS_IN_REVIEW], true)) {
            return ['ok' => false, 'reason' => 'This request can no longer be cancelled.'];
        }

        $from = $request->status;
        DB::transaction(function () use ($request, $from, $actorId) {
            $this->skipOpenSteps($request);
            $request->update(['status' => SalesComplianceRequest::STATUS_CANCELLED, 'current_step_order' => null, 'completed_at' => now()]);
            $this->logTransition($request, $from, 'cancelled', null, $actorId);
        });

        return ['ok' => true, 'reason' => ''];
    }

    /** Hapus draft beserta berkasnya. Dokumen yang pernah diajukan tidak dihapus — cukup dibatalkan. */
    public function deleteDraft(SalesComplianceRequest $request): array
    {
        if (!$request->isEditable() || $request->submitted_at !== null || $request->round > 1) {
            return ['ok' => false, 'reason' => 'Only a draft that was never submitted can be deleted.'];
        }

        foreach ($request->attachments as $attachment) {
            Storage::disk(self::DISK)->delete($attachment->path);
        }
        $request->delete(); // anak-anaknya ikut terhapus lewat FK cascade

        return ['ok' => true, 'reason' => ''];
    }

    public function addAttachment(SalesComplianceRequest $request, string $type, UploadedFile $file, int $actorId): SalesComplianceAttachment
    {
        $path = $file->storeAs(
            'sales-compliance/' . $request->getKey(),
            Str::random(40) . '.' . strtolower($file->getClientOriginalExtension()),
            self::DISK
        );

        return SalesComplianceAttachment::create([
            'request_id' => $request->getKey(), 'file_type' => $type, 'original_name' => $file->getClientOriginalName(),
            'path' => $path, 'size' => (int) $file->getSize(), 'mime' => $file->getClientMimeType(), 'uploaded_by' => $actorId,
        ]);
    }

    public function removeAttachment(SalesComplianceAttachment $attachment): void
    {
        Storage::disk(self::DISK)->delete($attachment->path);
        $attachment->delete();
    }

    /** @param array<int,mixed> $answers */
    private function saveAnswers(SalesComplianceRequest $request, array $answers): void
    {
        foreach ($this->questions() as $question) {
            if (!array_key_exists($question->id, $answers)) {
                continue;
            }
            $value = $answers[$question->id];
            $value = is_array($value) ? json_encode(array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''))) : trim((string) $value);

            SalesComplianceAnswer::updateOrCreate(
                ['request_id' => $request->getKey(), 'question_id' => $question->id],
                ['answer' => $value]
            );
        }
    }

    private function resolveCustomerName(array $data): string
    {
        $name = trim((string) ($data['customer_name'] ?? ''));
        if ($name === '' && !empty($data['customer_id'])) {
            $name = (string) Customer::with('basicData')->find($data['customer_id'])?->basicData?->name_1;
        }

        return $name;
    }

    private function actorName(int $actorId): string
    {
        return Employee::with('basicData')->find($actorId)?->fullNameLabel() ?: 'Employee #' . $actorId;
    }
}
