<?php

namespace App\Support\DataTransfer;

use App\Models\DeliveryProject;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\KpiIndicator;
use App\Models\KpiScoringScale;
use App\Models\KpiTemplate;
use App\Models\Position;
use App\Support\KpiAssignments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * One KPI template as a flat table: one row per indicator, the template's own settings repeated on every row.
 *
 * Export: one template (`template_id`, from the template form) or, without it, every template.
 * Import: a single file fills EVERYTHING the template form holds — details, deadline, who is evaluated / who evaluates,
 * peer pairs, scoring scales and indicators. A template is matched by name (created when new), an indicator by name
 * inside it (updated in place, so scored evaluations keep their ids).
 *
 * People are written as ECI, roles / positions / projects by name. Lists are separated by `;`.
 * Scales: one per line `value|category|definition|achievement label|min %|max %|description`.
 * Peer pairs: one per line `name|role/position/project|value|ECI,ECI,…`.
 */
class KpiTemplateDatasets
{
    public const TYPES   = ['self' => 'Self-Assessment', 'supervisor' => 'Lead Assessment', 'peer' => 'Peer Assessment', 'upward' => 'Upward Assessment'];
    public const PERIODS = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual'];
    public const ANSWERS = ['rating' => 'Rating', 'paragraph' => 'Paragraph'];
    private const BASES   = ['role', 'position', 'project'];

    /** Template-level columns: identical on every row of a template (checked on import). */
    private const TEMPLATE_KEYS = [
        'template', 'description', 'target_type', 'period_type', 'deadline_day', 'deadline_month', 'deadline_year',
        'is_anonymous', 'is_active', 'scales', 'target_roles', 'target_positions', 'target_employees',
        'target_projects', 'subject_employees', 'peer_groups',
    ];

    private static ?array $lookups = null;

    public static function indicators(): Dataset
    {
        $find = fn (string $name) => KpiTemplate::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        return new Dataset(
            'kpi.template-indicators', 'KPI Template', 'general.kpi-evaluation.templates',
            self::columns(),
            fn (Request $request) => self::rows($request),
            [
                // One template imported: open it in the form (edit page) so the user stays there to review it.
                'landing'    => fn () => count(self::$written) === 1 ? route('general.kpi-evaluation.templates.edit', reset(self::$written)->id, false) : null,
                'normalize'  => fn (array $sheet) => (new KpiTemplateLayout)->normalize($sheet),
                'permission' => ['general.kpi-evaluation.templates.manage', 'view'],
                'key'        => fn (array $v) => mb_strtolower(($v['template'] ?? '') . '|' . ($v['name'] ?? '')),
                'check'      => fn (array $v) => self::checkRow($v),
                'check_all'  => fn (array $rows) => self::checkAll($rows, $find),
                'find'       => fn (array $v) => ($t = $find($v['template']))
                    ? KpiIndicator::where('template_id', $t->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($v['name'])])->first()
                    : null,
                'save'       => fn (array $v, $existing, int $actor) => self::save($v, $existing, $actor, $find),
            ],
            new KpiTemplateLayout
        );
    }

    // ── columns ─────────────────────────────────────────────────────────────

    public static function columns(): array
    {
        $text = fn (string $key, string $label, callable $get, array $extra = []) => array_merge([
            'key' => $key, 'label' => $label, 'get' => $get, 'parse' => fn ($v) => Cell::text($v),
        ], $extra);
        $list = fn (string $key, string $label, callable $get, callable $resolve, string $hint) => [
            'key' => $key, 'label' => $label, 'get' => $get, 'rules' => ['nullable', 'array'], 'hint' => $hint, 'example' => '',
            'parse' => fn ($v) => array_values(array_unique(array_map($resolve, self::split($v)))),
        ];
        $number = fn (string $key, string $label, callable $get, array $rules, string $format = 'int', array $extra = []) => array_merge([
            'key' => $key, 'label' => $label, 'format' => $format, 'get' => $get,
            'parse' => fn ($v) => Cell::number($v), 'rules' => $rules,
        ], $extra);

        return [
            // ── template details ──
            $text('template', 'Template', fn ($r) => $r->t->name, ['required' => true, 'rules' => ['required', 'string', 'max:200'],
                'hint' => 'Template name. An existing name is updated, a new one is created.', 'example' => 'Engineering Staff Monthly KPI']),
            $text('description', 'Template Description', fn ($r) => $r->t->description, ['rules' => ['nullable', 'string'], 'example' => '']),
            ['key' => 'target_type', 'label' => 'Assessment Type', 'get' => fn ($r) => self::TYPES[$r->t->target_type] ?? 'Lead Assessment',
             'parse' => fn ($v) => Cell::choice($v, self::TYPES, 'assessment type') ?? 'supervisor', 'rules' => ['required', Rule::in(array_keys(self::TYPES))],
             'choices' => array_values(self::TYPES), 'hint' => 'Self-Assessment, Lead Assessment, Peer Assessment or Upward Assessment. Blank = Lead.', 'example' => 'Lead Assessment'],
            ['key' => 'period_type', 'label' => 'Period Type', 'get' => fn ($r) => self::PERIODS[$r->t->period_type] ?? 'Monthly',
             'parse' => fn ($v) => Cell::choice($v, self::PERIODS, 'period type') ?? 'monthly', 'rules' => ['required', Rule::in(array_keys(self::PERIODS))],
             'choices' => array_values(self::PERIODS), 'hint' => 'Monthly, Quarterly or Annual. Blank = Monthly.', 'example' => 'Monthly'],
            $number('deadline_day', 'Deadline Date', fn ($r) => $r->t->deadline_day, ['nullable', 'integer', 'min:1', 'max:31'], 'int', ['hint' => 'Day of month, 1–31.', 'example' => 25]),
            $number('deadline_month', 'Deadline Month', fn ($r) => $r->t->deadline_month, ['nullable', 'integer', 'min:1', 'max:12'], 'int', ['hint' => 'Quarterly / Annual only, 1–12.', 'example' => '']),
            $number('deadline_year', 'Deadline Year', fn ($r) => $r->t->deadline_year, ['nullable', 'integer', 'min:2000', 'max:2100'], 'int', ['hint' => 'Annual only.', 'example' => '']),
            ['key' => 'is_anonymous', 'label' => 'Anonymous', 'get' => fn ($r) => $r->t->is_anonymous ? 'Yes' : 'No',
             'parse' => fn ($v) => Cell::yesNo($v), 'rules' => ['boolean'], 'hint' => 'Yes/No (Upward only). Blank = No.', 'example' => 'No'],
            ['key' => 'is_active', 'label' => 'Active', 'get' => fn ($r) => $r->t->is_active ? 'Yes' : 'No',
             'parse' => fn ($v) => Cell::yesNo($v, true), 'rules' => ['boolean'], 'hint' => 'Yes/No. Blank = Yes.', 'example' => 'Yes'],
            ['key' => 'scales', 'label' => 'Scoring Scales', 'get' => fn ($r) => self::scalesText($r->t),
             'parse' => fn ($v) => self::parseScales($v), 'rules' => ['nullable', 'array'],
             'hint' => 'One scale per line: value|category|definition|achievement label|min|max|description. Blank on an existing template = keep its scales; blank on a new one = the default 5 scales.',
             'example' => ''],

            // ── who evaluates / who is evaluated ──
            ['key' => 'evaluator', 'label' => 'Evaluator (who fills)', 'get' => fn ($r) => self::evaluator($r->t),
             'hint' => 'Read-only summary for people; the lists below are what is imported.'],
            ['key' => 'evaluated', 'label' => 'Evaluated (who is scored)', 'get' => fn ($r) => self::evaluated($r->t),
             'hint' => 'Read-only summary for people; the lists below are what is imported.'],
            $list('target_roles', 'Target Roles', fn ($r) => self::names($r->t->target_roles, 'roles'), fn ($n) => self::resolve('roles', $n, 'role'),
                'Role names, separated by ;. Self/Upward = who fills, Lead = who is evaluated. Blank = no role filter.'),
            $list('target_positions', 'Target Positions', fn ($r) => implode('; ', (array) $r->t->target_positions), fn ($n) => self::resolve('positions', $n, 'position'),
                'Position names (Master data › Positions), separated by ;.'),
            $list('target_employees', 'Target Employees', fn ($r) => self::names($r->t->target_employees, 'ecis'), fn ($n) => self::resolve('employees', $n, 'employee ECI'),
                'ECI numbers, separated by ;.'),
            $list('target_projects', 'Target Projects', fn ($r) => self::names($r->t->target_projects, 'projects'), fn ($n) => self::resolve('projects', $n, 'delivery project'),
                'Delivery project names, separated by ;.'),
            $list('subject_employees', 'Counterpart Employees', fn ($r) => self::names($r->t->subject_employees, 'ecis'), fn ($n) => self::resolve('employees', $n, 'employee ECI'),
                'ECI numbers, separated by ;. Lead = the reviewers, Upward = who is rated. Blank = the direct supervisor.'),
            ['key' => 'peer_groups', 'label' => 'Peer Pairs', 'get' => fn ($r) => self::peerText($r->t),
             'parse' => fn ($v) => self::parsePeerGroups($v), 'rules' => ['nullable', 'array'],
             'hint' => 'Peer only. One pair per line: name|role/position/project|value|ECI,ECI,… (2–' . KpiTemplate::PEER_GROUP_MAX . ' people).', 'example' => ''],

            // ── indicators ──
            $number('order_seq', 'Order', fn ($r) => $r->i?->order_seq, ['nullable', 'integer', 'min:1', 'max:255'], 'int',
                ['hint' => 'Position in the template. Blank = after the existing ones.', 'example' => 1]),
            $text('name', 'Indicator', fn ($r) => $r->i?->name, ['required' => true, 'rules' => ['required', 'string', 'max:300'],
                'hint' => 'Indicator name / question. An existing one in the same template is updated.', 'example' => 'Code quality']),
            ['key' => 'answer_type', 'label' => 'Answer Type', 'get' => fn ($r) => $r->i ? (self::ANSWERS[$r->i->answer_type] ?? 'Rating') : null,
             'parse' => fn ($v) => Cell::choice($v, self::ANSWERS, 'answer type') ?? 'rating', 'rules' => ['required', Rule::in(array_keys(self::ANSWERS))],
             'choices' => array_values(self::ANSWERS), 'hint' => 'Rating or Paragraph (free text, no weight). Blank = Rating.', 'example' => 'Rating'],
            $number('rating_max', 'Scale Max', fn ($r) => ($r->i && $r->i->answer_type !== 'paragraph') ? $r->t->scaleMax() : null,
                ['nullable', 'integer', 'min:1', 'max:100'], 'int',
                ['hint' => 'Top score of the rating, as the form shows it (the template scale). A different number is kept as this indicator\'s own override.', 'example' => 5]),
            $text('measurement_unit', 'Measurement Unit', fn ($r) => $r->i?->measurement_unit, ['rules' => ['nullable', 'string', 'max:50'], 'example' => '%']),
            $number('target_value', 'Target Value', fn ($r) => $r->i && $r->i->target_value !== null ? (float) $r->i->target_value : null,
                ['nullable', 'numeric', 'min:0', 'max:99999999'], 'money', ['example' => 90]),
            $number('weight', 'Weight', fn ($r) => $r->i ? (float) $r->i->weight : null, ['required', 'numeric', 'min:0', 'max:100'], 'money',
                ['parse' => fn ($v) => Cell::number($v) ?? 0, 'hint' => 'Percent. The Rating indicators of a template must add up to 100.', 'example' => 25]),
            $text('indicator_description', 'Indicator Description', fn ($r) => $r->i?->description, ['rules' => ['nullable', 'string', 'max:2000'], 'example' => '']),
        ];
    }

    // ── export ──────────────────────────────────────────────────────────────

    /** The template named by `template_id`, or every template. */
    public static function templates(Request $request)
    {
        return KpiTemplate::with(['indicators', 'scoringScales'])
            ->when($request->query('template_id'), fn ($q, $id) => $q->where('id', (int) $id))
            ->orderBy('name')->get();
    }

    private static function rows(Request $request): array
    {
        $rows = [];
        foreach (self::templates($request) as $template) {
            $indicators = $template->indicators->sortBy('order_seq')->values();
            foreach ($indicators->isEmpty() ? [null] : $indicators as $indicator) {
                $rows[] = (object) ['t' => $template, 'i' => $indicator];
            }
        }

        return $rows;
    }

    /** What the form shows: the template's own scale rows, else the defaults. */
    private static function scalesText(KpiTemplate $t): string
    {
        $scales = $t->scoringScales->isNotEmpty()
            ? $t->scoringScales->sortBy('scale_value')->map(fn ($s) => $s->toArray())->all()
            : KpiScoringScale::defaultRows();

        return collect($scales)->map(fn ($s) => implode('|', [
            $s['scale_value'], $s['category'] ?? '', $s['definition'] ?? '', $s['achievement_label'] ?? '',
            $s['achievement_min'] !== null ? (float) $s['achievement_min'] + 0 : '',
            $s['achievement_max'] !== null ? (float) $s['achievement_max'] + 0 : '',
            $s['description'] ?? '',
        ]))->implode("\n");
    }

    private static function peerText(KpiTemplate $t): string
    {
        return collect($t->peerGroups())->map(fn ($g) => implode('|', [
            $g['name'], $g['basis'],
            match ($g['basis']) {
                'role'    => self::lookups()['roles_by_id'][(int) $g['value']] ?? $g['value'],
                'project' => self::lookups()['projects_by_id'][(int) $g['value']] ?? $g['value'],
                default   => $g['value'],
            },
            collect($g['members'])->map(fn ($id) => self::lookups()['eci_by_id'][$id] ?? $id)->implode(','),
        ]))->implode("\n");
    }

    private static function names($ids, string $kind): string
    {
        $map = match ($kind) {
            'roles'    => self::lookups()['roles_by_id'],
            'projects' => self::lookups()['projects_by_id'],
            default    => self::lookups()['eci_by_id'],
        };

        return collect((array) $ids)->map(fn ($id) => $map[(int) $id] ?? $id)->implode('; ');
    }

    private static function audience(KpiTemplate $t): string
    {
        $parts = array_filter([
            self::names($t->target_roles, 'roles') ? 'Roles: ' . self::names($t->target_roles, 'roles') : null,
            $t->target_positions ? 'Positions: ' . implode('; ', $t->target_positions) : null,
            $t->target_employees ? 'Employees: ' . self::names($t->target_employees, 'ecis') : null,
            $t->target_projects ? 'Projects: ' . self::names($t->target_projects, 'projects') : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Everyone';
    }

    private static function counterpart(KpiTemplate $t, string $fallback): string
    {
        return $t->subject_employees ? 'Employees: ' . self::names($t->subject_employees, 'ecis') : $fallback;
    }

    private static function evaluator(KpiTemplate $t): string
    {
        return match ($t->target_type) {
            'self'  => self::audience($t),
            'peer'  => 'Pair members, rating each other',
            'upward' => self::audience($t),
            default => self::counterpart($t, "Each evaluated employee's direct supervisor"),
        };
    }

    private static function evaluated(KpiTemplate $t): string
    {
        return match ($t->target_type) {
            'self'  => 'Themselves',
            'peer'  => 'Pair members, rated by each other',
            'upward' => self::counterpart($t, "Each rater's own direct supervisor"),
            default => self::audience($t),
        };
    }

    // ── import: parsing ─────────────────────────────────────────────────────

    private static function lookups(): array
    {
        return self::$lookups ??= [
            'roles'          => EmployeeRole::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => (int) $id])->all(),
            'roles_by_id'    => EmployeeRole::pluck('name', 'id')->all(),
            'projects'       => DeliveryProject::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => (int) $id])->all(),
            'projects_by_id' => DeliveryProject::pluck('name', 'id')->all(),
            'employees'      => DB::table('employee')->pluck('employee_id', 'eci')->mapWithKeys(fn ($id, $e) => [mb_strtolower((string) $e) => (int) $id])->all(),
            'eci_by_id'      => DB::table('employee')->pluck('eci', 'employee_id')->all(),
            'positions'      => collect(Position::options())->mapWithKeys(fn ($n) => [mb_strtolower($n) => $n])->all(),
        ];
    }

    private static function resolve(string $kind, string $name, string $what)
    {
        $hit = self::lookups()[$kind][mb_strtolower($name)] ?? null;

        if ($hit === null) {
            throw new \InvalidArgumentException("\"{$name}\" is not a known {$what}.");
        }

        return $hit;
    }

    /** @return array<int, string> */
    private static function split($value): array
    {
        $text = (string) Cell::text($value);

        return array_values(array_filter(array_map('trim', preg_split('/[;\n]+/', $text)), fn ($s) => $s !== ''));
    }

    private static function lines($value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) Cell::text($value))), fn ($s) => $s !== ''));
    }

    private static function parseScales($value): array
    {
        $scales = [];

        foreach (self::lines($value) as $n => $line) {
            $p = array_map('trim', explode('|', $line)) + array_fill(0, 7, '');
            if (!ctype_digit($p[0]) || (int) $p[0] < 1 || (int) $p[0] > 100) {
                throw new \InvalidArgumentException('scale line ' . ($n + 1) . ' must start with a whole number 1–100.');
            }
            $scales[] = [
                'scale_value' => (int) $p[0], 'category' => $p[1] ?: null, 'definition' => $p[2] ?: null, 'achievement_label' => $p[3] ?: null,
                'achievement_min' => $p[4] === '' ? null : Cell::number($p[4]), 'achievement_max' => $p[5] === '' ? null : Cell::number($p[5]),
                'description' => $p[6] ?: null,
            ];
        }

        if (count(array_unique(array_column($scales, 'scale_value'))) !== count($scales)) {
            throw new \InvalidArgumentException('a scale value is used twice.');
        }

        return $scales;
    }

    private static function parsePeerGroups($value): array
    {
        $groups = [];

        foreach (self::lines($value) as $n => $line) {
            $p = array_map('trim', explode('|', $line)) + array_fill(0, 4, '');
            $basis = strtolower($p[1]);
            if (!in_array($basis, self::BASES, true)) {
                throw new \InvalidArgumentException('pair ' . ($n + 1) . ': basis must be role, position or project.');
            }
            $groups[] = [
                'name'    => $p[0],
                'basis'   => $basis,
                'value'   => (string) match ($basis) {
                    'role'     => self::resolve('roles', $p[2], 'role'),
                    'project'  => self::resolve('projects', $p[2], 'delivery project'),
                    'position' => self::resolve('positions', $p[2], 'position'),
                },
                'members' => array_values(array_unique(array_map(
                    fn ($eci) => self::resolve('employees', $eci, 'employee ECI'),
                    array_filter(array_map('trim', explode(',', $p[3])), fn ($s) => $s !== '')
                ))),
            ];
        }

        return $groups;
    }

    // ── import: checks ──────────────────────────────────────────────────────

    private static function checkRow(array $v): ?string
    {
        if ($v['target_type'] === 'peer') {
            if (!$v['peer_groups']) {
                return 'A Peer template needs at least one pair (Peer Pairs column).';
            }
            $employees = Employee::with(['basicData', 'roles', 'deliveryProjects'])
                ->whereIn('employee_id', collect($v['peer_groups'])->flatMap(fn ($g) => $g['members'])->unique()->all())->get()->keyBy('employee_id');

            foreach ($v['peer_groups'] as $i => $g) {
                $label = $g['name'] !== '' ? "\"{$g['name']}\"" : 'Pair ' . ($i + 1);
                $n = count($g['members']);
                if ($n < 2 || $n > KpiTemplate::PEER_GROUP_MAX) {
                    return "{$label}: needs 2–" . KpiTemplate::PEER_GROUP_MAX . ' people.';
                }
                foreach ($g['members'] as $id) {
                    $e  = $employees->get($id);
                    $ok = $e && match ($g['basis']) {
                        'role'     => $e->roles->pluck('id')->contains((int) $g['value']),
                        'position' => ($e->basicData?->position ?? '') === $g['value'],
                        'project'  => $e->deliveryProjects->pluck('id')->contains((int) $g['value']),
                    };
                    if (!$ok) {
                        return "{$label}: " . ($e?->eci ?? "#{$id}") . " doesn't belong to the chosen {$g['basis']}.";
                    }
                }
            }
        } elseif ($v['target_type'] !== 'self') {
            $both = array_intersect($v['subject_employees'] ?? [], $v['target_employees'] ?? []);
            if ($both) {
                $ecis = collect($both)->map(fn ($id) => self::lookups()['eci_by_id'][$id] ?? $id)->implode(', ');

                return "An employee can't be both a filler and the subject of the same template: {$ecis}.";
            }
        }

        return null;
    }

    private static function checkAll(array $rows, \Closure $find): ?string
    {
        $seen = [];

        foreach ($rows as $v) {
            $part = json_encode(array_intersect_key($v, array_flip(self::TEMPLATE_KEYS)));
            $id   = mb_strtolower($v['template']);

            if (isset($seen[$id]['part']) && $seen[$id]['part'] !== $part) {
                return "\"{$v['template']}\": its details differ between rows. Template columns must be identical on every row of a template.";
            }
            $seen[$id]['part'] = $part;
            $seen[$id]['name'] = $v['template'];
            $seen[$id]['rows'][mb_strtolower($v['name'])] = $v['answer_type'] === 'paragraph' ? 0.0 : (float) $v['weight'];
        }

        foreach ($seen as $info) {
            // Indicators already on an existing template and not in the file keep their weight.
            $kept = 0.0;
            if ($template = $find($info['name'])) {
                $kept = KpiIndicator::where('template_id', $template->id)->where('answer_type', 'rating')->get()
                    ->reject(fn ($i) => isset($info['rows'][mb_strtolower($i->name)]))->sum('weight');
            }
            $total = $kept + array_sum($info['rows']);

            if (abs($total - 100) > 0.01) {
                return "Scored weights of \"{$info['name']}\" would add up to " . rtrim(rtrim(number_format($total, 2), '0'), '.') . '%, not 100%. Fix the Weight column of the INDICATORS table (Paragraph rows need no weight).';
            }
        }

        return null;
    }

    // ── import: saving ──────────────────────────────────────────────────────

    /** @var array<string, KpiTemplate> templates already written during this import */
    private static array $written = [];

    private static function save(array $v, ?KpiIndicator $existing, int $actor, \Closure $find)
    {
        $template = self::writeTemplate($v, $actor, $find);
        $divisor  = (int) ($template->score_divisor ?: 5);
        $paragraph = $v['answer_type'] === 'paragraph';

        $data = [
            'name' => $v['name'], 'answer_type' => $v['answer_type'],
            // Same as the template scale = follow it (the form has no per-indicator scale).
            'rating_max' => ($paragraph || !$v['rating_max'] || (int) $v['rating_max'] === $divisor) ? null : (int) $v['rating_max'],
            'measurement_unit' => $v['measurement_unit'], 'target_value' => $paragraph ? null : $v['target_value'],
            'weight' => $paragraph ? 0 : (float) $v['weight'], 'description' => $v['indicator_description'],
        ];
        if ($v['order_seq']) {
            $data['order_seq'] = (int) $v['order_seq'];
        }

        if ($existing) {
            return tap($existing)->update($data);
        }

        $data['order_seq'] ??= (int) KpiIndicator::where('template_id', $template->id)->max('order_seq') + 1;

        return KpiIndicator::create($data + ['template_id' => $template->id]);
    }

    private static function writeTemplate(array $v, int $actor, \Closure $find): KpiTemplate
    {
        $id = mb_strtolower($v['template']);
        if (isset(self::$written[$id])) {
            return self::$written[$id];
        }

        $type     = $v['target_type'];
        $existing = $find($v['template']);
        $day      = $v['deadline_day'] ? (int) $v['deadline_day'] : null;
        $scales   = $v['scales'] ?: ($existing ? [] : KpiScoringScale::defaultRows());
        $list     = fn ($ids) => $ids ? array_values(array_unique(array_map('strval', $ids))) : null;

        $data = [
            'name' => $v['template'], 'description' => $v['description'],
            'period_type' => $v['period_type'], 'target_type' => $type,
            'deadline_day'   => $day,
            'deadline_month' => $day && in_array($v['period_type'], ['quarterly', 'annual'], true) && $v['deadline_month'] ? (int) $v['deadline_month'] : null,
            'deadline_year'  => $day && $v['period_type'] === 'annual' && $v['deadline_year'] ? (int) $v['deadline_year'] : null,
            'is_anonymous'   => (bool) $v['is_anonymous'], 'is_active' => (bool) $v['is_active'],
            // Peer templates are driven by pairs alone; Self has no counterpart.
            'target_roles'      => $type === 'peer' ? null : $list($v['target_roles']),
            'target_positions'  => $type === 'peer' ? null : ($v['target_positions'] ?: null),
            'target_employees'  => $type === 'peer' ? null : $list($v['target_employees']),
            'target_projects'   => $type === 'peer' ? null : $list($v['target_projects']),
            'subject_employees' => in_array($type, ['self', 'peer'], true) ? null : $list($v['subject_employees']),
            'peer_groups'       => $type === 'peer' ? $v['peer_groups'] : null,
            'updated_by'        => $actor,
        ];
        if ($scales) {
            $data['score_divisor'] = count($scales);
        }

        if ($existing) {
            $existing->update($data);
            $template = $existing;
        } else {
            $template = KpiTemplate::create($data + ['score_divisor' => $data['score_divisor'] ?? 5, 'created_by' => $actor]);
        }

        if ($scales) {
            $template->scoringScales()->delete();
            foreach (array_values($scales) as $seq => $s) {
                KpiScoringScale::create(array_intersect_key($s, array_flip([
                    'scale_value', 'category', 'definition', 'achievement_label', 'achievement_min', 'achievement_max', 'description',
                ])) + ['template_id' => $template->id, 'order_seq' => $seq + 1]);
            }
        }

        if (!$existing) {
            // A new template is materialised for the current period, as when it is created in the form.
            KpiAssignments::syncPeriod(now()->format('Y-m'), $actor, true);
        }

        return self::$written[$id] = $template->refresh();
    }
}
