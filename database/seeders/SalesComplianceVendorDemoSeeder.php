<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Legal\SalesCompliance\SalesComplianceRequest;
use App\Models\Legal\VendorOnboarding\VendorRegistration;
use App\Services\Legal\SalesComplianceService;
use App\Services\Legal\VendorOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh Sales Compliance Approval dan Vendor Onboarding — untuk melihat seperti apa hasil dan keluaran kedua fitur.
 *
 * Seluruh baris diberi awalan "DEMO" pada nama proyek / perusahaan, dan dibuat lewat service yang sama dengan layar (jadi alurnya
 * nyata: salinan langkah, jejak, notifikasi, berkas privat). Menjalankannya ulang tidak menggandakan data.
 *
 *   php artisan db:seed --class=SalesComplianceVendorDemoSeeder
 *
 * Menghapusnya: `SalesComplianceVendorDemoSeeder::purge()` (php artisan tinker) — hanya baris ber-awalan "DEMO" beserta Business
 * Partner yang ditautkan darinya; tidak ada tabel yang dikosongkan.
 *
 * Pemeran: dua karyawan pertama yang bukan administrator menjadi sales (pemilik deal), administrator "membuatkan" permintaannya sehingga
 * draft-nya bisa diedit dari akun administrator; pemegang role HO Legal Head menjadi penyetuju langkah.
 * JANGAN dijalankan di produksi.
 */
class SalesComplianceVendorDemoSeeder extends Seeder
{
    private const MARK = 'DEMO';

    public function run(): void
    {
        if (SalesComplianceRequest::where('project_name', 'like', self::MARK . ' %')->exists()
            || VendorRegistration::where('company_name', 'like', self::MARK . ' %')->exists()) {
            $this->command?->info('Demo data already exists — nothing added. Use SalesComplianceVendorDemoSeeder::purge() to remove it first.');

            return;
        }

        $adminRole = DB::table('employee_role')->where('name', 'EC Administrator')->value('id');
        $admin = (int) DB::table('employee_role_assignment')->where('role_id', $adminRole)->value('employee_id');
        $headRole = DB::table('employee_role')->where('name', 'HO Legal Head')->value('id');
        $head = (int) DB::table('employee_role_assignment')->where('role_id', $headRole)->where('employee_id', '!=', $admin)->value('employee_id');
        // Penjual contoh: Sales Operation Account Executive; cadangan karyawan aktif tanpa peran Director/Head (jangan petinggi)
        $aeRole = DB::table('employee_role')->where('name', 'Sales Operation Account Executive')->value('id');
        $sales = $aeRole
            ? DB::table('employee_role_assignment')->where('role_id', $aeRole)->whereNotIn('employee_id', [$admin, $head])->orderBy('employee_id')->limit(2)->pluck('employee_id')->map(fn ($i) => (int) $i)->all()
            : [];
        if (count($sales) < 2) {
            $senior = DB::table('employee_role_assignment as a')->join('employee_role as r', 'r.id', '=', 'a.role_id')
                ->where(fn ($q) => $q->where('r.name', 'like', '%Director%')->orWhere('r.name', 'like', '%Head%')->orWhere('r.name', 'like', '%Administrator%'))
                ->pluck('a.employee_id')->all();
            $sales = DB::table('employee')->where('is_active', 1)->whereNotIn('employee_id', array_merge([$admin, $head], $senior))->orderBy('employee_id')->limit(2)->pluck('employee_id')->map(fn ($i) => (int) $i)->all();
        }

        if (!$admin || !$head || count($sales) < 2) {
            $this->command?->error('Needs an EC Administrator, a HO Legal Head and two other active employees.');

            return;
        }

        $this->sales($sales, $head, $admin);
        $this->vendors($admin, $head);

        $this->command?->info('Demo data added: 5 sales compliance requests and 6 vendor registrations (names start with "DEMO").');
    }

    /** @param array<int,int> $sales */
    private function sales(array $sales, int $head, int $admin): void
    {
        $svc = app(SalesComplianceService::class);

        $deals = [
            // [sales, project, customer, PIC, final, mrp, justification, tujuan akhir]
            [$sales[0], 'DEMO SAP S/4HANA Rollout Phase 1', 'PT Nusantara Energi (DEMO)', 'Budi Santoso', 1850000000, 2000000000, 'Multi-year commitment; discount approved by the principal.', 'approved'],
            [$sales[1], 'DEMO Cloud Migration Assessment', 'Pemkot Bandung (DEMO)', 'Rina Marlina', 420000000, 600000000, 'Competitive tender, price aligned with the winning bid range.', 'rejected'],
            [$sales[0], 'DEMO Basis Managed Service 2027', 'PT Samudera Logistik (DEMO)', 'Andi Pratama', 960000000, 960000000, null, 'in_review'],
            [$sales[1], 'DEMO Fiori Custom Apps', 'PT Kreasi Digital (DEMO)', 'Sari Wulandari', 275000000, 300000000, 'Bundled with the support contract.', 'in_review'],
            [$sales[0], 'DEMO HR Analytics Dashboard', 'PT Mitra Sejahtera (DEMO)', 'Dewi Anggraini', null, null, null, 'draft'],
        ];

        foreach ($deals as [$emp, $project, $customer, $pic, $final, $mrp, $just, $target]) {
            $saved = $svc->saveDraft(null, [
                'employee_id' => $emp, 'project_name' => $project, 'customer_name' => $customer, 'pic_name' => $pic,
                'has_meeting' => $target !== 'draft', 'meeting_location' => $target !== 'draft' ? 'Client office, Jakarta' : null,
                'has_hospitality' => $project === 'DEMO Fiori Custom Apps',
                'hospitality_detail' => $project === 'DEMO Fiori Custom Apps' ? 'Business lunch for 4 people, about IDR 1,200,000 in total.' : null,
                'documents_received' => $target !== 'draft' ? ['Company Profile', 'NPWP', 'NIB'] : null,
                'final_price' => $final, 'mrp_price' => $mrp, 'price_justification' => $just,
                'payment_method' => $target !== 'draft' ? 'Transfer, 30 days after invoice' : null,
                'contract_value' => $final, 'contract_date' => $target !== 'draft' ? now()->addDays(14)->toDateString() : null,
            ], $this->answers($svc), $admin);

            $request = $saved['request'];
            if ($target === 'draft') {
                continue;
            }

            foreach (['price' => 'Agreed price', 'mrp' => 'MRP'] as $type => $label) {
                $this->attach($svc, $request, $type, "{$label} - {$project}.pdf", "{$label} document for {$project}");
            }

            $result = $svc->submit($request->fresh(), $emp);
            if (!$result['ok']) {
                continue;
            }

            if ($target === 'approved') {
                $svc->approve($request->fresh(), $head, 'Price and declarations are consistent. Approved.');
            } elseif ($target === 'rejected') {
                $svc->reject($request->fresh(), $head, 'Discount is too deep and the tender communication is unclear. Please explain and resubmit.');
            }
        }
    }

    private function vendors(int $admin, int $head): void
    {
        $svc = app(VendorOnboardingService::class);

        $b2b = fn (string $name, string $slug, array $extra = []) => $extra + [
            'vendor_type' => 'b2b', 'company_name' => $name, 'company_address' => 'Jl. Contoh Raya No. 10, Yogyakarta 55281', 'company_phone' => '0274-555010',
            'company_email' => "hello@{$slug}.demo.test", 'company_website' => "https://{$slug}.demo.test", 'npwp' => '01.234.567.8-901.000',
            'nib' => '1234567890123', 'business_type' => 'IT Consulting', 'product_list' => 'SAP implementation, application support, staff augmentation',
            'bank_name' => 'Bank Central Asia', 'bank_account_no' => '8830' . random_int(100000, 999999), 'bank_account_name' => $name,
            'pic_name' => 'Agus Setiawan', 'pic_phone' => '0812-3456-7890', 'pic_email' => "agus@{$slug}.demo.test",
        ];

        $cases = [
            // [profil, lengkapi dokumen?, tujuan akhir]
            [$b2b('DEMO PT Teknologi Nusantara', 'teknusa', ['npwp' => '02.111.222.3-401.000', 'nib' => '8120000111222']), true, 'verified'],
            [$b2b('DEMO PT Solusi Data Prima', 'solusidata', ['npwp' => '02.333.444.5-401.000', 'nib' => '8120000333444']), true, 'in_review'],
            [$b2b('DEMO CV Karya Mandiri', 'karyamandiri', ['npwp' => '02.555.666.7-401.000', 'nib' => '8120000555666']), true, 'submitted'],
            [$b2b('DEMO PT Cipta Sistem Andalan', 'ciptasistem', ['npwp' => '02.777.888.9-401.000', 'nib' => '8120000777888']), true, 'rejected'],
            [$b2b('DEMO PT Baru Terdaftar', 'baruterdaftar', ['company_phone' => null, 'bank_name' => null, 'bank_account_no' => null, 'bank_account_name' => null, 'pic_name' => null, 'pic_phone' => null, 'pic_email' => null]), false, 'draft'],
            [[
                'vendor_type' => 'personal', 'company_name' => 'DEMO Maya Kusuma (Freelance SAP FICO)', 'company_address' => 'Jl. Kaliurang Km 7, Sleman', 'company_phone' => '0813-9876-5432',
                'company_email' => 'maya.kusuma@demo.test', 'company_website' => 'https://maya-kusuma.demo.test', 'npwp' => '09.876.543.2-411.000', 'nib' => '3404010101900001',
                'business_type' => 'SAP FICO consultant', 'product_list' => 'Financial accounting configuration, UAT support', 'bank_name' => 'Bank Mandiri',
                'bank_account_no' => '1370' . random_int(100000, 999999), 'bank_account_name' => 'Maya Kusuma',
            ], true, 'submitted'],
        ];

        foreach ($cases as [$profile, $withDocs, $target]) {
            $registration = $svc->saveProfile(null, $profile, $this->vendorAnswers($svc, $profile['vendor_type']), $admin)['registration'];

            if ($withDocs) {
                foreach ($svc->documentFields($registration->vendor_type)->where('is_required', true) as $field) {
                    $this->attachVendor($svc, $registration, $field, "{$field->label_en}.pdf", "{$field->label_en} of {$registration->company_name}", $admin);
                }
            }
            if ($target === 'draft') {
                continue;
            }

            $svc->submit($registration->fresh(), $admin);
            if ($target === 'submitted') {
                continue;
            }

            $svc->startReview($registration->fresh(), $admin);
            if ($target === 'verified') {
                $svc->approve($registration->fresh(), $head, 'Documents match the declarations. Verified.');
                $svc->createBusinessPartner($registration->fresh(), $admin);
            } elseif ($target === 'rejected') {
                $svc->reject($registration->fresh(), $head, 'The NIB document is unreadable and the ABAC declaration is unsigned. Please upload clear copies.');
            }
        }
    }

    /** @return array<int,mixed> jawaban untuk semua pertanyaan wajib (pilihan pertama) */
    private function answers(SalesComplianceService $svc): array
    {
        $out = [];
        foreach ($svc->questions() as $q) {
            if ($q->is_required) {
                $out[$q->id] = $q->options[0] ?? 'Yes';
            }
        }

        return $out;
    }

    /** Pernyataan due diligence: jawab "No" pada pertanyaan Yes/No, isi singkat pada teks. @return array<int,mixed> */
    private function vendorAnswers(VendorOnboardingService $svc, string $type): array
    {
        $out = [];
        foreach ($svc->questions($type) as $q) {
            $out[$q->id] = match ($q->type) {
                'radio' => (str_starts_with($q->text_en, 'Does the company') || str_starts_with($q->text_en, 'Is the company') || str_starts_with($q->text_en, 'Have all')) ? 'Yes' : 'No',
                'checkbox' => [($q->options[0] ?? '')],
                'text' => str_contains($q->text_en, 'years') ? '8' : 'IDR 5,000,000,000',
                default => 'None',
            };
        }

        return $out;
    }

    private function attach(SalesComplianceService $svc, SalesComplianceRequest $request, string $type, string $name, string $text): void
    {
        $svc->addAttachment($request, $type, $this->pdf($name, $text), (int) $request->employee_id);
    }

    private function attachVendor(VendorOnboardingService $svc, VendorRegistration $registration, $field, string $name, string $text, int $actor): void
    {
        $svc->addDocument($registration, $field, $this->pdf($name, $text), $actor);
    }

    /** PDF satu halaman yang sah (dibuat di tempat; tanpa paket). */
    private function pdf(string $name, string $text): UploadedFile
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], 'DEMO - ' . $text);
        $stream = "BT /F1 12 Tf 30 110 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 420 220] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $body = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($body);
            $body .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($body);
        $body .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $body .= sprintf("%010d 00000 n \n", $o);
        }
        $body .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        $tmp = tempnam(sys_get_temp_dir(), 'demo');
        file_put_contents($tmp, $body);

        return new UploadedFile($tmp, $name, 'application/pdf', null, true);
    }

    /**
     * Hapus HANYA data demo: baris ber-awalan "DEMO" beserta anak-anaknya, berkas privatnya, dan Business Partner yang dibuat dari
     * pendaftaran demo itu. Tidak ada tabel yang dikosongkan.
     */
    public static function purge(): array
    {
        $removed = ['sales' => 0, 'vendors' => 0, 'partners' => 0];

        foreach (SalesComplianceRequest::where('project_name', 'like', self::MARK . ' %')->get() as $request) {
            foreach ($request->attachments as $a) {
                Storage::disk('local')->delete($a->path);
            }
            DB::table('notifications')->where('type', 'like', 'sales_compliance_%')->where('preview', 'like', '%' . $request->request_no . '%')->delete();
            $request->delete();
            $removed['sales']++;
        }

        foreach (VendorRegistration::where('company_name', 'like', self::MARK . ' %')->get() as $registration) {
            foreach ($registration->documents as $d) {
                Storage::disk('local')->delete($d->path);
            }
            DB::table('notifications')->where('type', 'like', 'vendor_onboarding_%')->where('preview', 'like', '%' . $registration->reference_no . '%')->delete();
            $customerId = $registration->customer_id;
            $registration->delete();
            if ($customerId && DB::table('customer')->where('customer_id', $customerId)->where('type', 'Vendor')->exists()) {
                DB::table('customer')->where('customer_id', $customerId)->delete();
                $removed['partners']++;
            }
            $removed['vendors']++;
        }

        return $removed;
    }
}
