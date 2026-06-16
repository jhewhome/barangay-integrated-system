<?php

class User
{
    public static function findByUsername(PDO $db, string $username): ?array
    {
        $stmt = $db->prepare(
            "SELECT id, username, password_hash, role, is_active, display_name, created_at
             FROM users WHERE username = :u LIMIT 1"
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findById(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare(
            "SELECT id, username, role, is_active, display_name, created_at
             FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function all(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT id, username, role, is_active, display_name, created_at
             FROM users
             ORDER BY is_active DESC, role ASC, username ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function usernameExists(PDO $db, string $username, ?int $exceptId = null): bool
    {
        $sql = "SELECT id FROM users WHERE username = :u";
        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $params = [':u' => $username];
        if ($exceptId !== null) {
            $params[':id'] = $exceptId;
        }
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function countActiveAdmins(PDO $db, ?int $exceptId = null): int
    {
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1";
        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
        }
        $stmt = $db->prepare($sql);
        if ($exceptId !== null) {
            $stmt->execute([':id' => $exceptId]);
        } else {
            $stmt->execute();
        }
        return (int) $stmt->fetchColumn();
    }

    public static function allDoctors(PDO $db): array
    {
        $stmt = $db->query(
            "SELECT id, username, display_name
             FROM users
             WHERE role = 'doctor' AND is_active = 1
             ORDER BY COALESCE(display_name, username) ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function doctorLabel(array $user): string
    {
        $name = trim((string) ($user['display_name'] ?? ''));
        if ($name !== '') {
            return str_starts_with(strtolower($name), 'dr') ? $name : 'Dr. ' . $name;
        }
        return 'Dr. ' . ($user['username'] ?? '');
    }

    /** Name printed on clinical documents (prescriptions, certificates, referrals). */
    public static function documentName(array $user): string
    {
        $name = trim((string) ($user['display_name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($user['username'] ?? ''));
        }
        if ($name === '') {
            return '';
        }
        if (!str_starts_with(strtolower($name), 'dr')) {
            return 'Dr. ' . $name;
        }
        return $name;
    }

    public static function documentNameFromParts(?string $displayName, ?string $username): string
    {
        return self::documentName([
            'display_name' => $displayName ?? '',
            'username' => $username ?? '',
        ]);
    }

    public static function updateDisplayName(PDO $db, int $id, ?string $displayName): void
    {
        $name = $displayName !== null ? trim($displayName) : '';
        $stmt = $db->prepare('UPDATE users SET display_name = :display_name WHERE id = :id');
        $stmt->execute([
            ':display_name' => $name !== '' ? $name : null,
            ':id' => $id,
        ]);
    }

    public static function create(PDO $db, string $username, string $password, string $role, ?string $displayName = null): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO users (username, password_hash, role, is_active, display_name)
             VALUES (:u, :h, :r, 1, :display_name)"
        );
        $stmt->execute([
            ':u' => $username,
            ':h' => $hash,
            ':r' => $role,
            ':display_name' => $displayName && trim($displayName) !== '' ? trim($displayName) : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function setActive(PDO $db, int $id, bool $active): void
    {
        $stmt = $db->prepare("UPDATE users SET is_active = :a WHERE id = :id");
        $stmt->execute([
            ':a' => $active ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public static function updatePassword(PDO $db, int $id, string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $stmt->execute([':h' => $hash, ':id' => $id]);
    }
}
