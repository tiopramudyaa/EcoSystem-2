<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Approval\ApprovalEngine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Dasar bersama Sales Compliance Approval dan Vendor Onboarding: siapa saya, apa yang saya pegang, dan cara menayangkan berkas
 * privat. Pintu halaman dijaga middleware di rute (`menu:`, `menu.can:`, `menu.approver:`); di sini hanya keputusan PER DOKUMEN.
 */
abstract class LegalWorkflowController extends Controller
{
    protected function meId(): int
    {
        return (int) session('user.id');
    }

    protected function me(): ?Employee
    {
        return Employee::find($this->meId());
    }

    protected function holds(string $slug): bool
    {
        return (bool) $this->me()?->canAccessMenu($slug);
    }

    /**
     * Melihat SEMUA dokumen modul (bukan hanya miliknya): pemegang slug `.approve` atau orang yang ditunjuk pada langkah.
     * Selain itu hanya dokumen yang ia ajukan atau buatkan.
     */
    protected function seesAll(string $module, string $approveSlug): bool
    {
        return $this->holds($approveSlug) || app(ApprovalEngine::class)->isDesignatedApprover($module, $this->meId());
    }

    /**
     * Tayangkan berkas dari disk privat. Nama yang ditampilkan adalah nama asli unggahan, bukan nama acak di disk; nama itu
     * dibersihkan dari karakter pemisah supaya tidak menyusup ke header.
     */
    protected function streamPrivate(string $disk, string $path, string $originalName, ?string $mime, bool $download = false): StreamedResponse
    {
        abort_if($path === '' || !Storage::disk($disk)->exists($path), 404);

        $safe = preg_replace('/[\\\\\/\r\n"]+/', '_', $originalName) ?: 'file';

        return Storage::disk($disk)->response($path, $safe, [
            'Content-Type'           => $mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ], $download ? 'attachment' : 'inline');
    }

    /** Ekstensi dan ukuran sudah divalidasi aturan `mimes`/`max`; di sini hanya memastikan objeknya berkas yang sah. */
    protected function validUpload(mixed $file): bool
    {
        return $file instanceof UploadedFile && $file->isValid();
    }

    /** Jawaban pertanyaan dari input `q[<id>]`. @return array<int,mixed> */
    protected function answersFrom(\Illuminate\Http\Request $request): array
    {
        $out = [];
        foreach ((array) $request->input('q', []) as $id => $value) {
            if (is_numeric($id)) {
                $out[(int) $id] = is_array($value) ? array_map('strval', $value) : (string) $value;
            }
        }

        return $out;
    }
}
