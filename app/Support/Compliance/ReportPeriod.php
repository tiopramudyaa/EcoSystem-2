<?php

namespace App\Support\Compliance;

use Illuminate\Support\Carbon;

/**
 * The period of the Compliance Management → Reports table and of its export: a preset (today, this month, …) or one the user picks
 * (a day, a month, a year, a date range). Input that does not parse falls back to "all time" instead of failing.
 */
class ReportPeriod
{
    /** key => [label, icon, input the user must fill: date | month | year | range | null] */
    public const OPTIONS = [
        'all' => ['All time', 'infinity', null],
        'today' => ['Today', 'calendar-day', null],
        'yesterday' => ['Yesterday', 'calendar-minus', null],
        'this_week' => ['This week', 'calendar-week', null],
        'last_week' => ['Last week', 'calendar-week', null],
        'this_month' => ['This month', 'calendar', null],
        'last_month' => ['Last month', 'calendar', null],
        'this_year' => ['This year', 'calendar-days', null],
        'day' => ['Choose a day', 'calendar-day', 'date'],
        'month' => ['Choose a month', 'calendar', 'month'],
        'year' => ['Choose a year', 'calendar-days', 'year'],
        'range' => ['Date range', 'arrows-left-right', 'range'],
    ];

    /** @return array{key: string, from: ?Carbon, to: ?Carbon, label: string} from/to are inclusive (start and end of day) */
    public static function resolve(array $in): array
    {
        $key = array_key_exists($in['period'] ?? '', self::OPTIONS) ? $in['period'] : 'all';
        $now = now();

        [$from, $to] = match ($key) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'day' => self::pair(self::date($in['date'] ?? null), 'endOfDay'),
            'month' => self::pair(self::date(($in['month'] ?? '') . '-01'), 'endOfMonth', 'startOfMonth'),
            'year' => self::pair(self::date(((int) ($in['year'] ?? 0)) . '-01-01'), 'endOfYear', 'startOfYear'),
            'range' => self::range($in['from'] ?? null, $in['to'] ?? null),
            default => [null, null],
        };

        if ($from === null && $key !== 'all') {
            $key = 'all';   // the chosen day / month / year / range was empty or invalid
        }

        return ['key' => $key, 'from' => $from, 'to' => $to, 'label' => self::label($key, $from, $to)];
    }

    private static function label(string $key, ?Carbon $from, ?Carbon $to): string
    {
        return match ($key) {
            'all' => 'All time',
            'day', 'today', 'yesterday' => $from->format('d M Y'),
            'month', 'this_month', 'last_month' => $from->format('F Y'),
            'year', 'this_year' => $from->format('Y'),
            default => $from->format('d M Y') . ' – ' . $to->format('d M Y'),
        };
    }

    private static function date(?string $value): ?Carbon
    {
        try {
            return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function pair(?Carbon $start, string $end, string $begin = 'startOfDay'): array
    {
        return $start ? [$start->copy()->$begin(), $start->copy()->$end()] : [null, null];
    }

    private static function range(?string $from, ?string $to): array
    {
        $a = self::date($from);
        $b = self::date($to);

        if (!$a && !$b) {
            return [null, null];
        }

        // One end left empty means "from that day on" / "up to that day".
        $a ??= Carbon::create(2000, 1, 1)->startOfDay();
        $b ??= now()->endOfDay();

        return $a->gt($b) ? [$b->copy()->startOfDay(), $a->copy()->endOfDay()] : [$a, $b->copy()->endOfDay()];
    }
}
