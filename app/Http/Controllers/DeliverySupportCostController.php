<?php

namespace App\Http\Controllers;

use App\Exports\DeliveryProjectCostExport;
use App\Models\DeliverySupport;
use App\Models\DeliverySupportCost;
use App\Models\DeliverySupportCostItem;
use App\Models\DeliverySupportCostReleaseSchedule;
use App\Services\OneDriveService;
use App\Services\PlanCostDocumentLink;
use App\Services\ProjectMonthlyCostReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Plan Cost untuk Delivery Support — mirror dari DeliveryProjectCostController.
 */
class DeliverySupportCostController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // GET /delivery/support/{support}/costs
    // ──────────────────────────────────────────────────────────────
    public function index(DeliverySupport $support)
    {
        $costs = DeliverySupportCost::where('delivery_support_id', $support->id)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('order_sequence')
            ->get();

        return response()->json([
            'costs'   => $costs->map(fn($c) => $this->formatCost($c)),
            'summary' => $this->buildSummary($costs),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /delivery/support/{support}/costs/export
    // Export Plan Cost ke Excel — 4 sheet yang sama dengan Delivery Project
    // ──────────────────────────────────────────────────────────────
    public function export(DeliverySupport $support)
    {
        $support->load('client.basicData');

        $costs = DeliverySupportCost::where('delivery_support_id', $support->id)
            ->whereNull('parent_id')
            ->with(['items', 'children.items'])
            ->orderBy('order_sequence')
            ->get();

        $filename = 'Plan_Cost_'
            . trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $support->io_number ?: $support->name), '_')
            . '_' . now()->format('Ymd_His') . '.xlsx';

        $report = new ProjectMonthlyCostReport($support);

        return Excel::download(
            new DeliveryProjectCostExport(
                $support,
                $costs->map(fn($c) => $this->formatCost($c))->all(),
                $this->buildSummary($costs),
                $this->expenseRows($costs),
                $report->build(),
                $report->releaseSchedules()
            ),
            $filename
        );
    }

    // ──────────────────────────────────────────────────────────────
    // GET /delivery/support/{support}/costs/monthly
    // Report Monthly Cost vs TOP Invoiced (lihat ProjectMonthlyCostReport)
    // ──────────────────────────────────────────────────────────────
    public function monthly(DeliverySupport $support)
    {
        return response()->json((new ProjectMonthlyCostReport($support))->build());
    }

    // ──────────────────────────────────────────────────────────────
    // POST /delivery/support/{support}/costs
    // ──────────────────────────────────────────────────────────────
    public function store(Request $request, DeliverySupport $support)
    {
        $validated = $request->validate([
            'parent_id'      => 'nullable|exists:delivery_support_costs,id',
            'code'           => 'nullable|string|max:20',
            'name'           => 'required|string|max:200',
            'cost_type'      => 'required|in:indirect,direct',
            'budget'         => 'nullable|numeric|min:0',
            'release_amount' => 'nullable|numeric|min:0',
            // actual_amount is NOT accepted here — it is derived from the
            // sum of expense detail items (see syncActualFromItems()).
        ]);

        $maxOrder = DeliverySupportCost::where('delivery_support_id', $support->id)
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->max('order_sequence') ?? 0;

        $cost = DeliverySupportCost::create(array_merge($validated, [
            'delivery_support_id' => $support->id,
            'order_sequence'      => $maxOrder + 1,
            'actual_amount'       => 0,
        ]));

        $cost->load('children');

        return response()->json([
            'message' => 'Cost item created successfully.',
            'cost'    => $this->formatCost($cost),
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // PUT /delivery/support/{support}/costs/{cost}
    // ──────────────────────────────────────────────────────────────
    public function update(Request $request, DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($cost->delivery_support_id !== $support->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'code'           => 'nullable|string|max:20',
            'name'           => 'required|string|max:200',
            'cost_type'      => 'required|in:indirect,direct',
            'budget'         => 'nullable|numeric|min:0',
            'release_amount' => 'nullable|numeric|min:0',
        ]);

        $cost->update($validated);
        $cost->load('children');

        return response()->json([
            'message' => 'Cost item updated successfully.',
            'cost'    => $this->formatCost($cost),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE /delivery/support/{support}/costs/{cost}
    // ──────────────────────────────────────────────────────────────
    public function destroy(DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($cost->delivery_support_id !== $support->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $cost->delete();

        return response()->json(['message' => 'Cost item deleted successfully.']);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /delivery/support/{support}/costs/init
    // ──────────────────────────────────────────────────────────────
    public function init(DeliverySupport $support)
    {
        $exists = DeliverySupportCost::where('delivery_support_id', $support->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Already initialised.'], 200);
        }

        DB::transaction(function () use ($support) {
            DeliverySupportCost::create([
                'delivery_support_id' => $support->id,
                'parent_id'           => null,
                'code'                => '100',
                'name'                => 'INDIRECT COST',
                'cost_type'           => 'indirect',
                'budget'              => null,
                'release_amount'      => null,
                'actual_amount'       => null,
                'order_sequence'      => 1,
            ]);

            DeliverySupportCost::create([
                'delivery_support_id' => $support->id,
                'parent_id'           => null,
                'code'                => '200',
                'name'                => 'DIRECT COST',
                'cost_type'           => 'direct',
                'budget'              => null,
                'release_amount'      => null,
                'actual_amount'       => null,
                'order_sequence'      => 2,
            ]);
        });

        return response()->json(['message' => 'Cost structure initialised.'], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /delivery/support/{support}/costs/{cost}/items
    // ──────────────────────────────────────────────────────────────
    public function indexItems(DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($cost->delivery_support_id !== $support->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $items = $cost->items()->get();

        // Dokumen lama yang masih berupa path SharePoint langsung → jadikan share link anonim.
        $links = new PlanCostDocumentLink();
        $items->each(fn($i) => $links->ensure($i, $support->onedrive_folder_id));

        return response()->json([
            'items' => $items->map(fn($i) => $this->formatItem($i)),
            'total' => (float) $items->sum('amount'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /delivery/support/{support}/costs/{cost}/items
    // ──────────────────────────────────────────────────────────────
    public function storeItem(Request $request, DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($cost->delivery_support_id !== $support->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:200',
            'amount'      => 'required|numeric|min:0',
            // Tanggal dari kalender; period ("YYYY-MM") diturunkan darinya.
            'expense_date' => 'required|date_format:Y-m-d',
            'document'     => 'nullable|file|max:102400',
        ]);

        $docName = null;
        $docFile = null;
        $docUrl  = null;

        if ($request->hasFile('document')) {
            if (!$support->onedrive_folder_id) {
                return response()->json([
                    'message' => 'OneDrive folder has not been set up for this support. Please create the OneDrive folder first.',
                ], 422);
            }

            try {
                $upload  = $this->uploadDocument($support, $request->file('document'));
                $docName = $upload['name'];
                $docFile = $upload['file_id'];
                $docUrl  = $upload['url'];
            } catch (\Throwable $e) {
                Log::error('Plan Cost OneDrive upload failed (support)', ['error' => $e->getMessage()]);
                return response()->json([
                    'message' => 'Failed to upload document to OneDrive: ' . $e->getMessage(),
                ], 500);
            }
        }

        $item = DeliverySupportCostItem::create([
            'delivery_support_cost_id' => $cost->id,
            'description'              => $validated['description'],
            'amount'                   => $validated['amount'],
            'expense_date'             => $validated['expense_date'],
            'period'                   => substr($validated['expense_date'], 0, 7),
            'document_name'            => $docName,
            'document_file_id'         => $docFile,
            'document_url'             => $docUrl,
        ]);

        $total = $this->syncActualFromItems($cost);

        return response()->json([
            'message' => 'Expense item added.',
            'item'    => $this->formatItem($item),
            'total'   => $total,
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────
    // PUT /delivery/support/{support}/costs/{cost}/items/{item}
    // (frontend kirim POST + X-HTTP-Method-Override:PUT)
    // ──────────────────────────────────────────────────────────────
    public function updateItem(
        Request $request,
        DeliverySupport $support,
        DeliverySupportCost $cost,
        DeliverySupportCostItem $item
    ) {
        if ($cost->delivery_support_id !== $support->id
            || $item->delivery_support_cost_id !== $cost->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'description'     => 'required|string|max:200',
            'amount'          => 'required|numeric|min:0',
            'expense_date'    => 'required|date_format:Y-m-d',
            'document'        => 'nullable|file|max:102400',
            'remove_document' => 'nullable|boolean',
        ]);

        $docName = $item->document_name;
        $docFile = $item->document_file_id;
        $docUrl  = $item->document_url;
        $oldFile = $item->document_file_id;

        if ($request->hasFile('document')) {
            if (!$support->onedrive_folder_id) {
                return response()->json([
                    'message' => 'OneDrive folder has not been set up for this support. Please create the OneDrive folder first.',
                ], 422);
            }

            try {
                $upload  = $this->uploadDocument($support, $request->file('document'));
                $docName = $upload['name'];
                $docFile = $upload['file_id'];
                $docUrl  = $upload['url'];
            } catch (\Throwable $e) {
                Log::error('Plan Cost OneDrive upload failed (support)', ['error' => $e->getMessage()]);
                return response()->json([
                    'message' => 'Failed to upload document to OneDrive: ' . $e->getMessage(),
                ], 500);
            }

            // Nama identik → file lama ditimpa di tempat (ID sama); jangan hapus.
            if ($oldFile && $oldFile !== $docFile) {
                $this->deleteOneDriveFile($oldFile);
            }
        } elseif ($request->boolean('remove_document')) {
            $this->deleteOneDriveFile($oldFile);
            $docName = null;
            $docFile = null;
            $docUrl  = null;
        }

        $item->update([
            'description'      => $validated['description'],
            'amount'           => $validated['amount'],
            'expense_date'     => $validated['expense_date'],
            'period'           => substr($validated['expense_date'], 0, 7),
            'document_name'    => $docName,
            'document_file_id' => $docFile,
            'document_url'     => $docUrl,
        ]);

        $total = $this->syncActualFromItems($cost);

        return response()->json([
            'message' => 'Expense item updated.',
            'item'    => $this->formatItem($item),
            'total'   => $total,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE /delivery/support/{support}/costs/{cost}/items/{item}
    // ──────────────────────────────────────────────────────────────
    public function destroyItem(
        DeliverySupport $support,
        DeliverySupportCost $cost,
        DeliverySupportCostItem $item
    ) {
        if ($cost->delivery_support_id !== $support->id
            || $item->delivery_support_cost_id !== $cost->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // Supporting document ikut dihapus dari OneDrive supaya tidak ada file
        // yatim yang link-nya masih beredar setelah datanya hilang di aplikasi.
        $this->deleteOneDriveFile($item->document_file_id);

        $item->delete();

        $total = $this->syncActualFromItems($cost);

        return response()->json([
            'message' => 'Expense item deleted.',
            'total'   => $total,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // GET /delivery/support/{support}/costs/{cost}/releases
    // Total release + jadwal release bulanan sebuah cost item
    // ──────────────────────────────────────────────────────────────
    public function indexReleases(DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($error = $this->rejectNonLeaf($support, $cost)) {
            return $error;
        }

        return response()->json($this->releasePayload($cost));
    }

    // ──────────────────────────────────────────────────────────────
    // PUT /delivery/support/{support}/costs/{cost}/release
    // Langkah 1 "Atur Release Cost": simpan total release item
    // ──────────────────────────────────────────────────────────────
    public function updateRelease(Request $request, DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($error = $this->rejectNonLeaf($support, $cost)) {
            return $error;
        }

        $validated = $request->validate([
            'release_amount' => 'nullable|numeric|min:0',
        ]);

        $cost->update(['release_amount' => $validated['release_amount']]);

        return response()->json(array_merge(
            ['message' => 'Total release saved.'],
            $this->releasePayload($cost)
        ));
    }

    // ──────────────────────────────────────────────────────────────
    // POST /delivery/support/{support}/costs/{cost}/releases
    // Langkah 2: tambah satu jadwal release bulanan.
    // Total jadwal boleh melebihi total release (Belum dijadwalkan jadi minus).
    // ──────────────────────────────────────────────────────────────
    public function storeRelease(Request $request, DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($error = $this->rejectNonLeaf($support, $cost)) {
            return $error;
        }

        $validated = $request->validate([
            'schedule_date' => 'required|date_format:Y-m-d',
            'amount'        => 'required|numeric|gt:0',
            'notes'         => 'nullable|string|max:255',
        ]);

        DeliverySupportCostReleaseSchedule::create([
            'delivery_support_cost_id' => $cost->id,
            'schedule_date'            => $validated['schedule_date'],
            'period'                   => substr($validated['schedule_date'], 0, 7),
            'amount'                   => $validated['amount'],
            'notes'                    => $validated['notes'] ?? null,
        ]);

        return response()->json(array_merge(
            ['message' => 'Release schedule added.'],
            $this->releasePayload($cost)
        ), 201);
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE /delivery/support/{support}/costs/{cost}/releases/{schedule}
    // ──────────────────────────────────────────────────────────────
    public function destroyRelease(
        DeliverySupport $support,
        DeliverySupportCost $cost,
        DeliverySupportCostReleaseSchedule $schedule
    ) {
        if ($cost->delivery_support_id !== $support->id
            || $schedule->delivery_support_cost_id !== $cost->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $schedule->delete();

        return response()->json(array_merge(
            ['message' => 'Release schedule deleted.'],
            $this->releasePayload($cost)
        ));
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: release hanya diatur di cost leaf (sub-item, atau parent tanpa
    // sub-item). Release parent ber-sub-item adalah agregat anaknya.
    // ──────────────────────────────────────────────────────────────
    private function rejectNonLeaf(DeliverySupport $support, DeliverySupportCost $cost)
    {
        if ($cost->delivery_support_id !== $support->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($cost->children()->exists()) {
            return response()->json([
                'message' => 'Release of this item is the total of its sub-items. Set the release on each sub-item instead.',
            ], 422);
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: payload modal "Atur Release Cost"
    // ──────────────────────────────────────────────────────────────
    private function releasePayload(DeliverySupportCost $cost): array
    {
        $cost->refresh();
        $schedules = $cost->releaseSchedules()->get();
        $total     = $cost->release_amount;
        $scheduled = (float) $schedules->sum('amount');

        return [
            'release_amount' => $total,
            'scheduled'      => $scheduled,
            // Boleh minus bila jadwal melebihi total release.
            'unscheduled'    => (float) ($total ?? 0) - $scheduled,
            'schedules'      => $schedules->map(fn($s) => [
                'id'            => $s->id,
                'schedule_date' => $s->schedule_date?->format('Y-m-d'),
                'period'        => $s->period,
                'amount'        => (float) $s->amount,
                'notes'         => $s->notes,
            ])->values(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: upload supporting document ke subfolder "Plan Cost" milik
    // support, lalu buat share link ANONIM.
    //
    // PENTING: yang disimpan HARUS share link (createShareLink), bukan
    // `webUrl` hasil upload. webUrl adalah path SharePoint langsung — hanya
    // bisa dibuka akun yang punya izin item tersebut, sehingga siapa pun yang
    // lain akan dihadang halaman login / "Request access".
    //
    // @return array{name:string,file_id:string,url:string}
    // ──────────────────────────────────────────────────────────────
    private function uploadDocument(DeliverySupport $support, $file): array
    {
        $oneDrive = new OneDriveService();

        $planCostFolderId = $oneDrive->findOrCreateSubFolderById(
            $support->onedrive_folder_id,
            'Plan Cost'
        );

        $result = $oneDrive->uploadFile(
            $planCostFolderId,
            $file->getClientOriginalName(),
            file_get_contents($file->getRealPath()),
            $file->getMimeType() ?: 'application/octet-stream'
        );

        $link = $oneDrive->createShareLink($result['id'], 'view');

        if ($link['scope'] !== 'anonymous') {
            // Bukan error aplikasi: kebijakan sharing tenant menolak "Anyone".
            Log::warning('Plan Cost document share link is not anonymous (support)', [
                'support_id' => $support->id,
                'file_id'    => $result['id'],
                'scope'      => $link['scope'],
            ]);
        }

        return [
            'name'    => $result['name'],
            'file_id' => $result['id'],
            'url'     => $link['url'],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: hapus file OneDrive tanpa menggagalkan aksi utama.
    // Baris lama belum punya document_file_id → tidak ada yang bisa dihapus.
    // ──────────────────────────────────────────────────────────────
    private function deleteOneDriveFile(?string $fileId): void
    {
        if (!$fileId) {
            return;
        }

        try {
            (new OneDriveService())->deleteItem($fileId);
        } catch (\Throwable $e) {
            Log::warning('Plan Cost OneDrive file delete failed (support)', [
                'file_id' => $fileId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: recompute & persist actual_amount = sum of expense items.
    // ──────────────────────────────────────────────────────────────
    private function syncActualFromItems(DeliverySupportCost $cost): float
    {
        $total = (float) $cost->items()->sum('amount');
        $cost->update(['actual_amount' => $total]);

        return $total;
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: serialise a cost item with all computed fields
    // ──────────────────────────────────────────────────────────────
    private function formatCost(DeliverySupportCost $cost): array
    {
        $hasChildren = $cost->children->isNotEmpty();

        if ($hasChildren) {
            $budget        = $cost->children->sum('budget');
            $releaseAmount = $cost->children->sum('release_amount');
            $actualAmount  = $cost->children->sum('actual_amount');
        } else {
            $budget        = $cost->budget;
            $releaseAmount = $cost->release_amount;
            $actualAmount  = (float) ($cost->actual_amount ?? 0);
        }

        $availBudget = ($budget !== null || $releaseAmount !== null)
            ? (float)($budget ?? 0) - (float)($releaseAmount ?? 0)
            : null;

        $availRelease = ($releaseAmount !== null || (float)($actualAmount ?? 0) > 0)
            ? (float)($releaseAmount ?? 0) - (float)($actualAmount ?? 0)
            : null;

        return [
            'id'              => $cost->id,
            'parent_id'       => $cost->parent_id,
            'code'            => $cost->code,
            'name'            => $cost->name,
            'cost_type'       => $cost->cost_type,
            'order_sequence'  => $cost->order_sequence,
            'has_children'    => $hasChildren,
            'display_budget'  => $budget,
            'display_release' => $releaseAmount,
            'display_actual'  => $actualAmount,
            'budget'          => $hasChildren ? null : $cost->budget,
            'release_amount'  => $hasChildren ? null : $cost->release_amount,
            'actual_amount'   => $hasChildren ? null : $cost->actual_amount,
            'avail_budget'    => $availBudget,
            'avail_release'   => $availRelease,
            'children'        => $cost->children->map(fn($c) => $this->formatCost($c))->values(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: serialise an expense line-item
    // ──────────────────────────────────────────────────────────────
    private function formatItem(DeliverySupportCostItem $item): array
    {
        return [
            'id'            => $item->id,
            'description'   => $item->description,
            'amount'        => $item->amount,
            // "Y-m-d" / "YYYY-MM"; null untuk expense lama sebelum ada kalender.
            'expense_date'  => $item->expense_date?->format('Y-m-d'),
            'period'        => $item->period,
            'document_name' => $item->document_name,
            'document_url'  => $item->document_url,
            'created_at'    => $item->created_at?->format('d M Y'),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: seluruh expense detail support, satu baris per expense,
    // mengikuti urutan cost item di tabel rekap (dipakai export Excel).
    // ──────────────────────────────────────────────────────────────
    private function expenseRows($costs): array
    {
        $rows = [];

        foreach ($costs as $parent) {
            // Leaf = sub-item; parent tanpa sub-item bisa punya expense sendiri.
            $leaves = $parent->children->isNotEmpty() ? $parent->children : collect([$parent]);

            foreach ($leaves as $leaf) {
                foreach ($leaf->items as $item) {
                    $rows[] = [
                        'cost_type'     => $leaf->cost_type,
                        'cost_code'     => $leaf->code,
                        'cost_name'     => $leaf->name,
                        'period'        => $item->period,
                        'expense_date'  => $item->expense_date,
                        'description'   => $item->description,
                        'amount'        => (float) $item->amount,
                        'document_name' => $item->document_name,
                        'document_url'  => $item->document_url,
                        'created_at'    => $item->created_at,
                    ];
                }
            }
        }

        return $rows;
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: grand total summary row
    // ──────────────────────────────────────────────────────────────
    private function buildSummary($costs): array
    {
        $totBudget  = 0;
        $totRelease = 0;
        $totActual  = 0;

        foreach ($costs as $c) {
            if ($c->children->isNotEmpty()) {
                $totBudget  += $c->children->sum('budget');
                $totRelease += $c->children->sum('release_amount');
                $totActual  += $c->children->sum('actual_amount');
            } else {
                $totBudget  += (float)($c->budget ?? 0);
                $totRelease += (float)($c->release_amount ?? 0);
                $totActual  += (float)($c->actual_amount ?? 0);
            }
        }

        return [
            'total_budget'        => $totBudget,
            'total_release'       => $totRelease,
            'total_actual'        => $totActual,
            'total_avail_budget'  => $totBudget  - $totRelease,
            'total_avail_release' => $totRelease - $totActual,
        ];
    }
}
