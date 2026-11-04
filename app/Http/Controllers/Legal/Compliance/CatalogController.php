<?php

namespace App\Http\Controllers\Legal\Compliance;

use App\Http\Controllers\Controller;
use App\Models\Compliance\ComplianceCategory;
use App\Models\WebReport;
use App\Services\Compliance\ComplianceCatalog;
use App\Support\Compliance\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Shared behaviour of the two editable-list tabs of Compliance Management: Content (ContentController) and Settings (SettingsController).
 * Both show one list at a time (a mini sidebar chooses it) with add / edit / delete; the lists and their fields are defined in
 * ComplianceCatalog. V open the tab · C add · E change · D delete, all on one Menu Access slug (config compliance.slugs.manage).
 *
 * A subclass names its tab and its lists, and may veto a delete (beforeDelete) or adjust / reject a save (prepare).
 */
abstract class CatalogController extends Controller
{
    /** 'content' or 'settings' — also the route name (compliance.manage.{tab}) and the view. */
    abstract protected function tab(): string;

    /** @return string[] the ComplianceCatalog sections of this tab; the first is the default. */
    abstract protected function keys(): array;

    protected function beforeDelete(string $section, Model $row): ?string
    {
        return null;
    }

    /** Adjust $data before it is saved; return a message to refuse the save. */
    protected function prepare(string $section, array &$data, ?int $id): ?string
    {
        return null;
    }

    protected function show(string $key, array $extra = [])
    {
        $section = ComplianceCatalog::section($key);
        [$orderBy, $dir] = $section['order'];

        return view("legal.compliance.management.{$this->tab()}", $extra + [
            'tab' => $this->tab(),
            'sections' => collect($this->keys())->mapWithKeys(fn ($k) => [$k => ComplianceCatalog::section($k)])->all(),
            'key' => $key,
            'section' => $section,
            'rows' => ComplianceCatalog::query($section)->with($section['with'])->orderBy($orderBy, $dir)->orderByDesc('id')->get(),
            'categories' => ComplianceCategory::orderBy('sort_order')->orderBy('name')->pluck('name', 'id'),
            'counts' => collect(ComplianceCatalog::sections())->map(fn ($s) => ComplianceCatalog::query($s)->count()),
        ]);
    }

    protected function currentKey(Request $request): string
    {
        return in_array($request->query('section'), $this->keys(), true) ? $request->query('section') : $this->keys()[0];
    }

    public function store(Request $request, string $section)
    {
        return $this->save($request, $section, null);
    }

    public function update(Request $request, string $section, int $id)
    {
        return $this->save($request, $section, $id);
    }

    public function destroy(string $section, int $id)
    {
        $def = ComplianceCatalog::section($section);
        $row = ComplianceCatalog::query($def)->findOrFail($id);

        if ($problem = $this->beforeDelete($section, $row)) {
            return back()->withErrors(['compliance' => $problem]);
        }

        $row->delete();

        return $this->backTo($section)->with('success', ucfirst($def['noun']) . ' deleted.');
    }

    /** Does this list have an order the admin sets (by moving rows in the table)? */
    protected function isOrdered(array $def): bool
    {
        return ($def['order'][0] ?? '') === 'sort_order';
    }

    /** The final order of the ids; a subclass may pin some rows. @param int[] $ids @param \Illuminate\Support\Collection $rows keyed by id */
    protected function arrange(string $section, array $ids, $rows): array
    {
        return $ids;
    }

    /** Save the order of a list after rows were moved in the table. Takes `ids[]` in the new order; rows not named keep their place at the end. */
    public function reorder(Request $request, string $section)
    {
        $def = ComplianceCatalog::section($section);
        abort_unless($this->isOrdered($def), 404);

        $rows = ComplianceCatalog::query($def)->orderBy('sort_order')->orderBy('id')->get()->keyBy('id');
        $named = collect((array) $request->input('ids', []))->map(fn ($i) => (int) $i)->unique()->filter(fn ($i) => $rows->has($i))->values();
        $ids = $this->arrange($section, $named->merge($rows->keys()->diff($named))->values()->all(), $rows);

        DB::transaction(function () use ($ids, $def) {
            foreach ($ids as $position => $id) {
                DB::table((new $def['model'])->getTable())->where('id', $id)->update(['sort_order' => $position + 1]);
            }
        });
        foreach (['compliance.report-statuses', 'compliance.options'] as $key) {
            Cache::forget($key);
        }

        return response()->json(['ok' => true]);
    }

    /** Switch a yes/no column of a row (visible, featured, closes the report, …) from the table, without opening the row. Answers JSON. */
    public function toggle(Request $request, string $section, int $id)
    {
        $def = ComplianceCatalog::section($section);
        $field = (string) $request->input('field');
        $toggles = collect($def['columns'])->filter(fn ($c) => $c[2] === 'toggle')->pluck(0)->all();
        abort_unless(in_array($field, $toggles, true), 404);

        $row = ComplianceCatalog::query($def)->findOrFail($id);
        $data = $row->only(array_keys($def['fields']));
        $data[$field] = $request->boolean('value');

        if ($problem = $this->prepare($section, $data, $id)) {
            return response()->json(['message' => $problem], 422);
        }

        $row->fill([$field => $data[$field]])->save();

        return response()->json(['value' => (bool) $row->{$field}]);
    }

    private function save(Request $request, string $section, ?int $id)
    {
        $def = ComplianceCatalog::section($section);
        $data = $request->validate(ComplianceCatalog::rules($def));

        foreach ($def['fields'] as $name => $field) {
            if ($field['type'] === 'bool') {
                $data[$name] = $request->boolean($name);
            } elseif ($field['type'] === 'html') {
                $data[$name] = HtmlSanitizer::clean($data[$name] ?? '');
            } elseif ($field['type'] === 'number') {
                $data[$name] = (int) ($data[$name] ?? ($field['default'] ?? 0));
            }
        }

        if ($problem = $this->prepare($section, $data, $id)) {
            return back()->withInput()->withErrors(['compliance' => $problem]);
        }

        if (!$id && $this->isOrdered($def)) {
            $data['sort_order'] = (int) ComplianceCatalog::query($def)->max('sort_order') + 1;   // a new item goes last; moving it is done in the table
        }

        $row = $id ? ComplianceCatalog::query($def)->findOrFail($id) : new $def['model'];
        $row->fill($data)->save();

        return $this->backTo($section)->with('success', ucfirst($def['noun']) . ($id ? ' updated.' : ' added.'));
    }

    private function backTo(string $section)
    {
        return redirect()->route("compliance.manage.{$this->tab()}", ['section' => $section]);
    }

    /** The follow-up deadline shown on the Settings tab. */
    protected function followUpDays(): int
    {
        return WebReport::followUpDays(WebReport::CHANNEL_COMPLIANCE);
    }
}
