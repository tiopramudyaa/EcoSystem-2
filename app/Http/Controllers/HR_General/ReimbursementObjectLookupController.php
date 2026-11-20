<?php

namespace App\Http\Controllers\HR_General;

use App\Http\Controllers\Controller;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\Reimbursement\ReimbursementApprovalStep;
use App\Models\Ticket;
use App\Services\ObjectFund\FundService;
use App\Services\ObjectFund\ObjectAccessPolicy;
use App\Services\ObjectFund\ObjectApproverResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Pembantu form Reimbursement dengan konsep Object: pencarian tiket, kategori
 * dana, dan pratinjau penyetuju. Hanya MEMBACA.
 *
 * Sengaja tidak menampilkan sisa dana kepada pemohon (keputusan pengguna: dana
 * terhubung tetapi tidak ditampilkan). Pratinjau hanya menyebut apakah nominal
 * melebihi dana, dan pesan penolakan baru memuat angka bila memang menghalangi.
 */
class ReimbursementObjectLookupController extends Controller
{
    /**
     * Tiket dari master tiket (menu Ticket). Tanpa kata kunci: tiket terbaru,
     * supaya daftar langsung tampil saat kolom dibuka. Dengan kata kunci:
     * dicari pada nomor dan deskripsi. Hasil dibatasi supaya ringan.
     */
    public function tickets(Request $request, ObjectAccessPolicy $policy)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Ticket::query();

        // Hanya tiket yang boleh dibebani pemohon (aturan akses di Reimbursement Settings), tidak seluruh daftar tiket.
        $owner = $this->ownerOf($request, $request->query('employee_id'));
        $query = $policy->restrict($query, 'ticket_id', $policy->scope($owner, 'support'));
        if (\Illuminate\Support\Facades\Schema::hasColumn('ticket', 'is_hidden')) {
            $query->whereNull('is_hidden');
        }

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(fn ($w) => $w->where('ticket_number', 'like', $like)->orWhere('description', 'like', $like));
        }

        $rows = $query->orderByDesc('ticket_id')
            ->limit(20)
            ->get(['ticket_id', 'ticket_number', 'description', 'status'])
            ->map(fn ($t) => [
                'id'          => (int) $t->ticket_id,
                'number'      => $t->ticket_number,
                'description' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $t->description))), 0, 70),
                'status'      => $t->status,
            ]);

        return response()->json(['success' => true, 'message' => '', 'data' => $rows]);
    }

    /** Pemilik dokumen yang diperiksa: ESS selalu dirinya sendiri; form HR boleh menyebut karyawan lain. */
    private function ownerOf(Request $request, $requested): int
    {
        return $request->routeIs('general.my-*')
            ? (int) session('user.id')
            : (int) ($requested ?: session('user.id'));
    }

    /** Kategori dana yang tersedia untuk sebuah project atau tiket (nama saja, tanpa nominal). */
    public function categories(Request $request, ObjectAccessPolicy $policy)
    {
        $type = (string) $request->query('type', '');
        $ref  = (int) $request->query('ref', 0);

        if (!in_array($type, [FundPool::TYPE_PROJECT, FundPool::TYPE_SUPPORT], true) || $ref <= 0) {
            return response()->json(['success' => true, 'message' => '', 'data' => []]);
        }

        if (! $policy->canCharge($this->ownerOf($request, $request->query('employee_id')), $type, $ref)) {
            return response()->json(['success' => true, 'message' => '', 'data' => []]);
        }

        $names = FundPool::usable()
            ->where('object_type', $type)
            ->where('ref_id', $ref)
            ->where('category', '!=', '')
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json(['success' => true, 'message' => '', 'data' => $names]);
    }

    /**
     * Pratinjau: siapa yang akan menyetujui, dan apakah dana mencukupi.
     * Memakai pembentuk penyetuju dan pemeriksa dana yang sama dengan pengiriman,
     * sehingga yang terlihat di layar sama dengan yang terjadi saat dikirim.
     */
    public function preview(Request $request, ObjectApproverResolver $resolver, FundService $funds, ObjectAccessPolicy $policy)
    {
        $settings = ObjectFlowSetting::current();
        $module   = (string) $request->input('module', 'reimbursement');
        $isCash   = $module === 'cash_advance';

        if (!($isCash ? $settings->cash_advance_enabled : $settings->enabled)) {
            return response()->json(['success' => true, 'message' => '', 'data' => ['enabled' => false]]);
        }

        $items = collect($request->input('items', []))
            ->filter(fn ($r) => is_array($r))
            ->take(50)
            ->map(fn ($r) => [
                'cost_center_type'    => (string) ($r['cost_center_type'] ?? 'branch'),
                'branch_id'           => $r['branch_id'] ?? null,
                'delivery_project_id' => $r['delivery_project_id'] ?? null,
                'ticket_id'           => $r['ticket_id'] ?? null,
                'fund_category'       => $r['fund_category'] ?? null,
                'amount'              => (float) ($r['amount'] ?? 0),
                'cost_center_label'   => '',
            ])
            ->values()
            ->all();

        // Sisi karyawan (ESS) selalu memakai dirinya sendiri; hanya form buat-atas-nama milik HR yang boleh
        // menyebut karyawan lain, dan itu pun diperiksa ulang saat dokumen dikirim.
        $requester = $request->routeIs('general.my-*')
            ? (int) session('user.id')
            : (int) ($request->input('employee_id') ?: session('user.id'));
        // Akses: pemohon harus boleh membebani setiap project atau tiket yang dipilih.
        foreach ($items as $item) {
            $type = $item['cost_center_type'] === 'project' ? 'project' : ($item['cost_center_type'] === 'support' ? 'support' : null);
            $ref  = (int) ($type === 'project' ? $item['delivery_project_id'] : ($type === 'support' ? $item['ticket_id'] : 0));

            if ($type && $ref && ! $policy->canCharge($requester, $type, $ref)) {
                return response()->json(['success' => true, 'message' => '', 'data' => [
                    'enabled' => true, 'error' => $policy->denyMessage($type, '', $ref), 'steps' => [], 'skipped' => [], 'blocked' => null, 'needs_reason' => false,
                ]]);
            }
        }

        $resolved  = $resolver->resolve($items, $requester, $settings);

        $steps = collect($resolved['steps'])->pluck('name')->all();

        $configured = $isCash
            ? \App\Models\CashAdvance\CashAdvanceApprovalStep::where('module', \App\Models\CashAdvance\CashAdvanceApprovalStep::MODULE_CA)->where('is_active', true)->orderBy('order_seq')->get()
            : ReimbursementApprovalStep::forReimbursement()->active()->orderBy('order_seq')->get();

        foreach ($configured as $step) {
            $steps[] = $step->name;
        }

        try {
            $date = Carbon::parse((string) ($request->input('request_date') ?: now()));
        } catch (\Throwable $e) {
            $date = now();
        }

        $fund = $funds->evaluate($items, $date, $settings);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'enabled'      => true,
                'error'        => $resolved['error'],
                'steps'        => $steps,
                'skipped'      => $resolved['skipped'],
                'blocked'      => $fund['blocked'],
                'needs_reason' => $fund['needs_reason'],
            ],
        ]);
    }
}
