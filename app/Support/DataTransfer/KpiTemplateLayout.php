<?php

namespace App\Support\DataTransfer;

use App\Exports\Tabular\SectionedExport;
use App\Models\KpiScoringScale;
use App\Models\KpiTemplate;
use Illuminate\Http\Request;

/**
 * The KPI template sheet laid out like the template form: for each template a TEMPLATE DETAILS block (label | value),
 * a SCORING SCALES table and an INDICATORS table underneath — so what is the same for the whole template is written
 * once, not on every indicator. The importer reads the same layout back (normalize), turning it into the flat rows
 * the dataset validates and saves.
 */
class KpiTemplateLayout
{
    private const DETAILS    = ['template', 'description', 'target_type', 'period_type', 'deadline_day', 'deadline_month', 'deadline_year',
        'is_anonymous', 'is_active', 'evaluator', 'evaluated', 'target_roles', 'target_positions', 'target_employees',
        'target_projects', 'subject_employees', 'peer_groups'];
    private const INDICATORS = ['order_seq', 'name', 'answer_type', 'rating_max', 'measurement_unit', 'target_value', 'weight', 'indicator_description'];
    private const SCALES     = ['Scale', 'Category', 'Definition', 'Achievement', 'Min %', 'Max %', 'Description'];

    // ── export ──────────────────────────────────────────────────────────────

    public function export(Request $request, array $meta): SectionedExport
    {
        $blocks = [];

        foreach (KpiTemplateDatasets::templates($request) as $t) {
            $blocks[] = [
                'details'    => $this->detailValues($t),
                'scales'     => $this->scaleRows($t),
                'indicators' => $t->indicators->sortBy('order_seq')->map(fn ($i) => $this->indicatorValues($t, $i))->values()->all(),
            ];
        }

        return $this->sheet('KPI Template', 'KPI Template', $meta, $blocks);
    }

    /** The blank import template: one example template. */
    public function example(): SectionedExport
    {
        $columns = collect(KpiTemplateDatasets::columns())->keyBy('key');
        $example = fn (string $key) => $columns[$key]['example'] ?? '';
        $details = [];
        foreach (self::DETAILS as $key) {
            $details[$columns[$key]['label']] = in_array($key, ['evaluator', 'evaluated'], true) ? '(read only — leave as is)' : $example($key);
        }

        return $this->sheet('Data', 'KPI Template — Import Template', [
            'One block per template: edit TEMPLATE DETAILS, SCORING SCALES and INDICATORS (the example below imports as it is). Keep section names and table headings unchanged; the Rating weights must add up to 100. See the "Guide" sheet.',
        ], [[
            'details'    => $details,
            'scales'     => array_map(fn ($s) => [
                $s['scale_value'], $s['category'], $s['definition'], $s['achievement_label'], $s['achievement_min'], $s['achievement_max'], $s['description'],
            ], KpiScoringScale::defaultRows()),
            // A complete example that imports as it is: the Rating weights add up to 100, the Paragraph has none.
            'indicators' => [
                [1, 'Code quality', 'Rating', 5, '%', 90, 40, 'How clean and maintainable the delivered code is'],
                [2, 'On-time delivery', 'Rating', 5, '%', 95, 35, 'Share of tasks finished by their due date'],
                [3, 'Teamwork', 'Rating', 5, null, null, 25, 'Collaboration with the team'],
                [4, 'Comments for the employee', 'Paragraph', null, null, null, 0, 'Free-text feedback, not scored'],
            ],
        ]]);
    }

    private function detailValues(KpiTemplate $t): array
    {
        $columns = collect(KpiTemplateDatasets::columns())->keyBy('key');
        $row     = (object) ['t' => $t, 'i' => null];
        $out     = [];

        foreach (self::DETAILS as $key) {
            $out[$columns[$key]['label']] = ($columns[$key]['get'])($row);
        }

        return $out;
    }

    private function indicatorValues(KpiTemplate $t, $indicator): array
    {
        $columns = collect(KpiTemplateDatasets::columns())->keyBy('key');
        $row     = (object) ['t' => $t, 'i' => $indicator];

        return array_map(fn ($key) => ($columns[$key]['get'])($row), self::INDICATORS);
    }

    /** What the form shows: the template's own scale rows, else the defaults. */
    private function scaleRows(KpiTemplate $t): array
    {
        $scales = $t->scoringScales->isNotEmpty()
            ? $t->scoringScales->sortBy('scale_value')->map(fn ($s) => $s->toArray())->all()
            : KpiScoringScale::defaultRows();

        return array_map(fn ($s) => [
            $s['scale_value'], $s['category'] ?? null, $s['definition'] ?? null, $s['achievement_label'] ?? null,
            isset($s['achievement_min']) ? (float) $s['achievement_min'] : null,
            isset($s['achievement_max']) ? (float) $s['achievement_max'] : null,
            $s['description'] ?? null,
        ], array_values($scales));
    }

    private function sheet(string $sheetTitle, string $title, array $meta, array $blocks): SectionedExport
    {
        $columns = collect(KpiTemplateDatasets::columns())->keyBy('key');
        $heads   = array_map(fn ($k) => $columns[$k]['label'], self::INDICATORS);
        $rows    = [[$title], [implode('   |   ', $meta)], [null]];
        $marks   = ['section' => [], 'label' => [], 'header' => [], 'tables' => [], 'formats' => [], 'lists' => []];
        // Dropdowns of the form, by detail label: assessment type, period, yes/no, deadline date / month.
        $yesNo   = ['Yes', 'No'];
        $dropdowns = [
            $columns['target_type']['label']    => [array_values(KpiTemplateDatasets::TYPES), 'assessment type'],
            $columns['period_type']['label']    => [array_values(KpiTemplateDatasets::PERIODS), 'period type'],
            $columns['is_anonymous']['label']   => [$yesNo, 'value'],
            $columns['is_active']['label']      => [$yesNo, 'value'],
            $columns['deadline_day']['label']   => [range(1, 31), 'date'],
            $columns['deadline_month']['label'] => [range(1, 12), 'month'],
        ];
        $at      = function () use (&$rows) { // row number of the row just added (by reference: an arrow fn would freeze the count)
            return count($rows);
        };

        $lineCount = fn ($v) => max(1, substr_count((string) $v, "\n") + 1 + intdiv(mb_strlen((string) $v), 110));

        foreach ($blocks as $block) {
            $rows[] = ['TEMPLATE DETAILS'];
            $marks['section'][] = $at();
            foreach ($block['details'] as $label => $value) {
                $rows[] = [$label, $value];
                $marks['label'][$at()] = $lineCount($value);
                if (isset($dropdowns[$label])) {
                    $marks['lists'][] = ['B' . $at(), $dropdowns[$label][0], $dropdowns[$label][1]];
                }
            }
            $rows[] = [null];

            $rows[] = ['SCORING SCALES'];
            $marks['section'][] = $at();
            $rows[] = self::SCALES;
            $marks['header'][$at()] = count(self::SCALES);
            $first = $at() + 1;
            $lines = [];
            foreach ($block['scales'] as $scale) {
                $rows[] = $scale;
                $lines[$at()] = max($lineCount($scale[2] ?? ''), $lineCount($scale[6] ?? ''));
            }
            $marks['tables'][] = [$first, $at(), count(self::SCALES), array_filter($lines, fn ($n) => $n > 1)];
            $rows[] = [null];

            $rows[] = ['INDICATORS'];
            $marks['section'][] = $at();
            $rows[] = $heads;
            $marks['header'][$at()] = count($heads);
            $first = $at() + 1;
            $lines = [];
            foreach ($block['indicators'] as $indicator) {
                $rows[] = $indicator;
                $lines[$at()] = max($lineCount($indicator[1] ?? ''), $lineCount($indicator[7] ?? ''));
            }
            $last = $at();
            $marks['tables'][] = [$first, $last, count($heads), array_filter($lines, fn ($n) => $n > 1)];
            if ($last >= $first) {
                $marks['lists'][] = ["C{$first}:C{$last}", array_values(KpiTemplateDatasets::ANSWERS), 'answer type'];
                $marks['formats'][] = ["A{$first}:A{$last}", '0'];
                $marks['formats'][] = ["D{$first}:D{$last}", '0'];
                $marks['formats'][] = ["F{$first}:G{$last}", '#,##0.00'];
            }
            $rows[] = [null];
            $rows[] = [null];
        }

        return new SectionedExport($sheetTitle, $rows, $marks);
    }

    // ── import ──────────────────────────────────────────────────────────────

    /**
     * Turn the sectioned sheet back into the flat table the dataset validates: one header row, then one row per
     * indicator with its template's details and scales repeated. Data rows keep their sheet row number, so an error
     * names the row the user sees. A sheet without the sections (an old flat file) is returned unchanged.
     */
    public function normalize(array $sheet): array
    {
        $isLayout = false;
        foreach ($sheet as $cells) {
            if (mb_strtoupper(trim((string) ($cells[0] ?? ''))) === 'TEMPLATE DETAILS') {
                $isLayout = true;
                break;
            }
        }
        if (!$isLayout) {
            return $sheet;
        }

        $columns   = KpiTemplateDatasets::columns();
        $norm      = fn ($v) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $v)));
        $out       = [0 => array_column($columns, 'label')];
        $details   = $scales = [];
        $section   = null;
        $needsHead = false;
        $indexOf   = [];

        foreach ($sheet as $i => $cells) {
            $first = mb_strtoupper(trim((string) ($cells[0] ?? '')));
            $blank = collect($cells)->every(fn ($c) => $c === null || trim((string) $c) === '');

            if ($first === 'TEMPLATE DETAILS') {
                [$section, $details, $scales, $indexOf] = ['details', [], [], []];
                continue;
            }
            if ($first === 'SCORING SCALES') {
                [$section, $needsHead] = ['scales', true];
                continue;
            }
            if ($first === 'INDICATORS') {
                [$section, $needsHead, $indexOf] = ['indicators', true, []];
                continue;
            }
            if ($blank) {
                $section = $section === 'indicators' ? 'indicators' : null;
                continue;
            }

            switch ($section) {
                case 'details':
                    $details[$norm($cells[0])] = $cells[1] ?? null;
                    break;

                case 'scales':
                    if ($needsHead) {
                        $needsHead = false;
                        break;
                    }
                    $scales[] = implode('|', array_map(fn ($c) => (string) Cell::text($c), array_slice(array_pad($cells, 7, null), 0, 7)));
                    break;

                case 'indicators':
                    if ($needsHead) {
                        $needsHead = false;
                        foreach ($cells as $position => $heading) {
                            $indexOf[$norm($heading)] = $position;
                        }
                        break;
                    }
                    $row = [];
                    foreach ($columns as $column) {
                        $row[] = match (true) {
                            in_array($column['key'], self::INDICATORS, true) => $cells[$indexOf[$norm($column['label'])] ?? -1] ?? null,
                            $column['key'] === 'scales'                       => $scales ? implode("\n", $scales) : null,
                            default                                           => $details[$norm($column['label'])] ?? null,
                        };
                    }
                    $out[$i] = $row;
                    break;
            }
        }

        return $out;
    }
}
