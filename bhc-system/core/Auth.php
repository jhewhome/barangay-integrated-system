<?php

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'role' => (string) ($user['role'] ?? 'staff'),
        ];
        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}

