<?php

class MedicineCatalog
{
    public static function activeList(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT id, name, default_unit
             FROM medicine_catalog
             WHERE is_active = 1
             ORDER BY name ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Prescription picker list — prefers Gawad inventory when integration sync is enabled.
     *
     * @return list<array<string,mixed>>
     */
    public static function pickerList(PDO $db): array
    {
        return self::pickerMeta($db)['items'];
    }

    /** @return array{items: list<array<string,mixed>>, source: string, gawad_error: ?string} */
    public static function pickerMeta(PDO $db): array
    {
        if (GawadIntegration::isMedicineSyncEnabled()) {
            $gawad = GawadIntegration::fetchMedicines();
            if (is_array($gawad)) {
                return [
                    'items' => $gawad,
                    'source' => 'gawad',
                    'gawad_error' => null,
                ];
            }

            return [
                'items' => self::pickerListFromLocal($db),
                'source' => 'local',
                'gawad_error' => GawadIntegration::getLastApiError(),
            ];
        }

        return [
            'items' => self::pickerListFromLocal($db),
            'source' => 'local',
            'gawad_error' => null,
        ];
    }

    /** @return list<array<string,mixed>> */
    private static function pickerListFromLocal(PDO $db): array
    {
        $rows = self::activeList($db);

        return array_map(static function (array $row): array {
            return [
                'id' => (string) ($row['id'] ?? ''),
                'gawad_id' => '',
                'name' => (string) ($row['name'] ?? ''),
                'default_unit' => (string) ($row['default_unit'] ?? 'tablet(s)'),
                'stock_qty' => null,
                'min_stock' => null,
                'is_low_stock' => false,
                'is_out_of_stock' => false,
                'source' => 'local',
            ];
        }, $rows);
    }

    public static function all(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT id, name, default_unit, is_active, created_at
             FROM medicine_catalog
             ORDER BY is_active DESC, name ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT id, name, default_unit, is_active, created_at
             FROM medicine_catalog WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findByName(PDO $db, string $name): ?array
    {
        $stmt = $db->prepare(
            "SELECT id, name, default_unit, is_active
             FROM medicine_catalog WHERE name = :name AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([':name' => trim($name)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(PDO $db, array $data): int
    {
        $stmt = $db->prepare(
            "INSERT INTO medicine_catalog (name, default_unit, stock_qty, min_stock, is_active)
             VALUES (:name, :default_unit, 0, NULL, :is_active)"
        );
        $stmt->execute([
            ':name' => trim((string) ($data['name'] ?? '')),
            ':default_unit' => trim((string) ($data['default_unit'] ?? 'tablet(s)')) ?: 'tablet(s)',
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(PDO $db, int $id, array $data): void
    {
        $stmt = $db->prepare(
            "UPDATE medicine_catalog
             SET name = :name,
                 default_unit = :default_unit,
                 is_active = :is_active
             WHERE id = :id"
        );
        $stmt->execute([
            ':name' => trim((string) ($data['name'] ?? '')),
            ':default_unit' => trim((string) ($data['default_unit'] ?? 'tablet(s)')) ?: 'tablet(s)',
            ':is_active' => !empty($data['is_active']) ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public static function ensureSeedDefaults(PDO $db): void
    {
        $count = (int) $db->query("SELECT COUNT(*) FROM medicine_catalog")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['Paracetamol 500mg', 'tablet(s)'],
            ['Ibuprofen 400mg', 'tablet(s)'],
            ['Ambroxol 30mg', 'tablet(s)'],
            ['Losartan 50mg', 'tablet(s)'],
            ['Amlodipine 5mg', 'tablet(s)'],
            ['Metformin 500mg', 'tablet(s)'],
            ['Oral rehydration salts', 'sachet(s)'],
            ['Loperamide 2mg', 'capsule(s)'],
            ['Cetirizine 10mg', 'tablet(s)'],
            ['Ciprofloxacin 500mg', 'tablet(s)'],
            ['Hydrocortisone 1% cream', 'tube(s)'],
            ['Ferrous sulfate 325mg', 'tablet(s)'],
            ['Multivitamins', 'tablet(s)'],
            ['Folic acid 5mg', 'tablet(s)'],
            ['Iron + folic acid', 'tablet(s)'],
        ];

        foreach ($defaults as [$name, $unit]) {
            self::create($db, [
                'name' => $name,
                'default_unit' => $unit,
                'is_active' => 1,
            ]);
        }
    }
}
