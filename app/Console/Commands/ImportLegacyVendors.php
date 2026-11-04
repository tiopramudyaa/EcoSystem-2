<?php

namespace App\Console\Commands;

use App\Models\Legal\VendorOnboarding\VendorAnswer;
use App\Models\Legal\VendorOnboarding\VendorDocument;
use App\Models\Legal\VendorOnboarding\VendorDocumentField;
use App\Models\Legal\VendorOnboarding\VendorQuestion;
use App\Models\Legal\VendorOnboarding\VendorRegistration;
use App\Models\Legal\VendorOnboarding\VendorStatusLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Impor SEKALI JALAN pendaftaran vendor dari portal lama (vendor.eclectic.co.id) ke Vendor Onboarding.
 *
 * Sumber: ekspor CSV dari phpMyAdmin database lama (kolom pertama = nama kolom) + folder `uploads/` portalnya.
 *   vendors.csv            (wajib)    id, email, company_name, vendor_type, status, … (kolom profil sama dengan tabel baru)
 *   vendor_documents.csv   (opsional) id, vendor_id, field_id, filename, original_name, file_size, mime_type
 *   vendor_doc_fields.csv  (opsional) id, label — dipakai memetakan jenis dokumen lama ke jenis baru lewat nama
 *   vendor_answers.csv     (opsional) vendor_id, question_id, answer
 *   vendor_questions.csv   (opsional) id, question — dipakai memetakan pertanyaan lewat teks
 *
 * Aturan:
 *  - hanya vendor yang dipilih (`--ids`, bawaan: vendor yang berkasnya terlihat nyata di paket analisis); vendor uji tidak diimpor;
 *  - IDEMPOTEN: vendor yang `legacy_vendor_id`-nya sudah ada dilewati, dan perintah ini TIDAK PERNAH menghapus atau menimpa baris;
 *  - status lama dipetakan ke status baru TANPA membuat langkah persetujuan: draft→draft, submitted/pending→submitted,
 *    verified→verified, rejected→rejected. Vendor yang ingin ditinjau ulang memakai alur biasa (Start review);
 *  - berkas disalin ke disk privat dengan nama acak; berkas yang tidak ada di folder uploads dilaporkan, bukan menggagalkan impor;
 *  - Business Partner TIDAK dibuat otomatis — itu keputusan peninjau di halaman vendor.
 *
 * Selalu jalankan dengan --dry-run lebih dulu.
 */
class ImportLegacyVendors extends Command
{
    protected $signature = 'vendors:import-legacy
        {--source= : Folder berisi vendors.csv dan ekspor lain}
        {--uploads= : Folder uploads/ portal lama (bila berkas ikut dipindah)}
        {--ids=13,20,25,27,28 : ID vendor lama yang diimpor, dipisah koma, atau "all"}
        {--dry-run : Hanya laporkan, tidak menulis apa pun}';

    protected $description = 'One-time import of vendor registrations (and their documents) from the old vendor.eclectic.co.id portal';

    /** Pemetaan status lama → baru. */
    private const STATUS_MAP = ['draft' => 'draft', 'submitted' => 'submitted', 'pending' => 'submitted', 'verified' => 'verified', 'rejected' => 'rejected'];

    private const PROFILE_COLUMNS = [
        'company_name', 'company_address', 'company_phone', 'company_email', 'company_website', 'npwp', 'nib', 'business_type', 'product_list',
        'bank_name', 'bank_account_no', 'bank_account_name', 'pic_name', 'pic_phone', 'pic_email',
    ];

    /** @var string[] */
    private array $warnings = [];

    public function handle(): int
    {
        $source = rtrim((string) $this->option('source'), '/\\');
        if ($source === '' || !is_file($source . '/vendors.csv')) {
            $this->error('Give --source=<folder> that contains vendors.csv (phpMyAdmin export of the old `vendors` table, column names in the first row).');

            return self::FAILURE;
        }

        $this->warnings = [];
        $dry     = (bool) $this->option('dry-run');
        $uploads = rtrim((string) $this->option('uploads'), '/\\');
        $wanted  = $this->option('ids') === 'all' ? null : array_filter(array_map('intval', explode(',', (string) $this->option('ids'))));

        $vendors   = $this->rows($source . '/vendors.csv');
        $documents = collect($this->rows($source . '/vendor_documents.csv'))->groupBy('vendor_id');
        $answers   = collect($this->rows($source . '/vendor_answers.csv'))->groupBy('vendor_id');
        $fieldMap  = $this->fieldMap($this->rows($source . '/vendor_doc_fields.csv'));
        $questionMap = $this->questionMap($this->rows($source . '/vendor_questions.csv'));

        $this->line($dry ? 'DRY RUN — nothing is written.' : 'Importing…');
        $imported = $skipped = $files = 0;

        foreach ($vendors as $row) {
            $legacyId = (int) ($row['id'] ?? 0);
            if (!$legacyId || ($wanted !== null && !in_array($legacyId, $wanted, true))) {
                continue;
            }

            if (VendorRegistration::where('legacy_vendor_id', $legacyId)->exists()) {
                $this->line("  #{$legacyId} {$row['company_name']}: already imported — skipped");
                $skipped++;

                continue;
            }

            $docs = $documents->get((string) $legacyId, collect());
            $this->line("  #{$legacyId} {$row['company_name']} [{$row['status']}] — {$docs->count()} document(s)");

            if ($dry) {
                $imported++;
                $files += $docs->count();

                continue;
            }

            $files += DB::transaction(fn () => $this->importOne($row, $docs, $answers->get((string) $legacyId, collect()), $fieldMap, $questionMap, $uploads));
            $imported++;
        }

        foreach (array_unique($this->warnings) as $w) {
            $this->warn('  ! ' . $w);
        }
        $this->info(sprintf('%s: %d vendor(s)%s, %d already there, %d document row(s).', $dry ? 'Would import' : 'Imported', $imported, '', $skipped, $files));

        return self::SUCCESS;
    }

    /** @return int jumlah baris dokumen yang dibuat */
    private function importOne(array $row, $docs, $answers, array $fieldMap, array $questionMap, string $uploads): int
    {
        $status = self::STATUS_MAP[$row['status'] ?? 'draft'] ?? 'draft';
        $type   = ($row['vendor_type'] ?? 'b2b') === 'personal' ? 'personal' : 'b2b';

        $profile = [];
        foreach (self::PROFILE_COLUMNS as $col) {
            $profile[$col] = ($row[$col] ?? '') !== '' ? $row[$col] : null;
        }

        $registration = VendorRegistration::create($profile + [
            'reference_no' => $this->nextNumber(), 'vendor_type' => $type, 'source' => 'legacy', 'legacy_vendor_id' => (int) $row['id'],
            'status' => $status, 'admin_note' => ($row['admin_note'] ?? '') !== '' ? $row['admin_note'] : null,
            'submitted_at' => $this->date($row['submitted_at'] ?? null),
            'completed_at' => in_array($status, ['verified', 'rejected'], true) ? ($this->date($row['updated_at'] ?? null) ?? now()) : null,
        ]);
        VendorStatusLog::create([
            'registration_id' => $registration->id, 'from_status' => null, 'to_status' => $status,
            'note' => 'Imported from the old vendor portal (vendor #' . $row['id'] . ', status "' . ($row['status'] ?? '') . '")', 'actor_name' => 'Legacy import',
        ]);

        $made = 0;
        $other = VendorDocumentField::where('label_en', 'Other Legal Documents')->first();
        foreach ($docs as $doc) {
            $field = ($fieldMap[(int) ($doc['field_id'] ?? 0)] ?? null) ?: $other;
            if (!$field) {
                $this->warnings[] = 'No document type found for legacy field #' . ($doc['field_id'] ?? '?') . ' and no "Other Legal Documents" type exists.';

                continue;
            }

            $path = null;
            $name = $doc['filename'] ?? '';
            if ($uploads !== '' && $name !== '' && is_file($uploads . '/' . basename($name))) {
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $path = 'vendor-onboarding/' . $registration->id . '/' . Str::random(40) . ($ext ? '.' . $ext : '');
                Storage::disk('local')->put($path, file_get_contents($uploads . '/' . basename($name)));
            } else {
                $this->warnings[] = 'File not found in the uploads folder (that document row was skipped): ' . $name;
            }

            // Baris tanpa berkas tidak berguna dan menyesatkan (tombol Open gagal): hanya dibuat bila berkasnya ada.
            if ($path === null) {
                continue;
            }

            VendorDocument::create([
                'registration_id' => $registration->id, 'field_id' => $field->id, 'original_name' => $doc['original_name'] ?? $name,
                'path' => $path, 'size' => (int) ($doc['file_size'] ?? 0), 'mime' => $doc['mime_type'] ?? null,
            ]);
            $made++;
        }

        foreach ($answers as $a) {
            $question = $questionMap[(int) ($a['question_id'] ?? 0)] ?? null;
            if (!$question) {
                $this->warnings[] = 'An answer was skipped because its question could not be matched by text (give vendor_questions.csv).';

                continue;
            }
            VendorAnswer::updateOrCreate(['registration_id' => $registration->id, 'question_id' => $question->id], ['answer' => $a['answer'] ?? null]);
        }

        return $made;
    }

    /** Jenis dokumen lama → baru, lewat kecocokan nama (EN atau ID, huruf kecil, tanpa spasi ganda). @return array<int,VendorDocumentField> */
    private function fieldMap(array $legacy): array
    {
        $byName = [];
        foreach (VendorDocumentField::all() as $f) {
            foreach ([$f->label_en, $f->label_id] as $n) {
                if ($n) {
                    $byName[$this->norm($n)] = $f;
                }
            }
        }

        $map = [];
        foreach ($legacy as $row) {
            $hit = $byName[$this->norm($row['label'] ?? '')] ?? null;
            if ($hit) {
                $map[(int) $row['id']] = $hit;
            } else {
                $this->warnings[] = 'Legacy document type "' . ($row['label'] ?? '?') . '" has no match — its files go under "Other Legal Documents".';
            }
        }

        return $map;
    }

    /** @return array<int,VendorQuestion> */
    private function questionMap(array $legacy): array
    {
        $byText = [];
        foreach (VendorQuestion::all() as $q) {
            foreach ([$q->text_en, $q->text_id] as $t) {
                if ($t) {
                    $byText[$this->norm($t)] = $q;
                }
            }
        }

        $map = [];
        foreach ($legacy as $row) {
            if ($hit = $byText[$this->norm($row['question'] ?? '')] ?? null) {
                $map[(int) $row['id']] = $hit;
            }
        }

        return $map;
    }

    private function norm(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
    }

    private function date(?string $value): ?string
    {
        return $value && $value !== '0000-00-00 00:00:00' && strtotime($value) ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    private function nextNumber(): string
    {
        $stem   = 'VND-' . now()->format('Y') . '-';
        $latest = DB::table('vendor_registrations')->where('reference_no', 'like', $stem . '%')->orderByDesc('reference_no')->value('reference_no');

        return $stem . str_pad((string) ($latest ? ((int) substr($latest, strlen($stem))) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }

    /** @return array<int,array<string,string>> baris CSV dengan kunci nama kolom; berkas yang tidak ada = kosong */
    private function rows(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);

            return [];
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]); // BOM dari ekspor Windows
        $header = array_map('trim', $header);

        $out = [];
        while (($cols = fgetcsv($handle)) !== false) {
            if ($cols === [null]) {
                continue;
            }
            $out[] = array_combine($header, array_pad(array_slice($cols, 0, count($header)), count($header), ''));
        }
        fclose($handle);

        return $out;
    }
}
