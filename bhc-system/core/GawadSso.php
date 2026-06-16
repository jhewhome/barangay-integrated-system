<?php

class GawadSso
{
    /** @var array<string,mixed>|null */
    private static ?array $config = null;

    /** @return array<string,mixed> */
    private static function config(): array
    {
        if (self::$config === null) {
            self::$config = require BHC_ROOT . '/config/gawad_integration.php';
        }

        return self::$config;
    }

    public static function isEnabled(): bool
    {
        $cfg = self::config();

        return !empty($cfg['sso_enabled'])
            && trim((string) ($cfg['sso_secret'] ?? '')) !== '';
    }

    /** @return array{username:string,role:?string}|null */
    public static function validateToken(string $token): ?array
    {
        if (!self::isEnabled()) {
            return null;
        }

        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $sigB64] = $parts;
        $secret = (string) self::config()['sso_secret'];
        $expected = self::base64UrlEncode(hash_hmac('sha256', $payloadB64, $secret, true));
        if (!hash_equals($expected, $sigB64)) {
            return null;
        }

        $json = self::base64UrlDecode($payloadB64);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }

        $username = trim((string) ($data['u'] ?? ''));
        $exp = (int) ($data['exp'] ?? 0);
        if ($username === '' || $exp < time()) {
            return null;
        }

        return [
            'username' => $username,
            'role' => isset($data['r']) ? (string) $data['r'] : null,
        ];
    }

    public static function sanitizeReturnPath(string $return): string
    {
        $return = trim($return);
        if ($return === '' || !str_starts_with($return, '/')) {
            return '/';
        }
        if (str_starts_with($return, '//') || str_contains($return, '://')) {
            return '/';
        }

        return $return;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string|false
    {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
