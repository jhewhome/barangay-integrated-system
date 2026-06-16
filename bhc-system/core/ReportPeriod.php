<?php

class ReportPeriod
{
    public static function isoWeek(): string
    {
        return sprintf('%s-W%02d', date('o'), (int) date('W'));
    }

    /**
     * @param array<string,mixed> $query
     * @return array{
     *   period: string,
     *   start: string,
     *   end: string,
     *   label: string,
     *   date: string,
     *   week: string,
     *   month: string,
     *   from: string,
     *   to: string
     * }
     */
    public static function resolve(array $query = []): array
    {
        $period = (string) ($query['period'] ?? 'month');
        if (!in_array($period, ['day', 'week', 'month', 'custom'], true)) {
            $period = 'month';
        }

        $today = date('Y-m-d');

        if ($period === 'day') {
            $date = (string) ($query['date'] ?? $today);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = $today;
            }
            $dt = DateTime::createFromFormat('Y-m-d', $date) ?: new DateTime($today);
            $endDt = clone $dt;
            $endDt->modify('+1 day');
            return [
                'period' => 'day',
                'start' => $dt->format('Y-m-d'),
                'end' => $endDt->format('Y-m-d'),
                'label' => $dt->format('F j, Y'),
                'date' => $date,
                'week' => self::isoWeekFromDate($dt),
                'month' => $dt->format('Y-m'),
                'from' => $date,
                'to' => $date,
            ];
        }

        if ($period === 'week') {
            $week = (string) ($query['week'] ?? self::isoWeek());
            if (!preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) {
                $week = self::isoWeek();
                preg_match('/^(\d{4})-W(\d{2})$/', $week, $m);
            }
            $dt = new DateTime();
            $dt->setISODate((int) $m[1], (int) $m[2]);
            $start = $dt->format('Y-m-d');
            $endDt = clone $dt;
            $endDt->modify('+7 days');
            $end = $endDt->format('Y-m-d');
            $labelEnd = clone $dt;
            $labelEnd->modify('+6 days');
            return [
                'period' => 'week',
                'start' => $start,
                'end' => $end,
                'label' => $dt->format('M j') . ' – ' . $labelEnd->format('M j, Y'),
                'date' => $today,
                'week' => $week,
                'month' => $dt->format('Y-m'),
                'from' => $start,
                'to' => $labelEnd->format('Y-m-d'),
            ];
        }

        if ($period === 'custom') {
            $from = (string) ($query['from'] ?? date('Y-m-01'));
            $to = (string) ($query['to'] ?? $today);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                $from = date('Y-m-01');
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                $to = $today;
            }
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $toDt = DateTime::createFromFormat('Y-m-d', $to) ?: new DateTime($to);
            $endDt = clone $toDt;
            $endDt->modify('+1 day');
            $fromDt = DateTime::createFromFormat('Y-m-d', $from) ?: new DateTime($from);
            return [
                'period' => 'custom',
                'start' => $from,
                'end' => $endDt->format('Y-m-d'),
                'label' => $fromDt->format('M j, Y') . ' – ' . $toDt->format('M j, Y'),
                'date' => $today,
                'week' => self::isoWeek(),
                'month' => $fromDt->format('Y-m'),
                'from' => $from,
                'to' => $to,
            ];
        }

        // month (default)
        $month = (string) ($query['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $b = ReportMonth::bounds($month);
        return [
            'period' => 'month',
            'start' => $b['start'],
            'end' => $b['end'],
            'label' => $b['label'],
            'date' => $today,
            'week' => self::isoWeek(),
            'month' => $month,
            'from' => $b['start'],
            'to' => date('Y-m-d', strtotime($b['end'] . ' -1 day')),
        ];
    }

    /** @param array<string,mixed> $filter */
    public static function toQuery(array $filter): string
    {
        $params = ['period' => (string) ($filter['period'] ?? 'month')];
        switch ($params['period']) {
            case 'day':
                $params['date'] = (string) ($filter['date'] ?? date('Y-m-d'));
                break;
            case 'week':
                $params['week'] = (string) ($filter['week'] ?? self::isoWeek());
                break;
            case 'custom':
                $params['from'] = (string) ($filter['from'] ?? date('Y-m-01'));
                $params['to'] = (string) ($filter['to'] ?? date('Y-m-d'));
                break;
            default:
                $params['period'] = 'month';
                $params['month'] = (string) ($filter['month'] ?? date('Y-m'));
                break;
        }
        return http_build_query($params);
    }

    /** @param array<string,mixed> $filter */
    public static function fileSlug(array $filter): string
    {
        $period = (string) ($filter['period'] ?? 'month');
        return match ($period) {
            'day' => 'day-' . ($filter['date'] ?? date('Y-m-d')),
            'week' => 'week-' . ($filter['week'] ?? self::isoWeek()),
            'custom' => ($filter['from'] ?? 'from') . '_to_' . ($filter['to'] ?? 'to'),
            default => 'month-' . ($filter['month'] ?? date('Y-m')),
        };
    }

    private static function isoWeekFromDate(DateTime $dt): string
    {
        return sprintf('%s-W%02d', $dt->format('o'), (int) $dt->format('W'));
    }
}
