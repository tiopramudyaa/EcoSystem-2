<?php

namespace App\Services\Contracts;

use App\Models\ContractOption;
use App\Models\EmployeeContract;
use App\Support\Contracts\ContractRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Mencatat kontrak yang SUDAH berlaku (kontrak fisik yang discan) ke HR & General → Contract.
 *
 * Satu mesin untuk semua pintu masuk (tombol "Add existing contract" di halaman karyawan; kelak wizard massal di halaman Contracts):
 * aturan bisnisnya di sini saja. Perbedaan dengan ContractService::create (kontrak baru dari template):
 *  · nomor kontrak = nomor di kertas (harus unik), bukan nomor yang dibuat sistem;
 *  · tanpa template dan tanpa teks kontrak: dokumennya adalah salinan scan (PDF) yang diunggah;
 *  · pagar kesiapan data karyawan dilewati (data historis tidak boleh terhalang profil yang belum lengkap) → hanya peringatan;
 *  · gaji TIDAK dicatat dan Salary Components TIDAK disentuh (impor tidak boleh mengubah payroll);
 *  · tidak masuk alur tanda tangan elektronik (sign_stage kosong).
 * Aturan yang tetap sama: jenis dan tanggal (ContractRules::dateError), satu kontrak berjalan per karyawan, nomor unik, PDF diperiksa isinya.
 */
final class ExistingContractImporter
{
    /** Status yang boleh dipilih untuk kontrak lama: yang sedang berjalan, atau yang sudah berakhir. */
    public const STATUSES = [ContractRules::STATUS_ACTIVE, ContractRules::STATUS_TERMINATED];

    public function __construct(private ContractService $contracts, private ContractOptionService $options)
    {
    }

    public static function maxKb(): int
    {
        return (int) config('hc_contract.stamp.upload_max_kb', 10240);
    }

    /**
     * Periksa tanpa menyimpan. Dipakai formulir satuan dan (kelak) pratinjau massal.
     *
     * @param  array<string,mixed>  $d  contract_number, contract_type (kode pilihan Settings), start_date, end_date, signed_date, status, position, ...
     * @return array{errors:string[],warnings:string[],type:?string,kind:?string}
     */
    public function check(int $employeeId, array $d, ?UploadedFile $pdf, ?EmployeeContract $existing = null): array
    {
        $errors = [];
        $warnings = [];
        $text = fn ($v) => is_string($v) ? trim($v) : '';

        $header = $this->contracts->employeeHeader($employeeId);
        if ($header === null) {
            return ['errors' => ['The employee was not found or is not active.'], 'warnings' => [], 'type' => null, 'kind' => null];
        }
        if (($header->employee_type ?? null) === 'External') {
            $errors[] = 'Consultants have their own menu (External › External Contract).';
        }

        // Nomor kontrak asli
        $number = $text($d['contract_number'] ?? '');
        if ($number === '') {
            $errors[] = 'Enter the contract number as printed on the paper contract.';
        } elseif (mb_strlen($number) > 100) {
            $errors[] = 'The contract number is longer than 100 characters.';
        } elseif (EmployeeContract::where('contract_number', $number)->when($existing, fn ($q) => $q->where('contract_id', '!=', $existing->contract_id))->exists()) {
            $errors[] = "Contract number {$number} is already used by another contract.";
        }

        // Jenis (kode pilihan di Settings; aturan berjalan di jenis dasarnya)
        $resolved = null;
        $code = $text($d['contract_type'] ?? '');
        $allowed = $this->options->options(ContractOption::SCOPE_INTERNAL, ContractOption::KIND_CONTRACT_TYPE, $existing ? ($existing->contract_kind ?: $existing->contract_type) : null);
        if ($code === '' || !array_key_exists($code, $allowed)) {
            $errors[] = 'Choose a contract type.';
        } else {
            $resolved = $this->options->resolve(ContractOption::SCOPE_INTERNAL, $code);
        }

        // Status
        $status = $text($d['status'] ?? '');
        if (!in_array($status, self::STATUSES, true)) {
            $errors[] = 'Choose Active (still running) or Terminated (already ended).';
        }

        // Tanggal
        $start = $text($d['start_date'] ?? '');
        $end = $text($d['end_date'] ?? '');
        $signed = $text($d['signed_date'] ?? '');
        foreach (['Start date' => $start, 'End date' => $end, 'Signed date' => $signed] as $label => $v) {
            if ($v !== '' && !$this->validDate($v)) {
                $errors[] = "{$label} is not a valid date.";
            }
        }
        if ($resolved && !array_filter($errors, fn ($e) => str_contains($e, 'not a valid date'))) {
            if ($error = ContractRules::dateError($resolved['base'], $start, $end !== '' ? $end : null, (int) config('hc_contract.pkwt_max_months', 60))) {
                $errors[] = $error;
            }
        }
        if ($status === ContractRules::STATUS_ACTIVE && $end !== '' && $this->validDate($end) && $end < now()->toDateString()) {
            $warnings[] = 'The end date has already passed, so this contract will show as Expired. Choose Terminated if you want it to read as ended.';
        }
        if ($status === ContractRules::STATUS_TERMINATED && $end === '') {
            $warnings[] = 'A Terminated contract without an end date: the period in the history will read “until —”.';
        }

        // Satu kontrak berjalan per karyawan
        if ($status === ContractRules::STATUS_ACTIVE && !$errors) {
            $other = $this->contracts->runningContract($employeeId, $existing?->contract_id);
            if ($other) {
                $errors[] = "This employee already has a running contract ({$other->contract_number}). Record this one as Terminated, or end the running one first.";
            }
        }

        foreach (['position' => 255, 'department' => 255, 'work_location' => 255, 'notes' => 2000] as $field => $max) {
            if (mb_strlen($text($d[$field] ?? '')) > $max) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is longer than {$max} characters.";
            }
        }

        // Salinan scan: wajib saat mencatat; saat mengubah boleh kosong (salinan lama tetap)
        if ($pdf === null) {
            if ($existing === null || !$existing->signed_file_path) {
                $errors[] = 'Attach the scanned contract as a PDF.';
            }
        } elseif (($pdfError = $this->pdfError($pdf)) !== null) {
            $errors[] = $pdfError;
        }

        // Data karyawan belum lengkap: hanya peringatan (tidak menghalangi pencatatan data historis)
        if (!$existing) {
            $readiness = $this->contracts->readiness($employeeId);
            if (!$readiness['ready']) {
                $warnings[] = 'The employee’s data is not complete yet (' . count($readiness['missing']) . ' required item' . (count($readiness['missing']) === 1 ? '' : 's') . '). It does not block this record, but complete it before creating new contracts.';
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'type' => $resolved['base'] ?? null, 'kind' => $resolved['kind'] ?? null];
    }

    /**
     * Catat kontrak lama + salinan scan-nya. Semua-atau-tidak: bila PDF gagal disimpan, baris kontrak dibatalkan.
     *
     * @throws \DomainException
     */
    public function import(int $employeeId, array $d, UploadedFile $pdf, int $actorId): EmployeeContract
    {
        $c = $this->check($employeeId, $d, $pdf);
        if ($c['errors']) {
            throw new \DomainException(implode(' ', $c['errors']));
        }

        $created = null;
        try {
            return DB::transaction(function () use ($employeeId, $d, $pdf, $actorId, $c, &$created) {
                $created = EmployeeContract::create($this->attributes($d, $c) + [
                    'employee_id'   => $employeeId,
                    'created_by'    => $actorId,
                    'origin'        => EmployeeContract::ORIGIN_IMPORTED,
                    'imported_at'   => now(),
                    'imported_by'   => $actorId,
                    'template_id'   => null,
                    'body_html'     => null,
                    'is_active'     => ContractRules::isActiveFor(trim($d['status'])),
                ]);

                return $this->contracts->storeSignedCopy($created, $pdf, $actorId);
            });
        } catch (\Throwable $e) {
            if ($created?->contract_id) {
                Storage::disk('local')->deleteDirectory('contracts/' . $created->contract_id);   // sisa berkas dari transaksi yang dibatalkan
            }
            throw $e;
        }
    }

    /**
     * Koreksi data kontrak impor (nomor, jenis, tanggal, status, jabatan, ...) dan/atau ganti salinan scan.
     *
     * @throws \DomainException
     */
    public function update(EmployeeContract $contract, array $d, ?UploadedFile $pdf, int $actorId): EmployeeContract
    {
        $this->assertImported($contract);
        $c = $this->check((int) $contract->employee_id, $d, $pdf, $contract);
        if ($c['errors']) {
            throw new \DomainException(implode(' ', $c['errors']));
        }

        return DB::transaction(function () use ($contract, $d, $pdf, $actorId, $c) {
            $contract->update($this->attributes($d, $c) + ['is_active' => ContractRules::isActiveFor(trim($d['status']))]);
            if ($pdf !== null) {
                $contract = $this->contracts->storeSignedCopy($contract->refresh(), $pdf, $actorId);
            }

            return $contract->refresh();
        });
    }

    /** Hapus kontrak impor beserta salinan scan-nya (salah karyawan / salah berkas). Kontrak buatan sistem tidak bisa dihapus lewat sini. */
    public function remove(EmployeeContract $contract): void
    {
        $this->assertImported($contract);
        DB::transaction(function () use ($contract) {
            $id = $contract->contract_id;
            $contract->delete();
            // Folder per kontrak (contracts/{id}) berisi salinan scan saja: hapus utuh, jangan tinggalkan folder kosong.
            Storage::disk('local')->deleteDirectory('contracts/' . $id);
        });
    }

    /** @throws \DomainException */
    private function assertImported(EmployeeContract $contract): void
    {
        if (!$contract->isImported()) {
            throw new \DomainException('Only a contract recorded from a paper contract can be changed here.');
        }
    }

    /** Kolom yang sama untuk catat baru dan koreksi. @param array{type:?string,kind:?string} $c */
    private function attributes(array $d, array $c): array
    {
        $text = fn ($key) => (($v = trim((string) ($d[$key] ?? ''))) === '') ? null : $v;
        $start = trim((string) $d['start_date']);
        $signed = $text('signed_date');
        $label = ContractRules::TYPES[$c['type']] ?? $c['type'];

        return [
            'contract_number'  => trim((string) $d['contract_number']),
            'contract_name'    => $label . ' (imported)',
            'contract_type'    => $c['type'],
            'contract_kind'    => $c['kind'],
            'lifecycle_status' => trim((string) $d['status']),
            'start_date'       => $start,
            'end_date'         => $text('end_date'),
            'signed_date'      => $signed,
            'contract_date'    => $signed ?? $start,
            'position'         => $text('position'),
            'position_type'    => $text('position_type'),
            'department'       => $text('department'),
            'work_location'    => $text('work_location'),
            'notes'            => $text('notes'),
        ];
    }

    private function validDate(string $v): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && checkdate((int) substr($v, 5, 2), (int) substr($v, 8, 2), (int) substr($v, 0, 4));
    }

    /** Isi berkas diperiksa (bukan hanya nama): harus PDF sungguhan, tidak kosong, tidak melebihi batas. */
    private function pdfError(UploadedFile $pdf): ?string
    {
        if (!$pdf->isValid()) {
            return 'The file could not be uploaded. It may be larger than the server allows; try a smaller scan.';
        }
        if ($pdf->getSize() > self::maxKb() * 1024) {
            return 'The PDF is larger than ' . round(self::maxKb() / 1024) . ' MB. Scan at a lower resolution or compress it.';
        }
        if ((string) @file_get_contents($pdf->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            return 'The scan must be a PDF file.';
        }

        return null;
    }
}
