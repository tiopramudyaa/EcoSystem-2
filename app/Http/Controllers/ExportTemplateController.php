<?php

namespace App\Http\Controllers;

use App\Enums\RoleId;
use App\Models\Employee;
use App\Models\ExportTemplate;
use App\Support\ExportColumns;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Template pilihan kolom export, dua jenis:
 *   - Personal: hanya terlihat dan bisa dipakai pemiliknya.
 *   - Global: terlihat dan bisa dipakai semua orang yang membuka export itu.
 *
 * Template hanya memilih kolom. Izin untuk MENGEKSPOR tetap dicek di endpoint
 * export masing-masing list, jadi template Global tidak memberi akses tambahan.
 * Hanya pemilik yang boleh menimpa templatenya; EC Administrator boleh menghapus
 * template Global milik siapa pun (mis. milik karyawan yang sudah keluar).
 */
class ExportTemplateController extends Controller
{
    private function employeeId(): ?int
    {
        $id = session('user.id');

        return $id ? (int) $id : null;
    }

    private function isAdmin(int $employeeId): bool
    {
        return (bool) Employee::find($employeeId)?->hasRole(RoleId::EC_ADMINISTRATOR->value);
    }

    private function canManage(ExportTemplate $t, int $employeeId, bool $admin): bool
    {
        return (int) $t->employee_id === $employeeId || ($admin && $t->is_global);
    }

    private function present(ExportTemplate $t, string $type, int $employeeId, bool $admin, ?string $ownerName): array
    {
        return [
            'id'         => $t->id,
            'name'       => $t->name,
            'scope'      => $t->is_global ? 'global' : 'personal',
            'owner_name' => (int) $t->employee_id === $employeeId ? 'You' : ($ownerName ?: 'Unknown'),
            'can_manage' => $this->canManage($t, $employeeId, $admin),
            // Kolom yang sudah tidak ada di export dibuang diam-diam.
            'columns'    => ExportColumns::sanitize($type, $t->columns),
        ];
    }

    /** Daftar kolom + template yang boleh dipakai user untuk satu jenis export. */
    public function index(string $type): JsonResponse
    {
        $employeeId = $this->employeeId();
        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!ExportColumns::isValidType($type)) {
            abort(404);
        }

        $admin = $this->isAdmin($employeeId);

        $templates = ExportTemplate::where('export_type', $type)
            ->where(fn ($q) => $q->where('employee_id', $employeeId)->orWhere('is_global', true))
            ->with('owner.basicData')
            ->get();

        $rows = $templates
            ->sortBy([
                // Personal dulu, lalu Global; di tiap kelompok urut nama.
                fn ($a, $b) => (int) $a->is_global <=> (int) $b->is_global,
                fn ($a, $b) => strcasecmp($a->name, $b->name),
            ])
            ->map(fn (ExportTemplate $t) => $this->present(
                $t, $type, $employeeId, $admin,
                $t->owner?->basicData?->nick_name ?: $t->owner?->basicData?->first_name
            ))
            ->values();

        return response()->json([
            'success'   => true,
            'columns'   => ExportColumns::catalog($type),
            'templates' => $rows,
        ]);
    }

    /**
     * Simpan template. Nama unik per pemilik per list (Personal dan Global dihitung
     * satu): nama yang sudah Anda pakai ditimpa. Nama template Global juga unik di
     * antara semua pemilik, supaya daftar bersama tidak membingungkan.
     */
    public function store(Request $request, string $type): JsonResponse
    {
        $employeeId = $this->employeeId();
        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!ExportColumns::isValidType($type)) {
            abort(404);
        }

        $data = $request->validate([
            'name'      => 'required|string|max:80',
            'scope'     => 'sometimes|in:personal,global',
            'columns'   => 'required|array|min:1',
            'columns.*' => 'string|max:120',
        ]);

        $name     = trim($data['name']);
        $isGlobal = ($data['scope'] ?? 'personal') === 'global';
        $columns  = ExportColumns::sanitize($type, $data['columns']);

        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'Template name is required.'], 422);
        }
        if (empty($columns)) {
            return response()->json(['success' => false, 'message' => 'Select at least one field.'], 422);
        }

        if ($isGlobal) {
            $clash = ExportTemplate::where('export_type', $type)
                ->where('is_global', true)
                ->where('name', $name)
                ->where('employee_id', '!=', $employeeId)
                ->exists();

            if ($clash) {
                return response()->json([
                    'success' => false,
                    'message' => 'A Global template with this name already exists. Choose another name.',
                ], 422);
            }
        }

        $template = ExportTemplate::updateOrCreate(
            ['employee_id' => $employeeId, 'export_type' => $type, 'name' => $name],
            ['columns' => $columns, 'is_global' => $isGlobal]
        );

        $admin = $this->isAdmin($employeeId);

        return response()->json([
            'success'  => true,
            'template' => $this->present($template, $type, $employeeId, $admin, null),
        ]);
    }

    /** Hapus template. POST, bukan DELETE: verb DELETE diblokir WAF di production. */
    public function destroy(int $id): JsonResponse
    {
        $employeeId = $this->employeeId();
        if (!$employeeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $template = ExportTemplate::find($id);

        // Template Personal orang lain tidak boleh ketahuan keberadaannya.
        $visible = $template && ((int) $template->employee_id === $employeeId || $template->is_global);
        if (!$visible) {
            return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
        }

        if (!$this->canManage($template, $employeeId, $this->isAdmin($employeeId))) {
            return response()->json(['success' => false, 'message' => 'Only the owner can delete this template.'], 403);
        }

        $template->delete();

        return response()->json(['success' => true]);
    }
}
