<?php

namespace App\Services\Compliance;

use App\Models\Compliance\ComplianceAlert;
use App\Models\Compliance\ComplianceArticle;
use App\Models\Compliance\ComplianceCategory;
use App\Models\Compliance\ComplianceOption;
use App\Models\Compliance\ComplianceReportStatus;
use App\Models\Compliance\ComplianceRule;
use App\Models\Compliance\ComplianceSession;
use App\Support\Compliance\Tones;

/**
 * Definition of the editable lists of Legal → Compliance. One table + one form per list are drawn from this map, so adding a
 * field means adding one line here (and a migration), not touching three files.
 *
 * field types: text · textarea · html (rich text, sanitised on save) · number · date · time · url · color (hex swatches) · tone (named colour) · bool · select
 *   select `options`: an array, 'categories', or 'list:<name>' (a dropdown the admin manages in Settings — ComplianceOption)
 * column types: text · toggle (an on/off switch that saves at once) · badge · option:<list> (name + colour of a managed choice) · date · rel (a dotted path into a relation)
 * section keys: `group` (heading in the Settings sidebar) · `scope` (where-clause when several sections share one table)
 *   A section ordered by `sort_order` is re-ordered in its table (up / down buttons, drag) — it has no Order field; a new item goes last.
 */
class ComplianceCatalog
{
    public const SECTIONS = ['articles', 'rules', 'alerts', 'sessions', 'statuses', 'categories', 'severities', 'alert_colors', 'session_statuses', 'session_periods'];

    /** The lists on the Settings tab; the others are the Content tab. */
    public const SETTINGS = ['statuses', 'categories', 'severities', 'alert_colors', 'session_statuses', 'session_periods'];

    public static function sections(): array
    {
        return [
            'articles' => [
                'label' => 'Articles', 'icon' => 'book-open', 'model' => ComplianceArticle::class, 'with' => ['category'], 'order' => ['updated_at', 'desc'],
                'noun' => 'article',
                'fields' => [
                    'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => 'categories', 'rules' => 'required|integer|exists:compliance_categories,id'],
                    'title' => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:250', 'span' => 2],
                    'summary' => ['label' => 'Summary', 'type' => 'textarea', 'rules' => 'nullable|string|max:500', 'span' => 2, 'rows' => 2],
                    'content' => ['label' => 'Content (basic HTML: p, h3, ul, ol, li, strong, em, a)', 'type' => 'html', 'rules' => 'required|string|max:200000', 'span' => 2, 'rows' => 12],
                    'tags' => ['label' => 'Tags (comma separated)', 'type' => 'text', 'rules' => 'nullable|string|max:250'],
                    'is_featured' => ['label' => 'Featured on the home page', 'type' => 'bool', 'rules' => 'boolean'],
                    'is_active' => ['label' => 'Visible in the portal', 'type' => 'bool', 'rules' => 'boolean', 'default' => true],
                ],
                'columns' => [
                    ['title', 'Title', 'text'], ['category.name', 'Category', 'rel'], ['is_featured', 'Featured', 'toggle'], ['views', 'Views', 'text'], ['is_active', 'Visible', 'toggle'],
                ],
            ],
            'categories' => [
                'group' => 'Content dropdowns', 'label' => 'Categories', 'icon' => 'folder-tree', 'model' => ComplianceCategory::class, 'with' => [], 'order' => ['sort_order', 'asc'],
                'noun' => 'category',
                'fields' => [
                    'name' => ['label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:120'],
                    'icon' => ['label' => 'Icon (an emoji)', 'type' => 'text', 'rules' => 'nullable|string|max:16'],
                    'color' => ['label' => 'Colour', 'type' => 'color', 'rules' => 'required|regex:/^#[0-9A-Fa-f]{6}$/', 'default' => '#0F7B6C'],
                    'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:400', 'span' => 2, 'rows' => 2],
                    'is_active' => ['label' => 'Visible in the portal', 'type' => 'bool', 'rules' => 'boolean', 'default' => true],
                ],
                'columns' => [['icon', '', 'text'], ['name', 'Name', 'text'], ['description', 'Description', 'text'], ['is_active', 'Visible', 'toggle']],
            ],
            'statuses' => [
                'group' => 'Report handling', 'label' => 'Handling statuses', 'icon' => 'list-check', 'model' => ComplianceReportStatus::class, 'with' => [], 'order' => ['sort_order', 'asc'],
                'noun' => 'status',
                'fields' => [
                    'label' => ['label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:60'],
                    'tone' => ['label' => 'Colour', 'type' => 'tone', 'rules' => 'required|in:' . Tones::valid(), 'default' => 'gray'],
                    'is_final' => ['label' => 'Closes the report (no more follow-up date)', 'type' => 'bool', 'rules' => 'boolean'],
                    'is_active' => ['label' => 'Can be chosen when handling a report', 'type' => 'bool', 'rules' => 'boolean', 'default' => true],
                ],
                'columns' => [['label', 'Name', 'text'], ['tone', 'Colour', 'badge'], ['is_final', 'Closes report', 'toggle'], ['is_active', 'In use', 'toggle']],
            ],
            'severities' => self::optionSection('rule_severity', 'Rule severity', 'gauge-high', 'severity', 'Severity of a Don\'t rule (Content → Do & Don\'t)'),
            'alert_colors' => self::optionSection('alert_color', 'Critical rule colours', 'palette', 'colour', 'Colour of a critical rule card (Content → Critical rules)'),
            'session_statuses' => self::optionSection('session_status', 'Training status', 'list-check', 'training status', 'Status of a training session (Content → Training sessions)'),
            'session_periods' => self::optionSection('session_period', 'Training time of day', 'sun', 'time of day', 'Time of day of a training session (Content → Training sessions)'),
            'rules' => [
                'label' => 'Do & Don\'t', 'icon' => 'scale-balanced', 'model' => ComplianceRule::class, 'with' => ['category'], 'order' => ['sort_order', 'asc'],
                'noun' => 'rule',
                'fields' => [
                    'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['do' => 'Do (you should)', 'dont' => 'Don\'t (you must not)'], 'rules' => 'required|in:do,dont'],
                    'category_id' => ['label' => 'Category', 'type' => 'select', 'options' => 'categories', 'blank' => true, 'rules' => 'nullable|integer|exists:compliance_categories,id'],
                    'title' => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:250', 'span' => 2],
                    'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:3000', 'span' => 2, 'rows' => 3],
                    'severity' => ['label' => 'Severity (Don\'t rules)', 'type' => 'select', 'options' => 'list:rule_severity', 'rules' => 'required|string|max:40', 'default' => 'info'],
                    'is_active' => ['label' => 'Visible in the portal', 'type' => 'bool', 'rules' => 'boolean', 'default' => true],
                ],
                'columns' => [['type', 'Type', 'badge'], ['title', 'Title', 'text'], ['category.name', 'Category', 'rel'], ['severity', 'Severity', 'option:rule_severity'], ['is_active', 'Visible', 'toggle']],
            ],
            'alerts' => [
                'label' => 'Critical rules', 'icon' => 'triangle-exclamation', 'model' => ComplianceAlert::class, 'with' => [], 'order' => ['sort_order', 'asc'],
                'noun' => 'critical rule',
                'fields' => [
                    'icon' => ['label' => 'Icon (an emoji)', 'type' => 'text', 'rules' => 'nullable|string|max:16'],
                    'color_type' => ['label' => 'Colour', 'type' => 'select', 'options' => 'list:alert_color', 'rules' => 'required|string|max:40', 'default' => 'danger'],
                    'title' => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:200', 'span' => 2],
                    'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:500', 'span' => 2, 'rows' => 2],
                    'is_active' => ['label' => 'Visible in the portal', 'type' => 'bool', 'rules' => 'boolean', 'default' => true],
                ],
                'columns' => [['icon', '', 'text'], ['title', 'Title', 'text'], ['color_type', 'Colour', 'option:alert_color'], ['is_active', 'Visible', 'toggle']],
            ],
            'sessions' => [
                'label' => 'Training sessions', 'icon' => 'calendar-days', 'model' => ComplianceSession::class, 'with' => [], 'order' => ['session_date', 'desc'],
                'noun' => 'session',
                'fields' => [
                    'title' => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:250', 'span' => 2],
                    'description' => ['label' => 'Description', 'type' => 'textarea', 'rules' => 'nullable|string|max:3000', 'span' => 2, 'rows' => 3],
                    'session_date' => ['label' => 'Date', 'type' => 'date', 'rules' => 'required|date'],
                    'session_type' => ['label' => 'Time of day', 'type' => 'select', 'options' => 'list:session_period', 'rules' => 'required|string|max:40', 'default' => 'morning'],
                    'start_time' => ['label' => 'Start', 'type' => 'time', 'rules' => 'nullable|date_format:H:i'],
                    'end_time' => ['label' => 'End', 'type' => 'time', 'rules' => 'nullable|date_format:H:i'],
                    'duration_min' => ['label' => 'Duration (minutes)', 'type' => 'number', 'rules' => 'required|integer|min:5|max:600', 'default' => 30, 'step' => 5],
                    'status' => ['label' => 'Status', 'type' => 'select', 'options' => 'list:session_status', 'rules' => 'required|string|max:40', 'default' => 'upcoming'],
                    'facilitator' => ['label' => 'Facilitator', 'type' => 'text', 'rules' => 'nullable|string|max:150'],
                    'location' => ['label' => 'Location', 'type' => 'text', 'rules' => 'nullable|string|max:200'],
                    'meeting_url' => ['label' => 'Meeting link', 'type' => 'url', 'rules' => 'nullable|url:http,https|max:500', 'span' => 2],
                    'recording_url' => ['label' => 'Recording link', 'type' => 'url', 'rules' => 'nullable|url:http,https|max:500', 'span' => 2],
                    'material_url' => ['label' => 'Material link', 'type' => 'url', 'rules' => 'nullable|url:http,https|max:500', 'span' => 2],
                    'notes' => ['label' => 'Notes', 'type' => 'textarea', 'rules' => 'nullable|string|max:3000', 'span' => 2, 'rows' => 2],
                ],
                'columns' => [['session_date', 'Date', 'date'], ['title', 'Title', 'text'], ['facilitator', 'Facilitator', 'text'], ['status', 'Status', 'option:session_status']],
            ],
        ];
    }

    /** A dropdown the admin manages: name, colour, order, on/off. The choices live in compliance_options, one `list` per dropdown. */
    private static function optionSection(string $list, string $label, string $icon, string $noun, string $hint): array
    {
        return [
            'group' => 'Content dropdowns', 'label' => $label, 'icon' => $icon, 'model' => ComplianceOption::class, 'scope' => ['list' => $list], 'with' => [], 'order' => ['sort_order', 'asc'],
            'noun' => $noun, 'hint' => $hint, 'option_list' => $list,
            'fields' => [
                'label' => ['label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:60'],
                'tone' => ['label' => 'Colour', 'type' => 'tone', 'rules' => 'required|in:' . Tones::valid(), 'default' => 'gray'],
                'is_active' => ['label' => 'Can be chosen', 'type' => 'bool', 'rules' => 'boolean', 'default' => true],
            ],
            'columns' => [['label', 'Name', 'text'], ['tone', 'Colour', 'badge'], ['is_active', 'In use', 'toggle']],
        ];
    }

    /** The rows of a section (its `scope` applied). */
    public static function query(array $section)
    {
        return $section['model']::query()->where($section['scope'] ?? []);
    }

    public static function section(string $key): array
    {
        $all = self::sections();
        abort_unless(isset($all[$key]), 404);

        return $all[$key];
    }

    /** Laravel validation rules of a section, keyed by field. */
    public static function rules(array $section): array
    {
        return collect($section['fields'])->map(function ($f) {
            // A dropdown the admin manages accepts any of its choices (a switched-off one too, so an old row can still be saved).
            if (is_string($f['options'] ?? null) && str_starts_with($f['options'], 'list:')) {
                return $f['rules'] . '|in:' . ComplianceOption::list(substr($f['options'], 5))->keys()->implode(',');
            }

            return $f['rules'];
        })->all();
    }
}
