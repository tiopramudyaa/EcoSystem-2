<?php

namespace App\Services\ObjectFund;

use App\Models\DeliveryProject;
use App\Models\ObjectFund\FundPool;
use App\Models\ObjectFund\ObjectAccessRule;
use App\Models\ObjectFund\ObjectFlowSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Siapa yang boleh membebankan biaya ke sebuah project atau tiket.
 *
 * Tiga mode, dipilih di Reimbursement Settings:
 *   everyone : semua karyawan
 *   team     : lead dan anggota (bawaan; biaya dibebankan oleh orang yang bekerja di Object itu)
 *   lead     : hanya lead
 * Aturan bawaan berlaku per jenis Object, dan dapat dikecualikan per project atau per tiket.
 *
 * Definisi (mengikuti menu Project dan Ticket, tanpa definisi baru):
 *   Project lead : project_manager_id, co_pm_id, delivery_manager_id, delivery_owner_id, dan baris pivot
 *                  ber-role Lead / Project Manager / Co Project Manager yang aktif hari ini.
 *   Project team : lead + project_admin_id + setiap baris pivot yang aktif hari ini.
 *   Ticket lead  : ticket.ticket_lead_id.
 *   Ticket team  : lead + ticket_member yang aktif.
 *
 * Diperiksa di server (pencarian, pratinjau, pengiriman, perubahan dokumen). Untuk dokumen yang dibuat
 * atas nama karyawan, yang diperiksa adalah PEMILIK dokumen, bukan pembuatnya. Office tidak dibatasi.
 */
class ObjectAccessPolicy
{
    public const EVERYONE = 'everyone';
    public const TEAM     = 'team';
    public const LEAD     = 'lead';
    public const MODES    = [self::EVERYONE, self::TEAM, self::LEAD];

    public const MODE_LABELS = [
        self::EVERYONE => 'Everyone',
        self::TEAM     => 'Team (lead and members)',
        self::LEAD     => 'Lead only',
    ];

    /** @var array<string, string>|null */
    private ?array $rules = null;

    /** Mode yang berlaku untuk satu Object: pengecualian bila ada, selain itu aturan bawaan. */
    public function modeFor(string $type, int $ref): string
    {
        return $this->rules()[$type . ':' . $ref] ?? $this->defaultMode($type);
    }

    public function defaultMode(string $type): string
    {
        $settings = ObjectFlowSetting::current();
        $mode     = $type === FundPool::TYPE_PROJECT ? $settings->access_project : $settings->access_support;

        return in_array($mode, self::MODES, true) ? $mode : self::TEAM;
    }

    public function canCharge(int $employeeId, string $type, int $ref): bool
    {
        if (!in_array($type, [FundPool::TYPE_PROJECT, FundPool::TYPE_SUPPORT], true)) {
            return true;
        }

        return match ($this->modeFor($type, $ref)) {
            self::EVERYONE => true,
            self::LEAD     => $this->isLead($employeeId, $type, $ref),
            default        => $this->isTeam($employeeId, $type, $ref),
        };
    }

    public function denyMessage(string $type, string $label, int $ref): string
    {
        $what = $type === FundPool::TYPE_PROJECT ? 'project' : 'ticket';
        $name = $label !== '' ? ' ' . $label : '';

        return $this->modeFor($type, $ref) === self::LEAD
            ? "Only the lead of the {$what}{$name} can charge costs to it. Ask the lead, or ask Finance to change who can charge to it in Reimbursement Settings."
            : "You are not on the team of the {$what}{$name}, so you cannot charge costs to it. Ask its lead to add you, or ask Finance to change who can charge to it in Reimbursement Settings.";
    }

    public function isLead(int $employeeId, string $type, int $ref): bool
    {
        return in_array($ref, $this->ids($employeeId, $type, self::LEAD), true);
    }

    public function isTeam(int $employeeId, string $type, int $ref): bool
    {
        return in_array($ref, $this->ids($employeeId, $type, self::TEAM), true);
    }

    /**
     * Object yang dapat dipilih karyawan.
     *
     * @return array{all: bool, ids: array<int,int>, except: array<int,int>}
     *   all=true  : semua Object kecuali `except`. all=false : hanya `ids`.
     */
    public function scope(int $employeeId, string $type): array
    {
        $default  = $this->defaultMode($type);
        $rulesFor = collect($this->rules())
            ->filter(fn ($mode, $key) => str_starts_with($key, $type . ':'))
            ->mapWithKeys(fn ($mode, $key) => [(int) substr($key, strlen($type) + 1) => $mode]);

        $permitted = fn (string $mode, int $ref) => match ($mode) {
            self::EVERYONE => true,
            self::LEAD     => in_array($ref, $this->ids($employeeId, $type, self::LEAD), true),
            default        => in_array($ref, $this->ids($employeeId, $type, self::TEAM), true),
        };

        if ($default === self::EVERYONE) {
            return [
                'all'    => true,
                'ids'    => [],
                'except' => $rulesFor->filter(fn ($mode, $ref) => !$permitted($mode, (int) $ref))->keys()->map(fn ($v) => (int) $v)->values()->all(),
            ];
        }

        $ids = collect($this->ids($employeeId, $type, $default))->reject(fn ($ref) => $rulesFor->has($ref));

        foreach ($rulesFor as $ref => $mode) {
            if ($permitted($mode, (int) $ref)) {
                $ids->push((int) $ref);
            }
        }

        return ['all' => false, 'ids' => $ids->unique()->values()->all(), 'except' => []];
    }

    /** Terapkan scope pada query yang memiliki kolom id Object. */
    public function restrict($query, string $column, array $scope)
    {
        if ($scope['all']) {
            return $scope['except'] === [] ? $query : $query->whereNotIn($column, $scope['except']);
        }

        return $query->whereIn($column, $scope['ids'] === [] ? [0] : $scope['ids']);
    }

    /** @return array<int,int> */
    public function ids(int $employeeId, string $type, string $level): array
    {
        return $type === FundPool::TYPE_PROJECT
            ? $this->projectIds($employeeId, $level)
            : $this->ticketIds($employeeId, $level);
    }

    /** @return array<int,int> */
    private function projectIds(int $employeeId, string $level): array
    {
        $today = now()->toDateString();
        $lead  = ['Lead', 'Project Manager', 'Co Project Manager'];

        $fk = DeliveryProject::query()
            ->where(function ($q) use ($employeeId, $level) {
                $q->where('project_manager_id', $employeeId)
                  ->orWhere('co_pm_id', $employeeId)
                  ->orWhere('delivery_manager_id', $employeeId)
                  ->orWhere('delivery_owner_id', $employeeId);

                if ($level === self::TEAM) {
                    $q->orWhere('project_admin_id', $employeeId);
                }
            })
            ->pluck('id');

        $pivot = DB::table('delivery_project_employee')
            ->where('employee_id', $employeeId)
            ->when($level === self::LEAD, fn ($q) => $q->whereIn('role', $lead))
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->pluck('delivery_projects_id');

        return $fk->merge($pivot)->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    /** @return array<int,int> */
    private function ticketIds(int $employeeId, string $level): array
    {
        $led = DB::table('ticket')->where('ticket_lead_id', $employeeId)->whereNull('deleted_at')->pluck('ticket_id');

        if ($level === self::LEAD) {
            return $led->map(fn ($v) => (int) $v)->all();
        }

        $member = DB::table('ticket_member')
            ->where('employee_id', $employeeId)
            ->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->pluck('ticket_id');

        return $led->merge($member)->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    /** @return array<string, string> "type:ref" => mode */
    private function rules(): array
    {
        if ($this->rules === null) {
            $this->rules = Schema::hasTable('object_access_rules')
                ? ObjectAccessRule::query()->get()->mapWithKeys(fn ($r) => [$r->object_type . ':' . $r->ref_id => $r->mode])->all()
                : [];
        }

        return $this->rules;
    }

    /** Dipanggil setelah aturan berubah. */
    public function forget(): void
    {
        $this->rules = null;
    }
}
