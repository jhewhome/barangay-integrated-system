<?php

class DailyOperations
{
    public static function resolveDate(array $query = []): string
    {
        $date = (string) ($query['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        return $date;
    }

    /** @return array{waiting:int,serving:int,done:int,skipped:int,total:int} */
    public static function ticketTotals(PDO $db, string $date): array
    {
        $start = $date . ' 00:00:00';
        $end = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $stmt = $db->prepare(
            "SELECT
                SUM(status = 'waiting') AS waiting,
                SUM(status = 'serving') AS serving,
                SUM(status = 'done') AS done,
                SUM(status = 'skipped') AS skipped,
                COUNT(*) AS total
             FROM queue_tickets
             WHERE created_at >= :start AND created_at < :end"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'waiting' => (int) ($r['waiting'] ?? 0),
            'serving' => (int) ($r['serving'] ?? 0),
            'done' => (int) ($r['done'] ?? 0),
            'skipped' => (int) ($r['skipped'] ?? 0),
            'total' => (int) ($r['total'] ?? 0),
        ];
    }

    public static function stationBreakdown(PDO $db, string $date): array
    {
        $start = $date . ' 00:00:00';
        $end = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $sql =
            "SELECT
                s.id AS station_id,
                s.name AS station_name,
                SUM(qt.status = 'waiting') AS waiting,
                SUM(qt.status = 'serving') AS serving,
                SUM(qt.status = 'done') AS done,
                SUM(qt.status = 'skipped') AS skipped,
                COUNT(qt.id) AS total,
                COALESCE(AVG(CASE
                    WHEN qt.called_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, qt.created_at, qt.called_at)
                    ELSE NULL
                END), 0) AS avg_wait_seconds,
                COALESCE(AVG(CASE
                    WHEN qt.called_at IS NOT NULL AND qt.completed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, qt.called_at, qt.completed_at)
                    ELSE NULL
                END), 0) AS avg_service_seconds
             FROM stations s
             LEFT JOIN queue_tickets qt
               ON qt.station_id = s.id
              AND qt.created_at >= :start
              AND qt.created_at < :end
             WHERE s.is_active = 1
             GROUP BY s.id, s.name
             ORDER BY s.sort_order ASC, s.id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'station_id' => (int) ($r['station_id'] ?? 0),
                'station_name' => (string) ($r['station_name'] ?? ''),
                'waiting' => (int) ($r['waiting'] ?? 0),
                'serving' => (int) ($r['serving'] ?? 0),
                'done' => (int) ($r['done'] ?? 0),
                'skipped' => (int) ($r['skipped'] ?? 0),
                'total' => (int) ($r['total'] ?? 0),
                'avg_wait_seconds' => (int) round((float) ($r['avg_wait_seconds'] ?? 0)),
                'avg_service_seconds' => (int) round((float) ($r['avg_service_seconds'] ?? 0)),
            ];
        }
        return $out;
    }

    public static function topReasons(PDO $db, string $date, int $limit = 15): array
    {
        $start = $date . ' 00:00:00';
        $end = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $stmt = $db->prepare(
            "SELECT COALESCE(NULLIF(TRIM(reason), ''), '(No reason recorded)') AS reason_label,
                    COUNT(*) AS ticket_count
             FROM queue_tickets
             WHERE created_at >= :start AND created_at < :end
             GROUP BY reason_label
             ORDER BY ticket_count DESC, reason_label ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array{hour:int,ticket_count:int}> */
    public static function hourlyVolume(PDO $db, string $date): array
    {
        $start = $date . ' 00:00:00';
        $end = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $stmt = $db->prepare(
            "SELECT HOUR(created_at) AS hour_slot, COUNT(*) AS ticket_count
             FROM queue_tickets
             WHERE created_at >= :start AND created_at < :end
             GROUP BY hour_slot
             ORDER BY hour_slot ASC"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['hour_slot']] = (int) $row['ticket_count'];
        }
        $out = [];
        for ($h = 7; $h <= 17; $h++) {
            $out[] = ['hour' => $h, 'ticket_count' => $map[$h] ?? 0];
        }
        return $out;
    }
}
