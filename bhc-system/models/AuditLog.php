<?php



class AuditLog

{

    public static function log(PDO $db, ?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void

    {

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $json = empty($meta) ? null : json_encode($meta);



        $stmt = $db->prepare(

            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)

             VALUES (:user_id, :action, :entity_type, :entity_id, :metadata_json, :ip_address, :user_agent)"

        );

        $stmt->execute([

            ':user_id' => $userId,

            ':action' => $action,

            ':entity_type' => $entityType,

            ':entity_id' => $entityId,

            ':metadata_json' => $json,

            ':ip_address' => $ip,

            ':user_agent' => $ua ? substr((string) $ua, 0, 255) : null,

        ]);

    }



    /**

     * @param array{action?: string, date_from?: string, date_to?: string} $filters

     * @return array{sql: string, params: array<string, string>}

     */

    public static function filterWhere(array $filters): array

    {

        $parts = [];

        $params = [];



        $action = trim((string) ($filters['action'] ?? ''));

        if ($action !== '') {

            $parts[] = 'al.action = :action';

            $params[':action'] = $action;

        }



        $dateFrom = trim((string) ($filters['date_from'] ?? ''));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {

            $parts[] = 'DATE(al.created_at) >= :date_from';

            $params[':date_from'] = $dateFrom;

        }



        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {

            $parts[] = 'DATE(al.created_at) <= :date_to';

            $params[':date_to'] = $dateTo;

        }



        $sql = $parts === [] ? '' : ('WHERE ' . implode(' AND ', $parts));

        return ['sql' => $sql, 'params' => $params];

    }



    public static function countFiltered(PDO $db, array $filters = []): int

    {

        $where = self::filterWhere($filters);

        $stmt = $db->prepare(

            "SELECT COUNT(*) FROM audit_logs al {$where['sql']}"

        );

        foreach ($where['params'] as $key => $val) {

            $stmt->bindValue($key, $val);

        }

        $stmt->execute();

        return (int) $stmt->fetchColumn();

    }



    public static function paginate(PDO $db, int $limit = 50, int $offset = 0, array $filters = []): array

    {

        $where = self::filterWhere($filters);

        $stmt = $db->prepare(

            "SELECT al.id, al.user_id, u.username, al.action, al.entity_type, al.entity_id, al.metadata_json, al.ip_address, al.created_at

             FROM audit_logs al

             LEFT JOIN users u ON u.id = al.user_id

             {$where['sql']}

             ORDER BY al.id DESC

             LIMIT :limit OFFSET :offset"

        );

        foreach ($where['params'] as $key => $val) {

            $stmt->bindValue($key, $val);

        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }



    public static function distinctActions(PDO $db): array

    {

        $stmt = $db->query(

            "SELECT DISTINCT action FROM audit_logs ORDER BY action ASC"

        );

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'action');

    }

}


