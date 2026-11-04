<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Models\Compliance\ComplianceCategory;
use App\Models\Compliance\ComplianceOption;
use App\Models\Compliance\ComplianceReportStatus;
use App\Models\AppConfig;
use App\Services\Compliance\ComplianceCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Legal → Compliance Management → Settings tab (URL /legal/compliance/settings): the options of the module — the statuses a report moves
 * through (the handling dropdown, the Reports filters, the export), the follow-up deadline, the categories My Compliance groups its
 * articles and rules by, and the choices of the dropdowns of the Content tab (rule severity, critical rule colour, training status and time of day). Lists, fields and the add / edit / delete come from CatalogController.
 */
class SettingsController extends CatalogController
{
    protected function tab(): string
    {
        return 'settings';
    }

    protected function keys(): array
    {
        return ComplianceCatalog::SETTINGS;
    }

    public function index(Request $request)
    {
        return $this->show($this->currentKey($request), ['followUpDays' => $this->followUpDays()]);
    }

    /** Days within which a compliance report should get its follow-up. */
    public function followUp(Request $request)
    {
        $days = $request->validate(['follow_up_days' => 'required|integer|min:1|max:365'])['follow_up_days'];
        AppConfig::set('compliance_follow_up_days', (string) $days, 'Days within which a compliance report should get its follow-up');

        return redirect()->route('compliance.manage.settings', ['section' => 'statuses'])->with('success', "Follow-up deadline set to {$days} days. It applies to reports sent from now on.");
    }

    /** `new` is where every report starts: it stays first. */
    protected function arrange(string $section, array $ids, $rows): array
    {
        if ($section !== 'statuses') {
            return $ids;
        }
        $first = $rows->first(fn ($r) => $r->isSystem())?->id;

        return $first ? array_merge([$first], array_values(array_diff($ids, [$first]))) : $ids;
    }

    protected function beforeDelete(string $section, Model $row): ?string
    {
        if ($section === 'categories' && $row->articles()->exists()) {
            return "“{$row->name}” still holds articles. Move or delete them first, or switch the category off instead.";
        }

        if ($row instanceof ComplianceOption) {
            if ($row->is_system) {
                return "“{$row->label}” is built in: the pages depend on it. Rename or recolour it, or switch it off.";
            }
            if ($row->isUsed()) {
                return "“{$row->label}” is used by existing items. Switch it off instead (they keep it, but it can no longer be chosen).";
            }
            if ($row->is_active && !$this->anotherActiveOption($row->list, $row->id)) {
                return 'Keep at least one choice switched on.';
            }
        }

        if ($section === 'statuses') {
            if ($row->isSystem()) {
                return "“{$row->label}” is where every report starts and cannot be deleted.";
            }
            if ($row->isUsed()) {
                return "“{$row->label}” is used by reports. Switch it off instead (it stays on those reports but can no longer be chosen).";
            }
            if ($row->is_final && $row->is_active && !ComplianceReportStatus::where('is_final', true)->where('is_active', true)->where('id', '!=', $row->id)->exists()) {
                return 'Keep at least one status that closes a report.';
            }
        }

        return null;
    }

    protected function prepare(string $section, array &$data, ?int $id): ?string
    {
        if ($section === 'categories') {
            $data['slug'] = $this->uniqueSlug($data['name'], $id);
        }

        if ($section === 'statuses') {
            return $this->prepareStatus($data, $id);
        }

        if ($list = ComplianceCatalog::section($section)['option_list'] ?? null) {
            return $this->prepareOption($list, $data, $id);
        }

        return null;
    }

    /** `new` stays active and open; there is always an active status that closes a report; a new status gets its key from its name. */
    private function prepareStatus(array &$data, ?int $id): ?string
    {
        if ($id && ComplianceReportStatus::findOrFail($id)->isSystem()) {
            $data['is_final'] = false;
            $data['is_active'] = true;
        }

        $closesReports = $data['is_final'] && $data['is_active'];
        $another = ComplianceReportStatus::where('is_final', true)->where('is_active', true)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
        if (!$closesReports && !$another) {
            return 'Keep at least one active status that closes a report.';
        }

        if (!$id) {
            $data['key'] = $this->uniqueStatusKey($data['label']);
        }

        return null;
    }

    /** A choice of a Content dropdown: there is always one switched on; a new one gets its key from its name. */
    private function prepareOption(string $list, array &$data, ?int $id): ?string
    {
        if (!$data['is_active'] && !$this->anotherActiveOption($list, $id)) {
            return 'Keep at least one choice switched on.';
        }

        if (!$id) {
            $data['list'] = $list;
            $base = Str::slug($data['label'], '_') ?: 'choice';
            $key = $base;
            for ($i = 2; ComplianceOption::where('list', $list)->where('key', $key)->exists(); $i++) {
                $key = "{$base}_{$i}";
            }
            $data['key'] = $key;
        }

        return null;
    }

    private function anotherActiveOption(string $list, ?int $ignoreId): bool
    {
        return ComplianceOption::where('list', $list)->where('is_active', true)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
    }

    private function uniqueStatusKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'status';
        $key = $base;
        for ($i = 2; ComplianceReportStatus::where('key', $key)->exists(); $i++) {
            $key = "{$base}_{$i}";
        }

        return $key;
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; ComplianceCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
