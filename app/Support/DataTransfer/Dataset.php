<?php

namespace App\Support\DataTransfer;

use Closure;
use Illuminate\Http\Request;

/**
 * Definisi satu tabel yang dapat diekspor (dan, bila `import` diisi, diimpor).
 *
 * Kolom: ['key', 'label', 'get' => fn($record), 'format' => text|int|money,
 *         'required', 'hint', 'example', 'parse' => fn($raw), 'rules' => [...]].
 * Bagian `parse` dan `rules` hanya dipakai saat impor.
 */
class Dataset
{
    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<string, mixed>|null  $import  ['permission' => [slug, action], 'key' => kolom, 'find' => fn, 'save' => fn, 'check' => fn?]
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $viewSlug,
        public array $columns,
        public Closure $query,
        public ?array $import = null,
        /** Optional form-like sheet layout (export / import template / normalize), instead of one flat table. */
        public ?object $layout = null,
    ) {
    }

    public function importable(): bool
    {
        return $this->import !== null;
    }

    /** @return array<string> */
    public function headings(): array
    {
        return array_column($this->columns, 'label');
    }

    /**
     * Dropdown options per column index: a column's own `choices`, or Yes/No for a column whose hint says so.
     *
     * @return array<int, array<int, string>>
     */
    public function choices(): array
    {
        $choices = [];

        foreach (array_values($this->columns) as $i => $column) {
            $list = $column['choices'] ?? (str_starts_with((string) ($column['hint'] ?? ''), 'Yes/No') ? ['Yes', 'No'] : null);

            if ($list) {
                $choices[$i] = array_values($list);
            }
        }

        return $choices;
    }

    /** @return array<int, string> */
    public function formats(): array
    {
        $formats = [];

        foreach (array_values($this->columns) as $i => $column) {
            $formats[$i] = $column['format'] ?? 'text';
        }

        return $formats;
    }

    /** @return array<int, array<mixed>> */
    public function rows(Request $request): array
    {
        $rows = [];

        foreach (($this->query)($request) as $record) {
            $rows[] = array_map(fn ($column) => ($column['get'])($record), $this->columns);
        }

        return $rows;
    }

    public function filename(): string
    {
        return str_replace('.', '_', $this->key) . '_' . now()->format('Ymd_His') . '.xlsx';
    }
}
