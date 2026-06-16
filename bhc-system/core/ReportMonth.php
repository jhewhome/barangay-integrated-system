<?php

class ReportMonth
{
    /** @return array{start: string, end: string, label: string} */
    public static function bounds(string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $start = $month . '-01';
        $dt = DateTime::createFromFormat('Y-m-d', $start) ?: new DateTime('first day of this month');
        $label = $dt->format('F Y');
        $dt->modify('first day of next month');
        return [
            'start' => $start,
            'end' => $dt->format('Y-m-d'),
            'label' => $label,
        ];
    }
}
