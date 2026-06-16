<?php

class Station
{
    public int $id;
    public string $name;
    public int $sort_order;
    public bool $is_active;

    public static function allActive(PDO $db): array
    {
        $stmt = $db->query("SELECT id, name, sort_order, is_active FROM stations WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare("SELECT id, name, sort_order, is_active FROM stations WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

