<?php

namespace App\Services\Legal;

use App\Models\Customer;
use App\Models\CustomerBank;
use App\Models\CustomerIdentification;
use App\Models\Employee;
use App\Models\Legal\VendorOnboarding\VendorAnswer;
use App\Models\Legal\VendorOnboarding\VendorApproval;
use App\Models\Legal\VendorOnboarding\VendorApprovalStep;
use App\Models\Legal\VendorOnboarding\VendorDocument;
use App\Models\Legal\VendorOnboarding\VendorDocumentField;
use App\Models\Legal\VendorOnboarding\VendorQuestion;
use App\Models\Legal\VendorOnboarding\VendorRegistration;
use App\Models\Legal\VendorOnboarding\VendorStatusLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Vendor Onboarding: pendaftaran vendor → tinjauan bertingkat → Business Partner bertipe Vendor.
 *
 * Aturan persetujuannya ada di {@see StepWorkflowService}. Dokumen identitas dan pajak (NPWP, akta, KTP direksi) HANYA disimpan
 * di disk privat modul ini; ke Business Partner hanya data profil, identifikasi, bank, dan kontak yang dipindah — tabel lampiran
 * Business Partner memakai disk publik dan tidak cocok untuk dokumen semacam itu.
 */
class VendorOnboardingService extends StepWorkflowService
{
    public const DISK = 'local';

    public const DOCUMENT_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const DOCUMENT_MAX_KB     = 51200; // 50 MB, sama dengan portal lama

    /** Kolom profil yang wajib lengkap sebelum diajukan, per tipe (sama dengan validasi portal lama) → label. */
    public const REQUIRED_B2B = [
        'company_name' => 'Company name', 'company_address' => 'Company address', 'company_phone' => 'Company phone',
        'company_email' => 'Company email', 'company_website' => 'Company website', 'npwp' => 'Tax ID (NPWP)', 'nib' => 'NIB',
        'business_type' => 'Business type', 'product_list' => 'Products / services', 'bank_name' => 'Bank name',
        'bank_account_no' => 'Bank account number', 'bank_account_name' => 'Bank account holder',
        'pic_name' => 'PIC name', 'pic_phone' => 'PIC phone', 'pic_email' => 'PIC email',
    ];

    public const REQUIRED_PERSONAL = [
        'company_name' => 'Full name', 'company_address' => 'Address', 'company_phone' => 'Phone', 'company_email' => 'Email',
        'company_website' => 'Website', 'npwp' => 'Tax ID (NPWP)', 'nib' => 'National ID number (NIK)',
        'business_type' => 'Expertise', 'product_list' => 'Services offered', 'bank_name' => 'Bank name',
        'bank_account_no' => 'Bank account number', 'bank_account_name' => 'Bank account holder',
    ];

    protected function approvalModel(): string { return VendorApproval::class; }
    protected function stepModel(): string { return VendorApprovalStep::class; }
    protected function documentModel(): string { return VendorRegistration::class; }
    protected function foreignKey(): string { return 'registration_id'; }
    protected function moduleKey(): string { return VendorApprovalStep::MODULE; }
    protected function approvedStatus(): string { return VendorRegistration::STATUS_VERIFIED; }
    protected function label(): string { return 'vendor registration'; }
    protected function numberOf(Model $document): string { return (string) $document->reference_no; }
    protected function requesterOf(Model $document): ?int { return $document->created_by ? (int) $document->created_by : null; }
    protected function linkFor(Model $document): string { return route('legal.vendor-onboarding.show', $document->getKey(), false); }

    /** Staf yang mengetik data vendor boleh ikut memverifikasinya; yang dibatasi hanya pengaju deal sales. */
    protected function decidesOwnDocument(Model $document, int $actorId): bool
    {
        return false;
    }

    protected function logTransition(Model $document, ?string $from, string $to, ?string $note, ?int $actorId): void
    {
        VendorStatusLog::create([
            'registration_id' => $document->getKey(), 'from_status' => $from, 'to_status' => $to, 'note' => $note,
            'actor_id' => $actorId, 'actor_name' => $actorId ? $this->actorName($actorId) : null,
        ]);
    }

    public function requiredProfileFields(string $vendorType): array
    {
        return $vendorType === VendorRegistration::TYPE_PERSONAL ? self::REQUIRED_PERSONAL : self::REQUIRED_B2B;
    }

    public function documentFields(string $vendorType): Collection
    {
        return VendorDocumentField::active()->forType($vendorType)->get();
    }

    public function questions(string $vendorType): Collection
    {
        return VendorQuestion::active()->forType($vendorType)->get();
    }

    /**
     * Simpan data profil dan jawaban (buat baru bila $registration null). Hanya yang belum masuk tinjauan yang boleh diubah.
     *
     * @return array{ok: bool, reason: string, registration: ?VendorRegistration}
     */
    public function saveProfile(?VendorRegistration $registration, array $data, array $answers, ?int $createdBy = null): array
    {
        if ($registration && !$registration->isEditable()) {
            return ['ok' => false, 'reason' => 'This registration is already under review and can no longer be edited.', 'registration' => $registration];
        }

        $registration = DB::transaction(function () use ($registration, $data, $answers, $createdBy) {
            if ($registration) {
                $registration->update($data);
            } else {
                $registration = VendorRegistration::create($data + [
                    'reference_no' => $this->nextNumber('VND', 'vendor_registrations', 'reference_no'),
                    'status'       => VendorRegistration::STATUS_DRAFT,
                    'source'       => 'internal',
                    'created_by'   => $createdBy,
                ]);
                $this->logTransition($registration, null, 'draft', 'Registration created', $createdBy);
            }

            $this->saveAnswers($registration, $answers);

            return $registration;
        });

        return ['ok' => true, 'reason' => '', 'registration' => $registration];
    }

    /** @return string[] bagian yang belum lengkap; kosong = siap diajukan */
    public function readiness(VendorRegistration $registration): array
    {
        $missing = [];

        foreach ($this->requiredProfileFields($registration->vendor_type) as $column => $label) {
            if (trim((string) $registration->{$column}) === '') {
                $missing[] = $label;
            }
        }

        $uploaded = $registration->documents()->pluck('field_id')->unique()->all();
        foreach ($this->documentFields($registration->vendor_type)->where('is_required', true) as $field) {
            if (!in_array($field->id, $uploaded, true)) {
                $missing[] = 'Document: ' . $field->label_en;
            }
        }

        $answers = $registration->answers()->pluck('answer', 'question_id');
        foreach ($this->questions($registration->vendor_type)->where('is_required', true) as $question) {
            $value = $answers[$question->id] ?? null;
            if ($value === null || $value === '' || $value === '[]') {
                $missing[] = 'Question: ' . Str::limit($question->text_en, 60);
            }
        }

        return $missing;
    }

    /** draft → submitted. @return array{ok: bool, reason: string, missing: string[]} */
    public function submit(VendorRegistration $registration, int $actorId): array
    {
        if ($registration->status !== VendorRegistration::STATUS_DRAFT) {
            return ['ok' => false, 'reason' => 'Only a draft can be submitted.', 'missing' => []];
        }

        $missing = $this->readiness($registration);
        if ($missing) {
            return ['ok' => false, 'reason' => 'The registration is not complete yet.', 'missing' => $missing];
        }

        DB::transaction(function () use ($registration, $actorId) {
            $registration->update(['status' => VendorRegistration::STATUS_SUBMITTED, 'submitted_at' => now()]);
            $this->logTransition($registration, 'draft', 'submitted', 'Registration submitted', $actorId);
        });

        return ['ok' => true, 'reason' => '', 'missing' => []];
    }

    /** submitted → in_review: langkah persetujuan disalin dan peninjau pertama diberi tahu. */
    public function startReview(VendorRegistration $registration, int $actorId): array
    {
        if ($registration->status !== VendorRegistration::STATUS_SUBMITTED) {
            return ['ok' => false, 'reason' => 'Only a submitted registration can be sent to review.'];
        }

        return $this->beginReview($registration, VendorRegistration::STATUS_SUBMITTED, $actorId, 'Review started');
    }

    /** Ditolak → draft pada putaran berikutnya, supaya datanya bisa dilengkapi lalu diajukan lagi. */
    public function reopen(VendorRegistration $registration, int $actorId): array
    {
        if ($registration->status !== VendorRegistration::STATUS_REJECTED) {
            return ['ok' => false, 'reason' => 'Only a rejected registration can be reopened.'];
        }

        $registration->update([
            'status' => VendorRegistration::STATUS_DRAFT, 'round' => $registration->round + 1,
            'current_step_order' => null, 'completed_at' => null,
        ]);
        $this->logTransition($registration, 'rejected', 'draft', 'Reopened for correction (round ' . $registration->round . ')', $actorId);

        return ['ok' => true, 'reason' => ''];
    }

    public function addDocument(VendorRegistration $registration, VendorDocumentField $field, UploadedFile $file, int $actorId): VendorDocument
    {
        $path = $file->storeAs(
            'vendor-onboarding/' . $registration->getKey(),
            Str::random(40) . '.' . strtolower($file->getClientOriginalExtension()),
            self::DISK
        );

        return VendorDocument::create([
            'registration_id' => $registration->getKey(), 'field_id' => $field->id, 'original_name' => $file->getClientOriginalName(),
            'path' => $path, 'size' => (int) $file->getSize(), 'mime' => $file->getClientMimeType(), 'uploaded_by' => $actorId,
        ]);
    }

    public function removeDocument(VendorDocument $document): void
    {
        Storage::disk(self::DISK)->delete($document->path);
        $document->delete();
    }

    /** Hapus pendaftaran draft yang belum pernah diajukan, beserta berkasnya. */
    public function deleteDraft(VendorRegistration $registration): array
    {
        if ($registration->status !== VendorRegistration::STATUS_DRAFT || $registration->submitted_at !== null || $registration->round > 1) {
            return ['ok' => false, 'reason' => 'Only a draft that was never submitted can be deleted.'];
        }

        foreach ($registration->documents as $document) {
            Storage::disk(self::DISK)->delete($document->path);
        }
        $registration->delete(); // dokumen, jawaban, dan jejak ikut terhapus lewat FK cascade

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Buat Business Partner bertipe Vendor dari pendaftaran yang sudah terverifikasi, lalu tautkan.
     *
     * Nilai yang sudah dipakai Business Partner lain (email, nomor identifikasi, nomor rekening — ketiganya unik di tabel
     * Business Partner) TIDAK ditimpa dan tidak menggagalkan proses: bagian itu dilewati dan dilaporkan di `notes`.
     *
     * @return array{ok: bool, reason: string, customer: ?Customer, notes: string[]}
     */
    public function createBusinessPartner(VendorRegistration $registration, int $actorId): array
    {
        if ($registration->status !== VendorRegistration::STATUS_VERIFIED) {
            return ['ok' => false, 'reason' => 'Only a verified vendor can become a Business Partner.', 'customer' => null, 'notes' => []];
        }
        if ($registration->customer_id) {
            return ['ok' => false, 'reason' => 'This vendor is already linked to a Business Partner.', 'customer' => null, 'notes' => []];
        }

        $notes = [];
        $actor = $this->actorName($actorId);

        $customer = DB::transaction(function () use ($registration, $actorId, $actor, &$notes) {
            $email = trim((string) $registration->company_email);
            if ($email !== '' && Customer::where('email', $email)->exists()) {
                $notes[] = "The email {$email} already belongs to another Business Partner, so it was left empty.";
                $email = '';
            }

            $customer = Customer::createWithBasicData(
                ['type' => Customer::TYPE_VENDOR, 'email' => $email !== '' ? $email : null, 'is_active' => true],
                [
                    'name_1' => $registration->company_name, 'search_term_1' => strtoupper($registration->company_name),
                    'created_by' => $actor, 'created_on' => now(), 'block' => false, 'deletion_flag' => false,
                ]
            );

            if (trim((string) $registration->company_address) !== '') {
                $customer->addresses()->create(['street' => Str::limit($registration->company_address, 250, ''), 'full_address' => $registration->company_address]);
            }

            $identifications = [
                ['NPWP', $registration->npwp],
                [$registration->isPersonal() ? 'NIK' : 'NIB', $registration->nib],
            ];
            foreach ($identifications as [$type, $number]) {
                $number = trim((string) $number);
                if ($number === '') {
                    continue;
                }
                if (CustomerIdentification::where('identification_number', $number)->exists()) {
                    $notes[] = "{$type} {$number} already exists on another Business Partner, so it was not copied.";
                    continue;
                }
                $customer->identifications()->create(['identification_type' => $type, 'identification_number' => $number]);
            }

            $account = trim((string) $registration->bank_account_no);
            if ($account !== '' && trim((string) $registration->bank_name) !== '') {
                if (CustomerBank::where('account_number', $account)->exists()) {
                    $notes[] = "Bank account {$account} already exists on another Business Partner, so it was not copied.";
                } else {
                    $customer->banks()->create([
                        'bank_name' => $registration->bank_name, 'account_number' => $account,
                        'account_holder' => $registration->bank_account_name,
                    ]);
                }
            }

            if (trim((string) $registration->pic_name) !== '') {
                $customer->contact()->create([
                    'full_name' => $registration->pic_name, 'cell_phone' => $registration->pic_phone ?: null,
                    'email_work' => $registration->pic_email ?: null,
                ]);
            }

            $registration->update(['customer_id' => $customer->customer_id]);
            $this->logTransition(
                $registration, 'verified', 'verified',
                'Business Partner ' . $customer->customer_code . ' created' . ($notes ? ' (' . count($notes) . ' note(s))' : ''),
                $actorId
            );

            return $customer;
        });

        return ['ok' => true, 'reason' => '', 'customer' => $customer, 'notes' => $notes];
    }

    /** Tautkan ke Business Partner bertipe Vendor yang SUDAH ada (tanpa membuat baru). */
    public function linkBusinessPartner(VendorRegistration $registration, int $customerId, int $actorId): array
    {
        if ($registration->status !== VendorRegistration::STATUS_VERIFIED || $registration->customer_id) {
            return ['ok' => false, 'reason' => 'Only a verified vendor without a link can be linked.'];
        }

        $customer = Customer::where('type', Customer::TYPE_VENDOR)->find($customerId);
        if (!$customer) {
            return ['ok' => false, 'reason' => 'Choose an existing Business Partner of type Vendor.'];
        }

        $registration->update(['customer_id' => $customer->customer_id]);
        $this->logTransition($registration, 'verified', 'verified', 'Linked to Business Partner ' . $customer->customer_code, $actorId);

        return ['ok' => true, 'reason' => ''];
    }

    /** @param array<int,mixed> $answers */
    private function saveAnswers(VendorRegistration $registration, array $answers): void
    {
        foreach ($this->questions($registration->vendor_type) as $question) {
            if (!array_key_exists($question->id, $answers)) {
                continue;
            }
            $value = $answers[$question->id];
            $value = is_array($value) ? json_encode(array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''))) : trim((string) $value);

            VendorAnswer::updateOrCreate(
                ['registration_id' => $registration->getKey(), 'question_id' => $question->id],
                ['answer' => $value]
            );
        }
    }

    private function actorName(int $actorId): string
    {
        return Employee::with('basicData')->find($actorId)?->fullNameLabel() ?: 'Employee #' . $actorId;
    }
}
