<?php

namespace App\Http\Controllers\HR_General;

use App\Http\Controllers\Controller;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectFlowSetting;
use App\Models\Reimbursement\ReimbursementApprovalStep;
use App\Models\Ticket;
use App\Services\ObjectFund\FundService;
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
    public function tickets(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Ticket::query();

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

    /** Kategori dana yang tersedia untuk sebuah project atau tiket (nama saja, tanpa nominal). */
    public function categories(Request $request)
    {
        $type = (string) $request->query('type', '');
        $ref  = (int) $request->query('ref', 0);

        if (!in_array($type, [FundPool::TYPE_PROJECT, FundPool::TYPE_SUPPORT], true) || $ref <= 0) {
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
    public function preview(Request $request, ObjectApproverResolver $resolver, FundService $funds)
    {
        $settings = ObjectFlowSetting::current();

        if (!$settings->enabled) {
            return response()->json(['success' => true, 'message' => '', 'data' => ['enabled' => false]]);
        }

        $items = collect($request->input('items', []))
            ->filter(fn ($r) => is_array($r))
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

        $requester = (int) ($request->input('employee_id') ?: session('user.id'));
        $resolved  = $resolver->resolve($items, $requester, $settings);

        $steps = collect($resolved['steps'])->pluck('name')->all();

        foreach (ReimbursementApprovalStep::forReimbursement()->active()->orderBy('order_seq')->get() as $step) {
            $steps[] = $step->name;
        }

        $fund = $funds->evaluate($items, Carbon::parse($request->input('request_date') ?: now()), $settings);

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
