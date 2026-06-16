<?php

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string) $v);
    }
}

if (!function_exists('truncate_label')) {
    function truncate_label(string $s, int $max = 42): string
    {
        $s = trim($s);
        if (mb_strlen($s) <= $max) {
            return $s;
        }

        return mb_substr($s, 0, $max - 1) . '…';
    }
}

if (!function_exists('format_appt_time')) {
    function format_appt_time(?string $time): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return 'Any time';
        }

        return substr($time, 0, 5);
    }
}

if (!function_exists('format_appt_date')) {
    function format_appt_date(?string $date): string
    {
        $date = trim((string) $date);
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return date('l, F j, Y');
        }

        $ts = strtotime($date);
        return $ts ? date('l, F j, Y', $ts) : $date;
    }
}

if (!function_exists('station_icon')) {
    function station_icon(?string $stationName): string
    {
        return match ((string) $stationName) {
            'Registration' => '🧾',
            'Triage / Vitals' => '🩺',
            'Consultation' => '👩‍⚕️',
            'Pharmacy' => '💊',
            default => '🏥',
        };
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $root = defined('BHC_ROOT') ? BHC_ROOT : dirname(__DIR__);
        $configFile = $root . '/config/app.php';
        if (is_file($configFile)) {
            $app = require $configFile;
            if (is_array($app)) {
                $configuredPath = trim((string) ($app['base_path'] ?? ''));
                if ($configuredPath !== '') {
                    $cached = rtrim(str_replace('\\', '/', $configuredPath), '/');

                    return $cached;
                }

                $baseUrl = trim((string) ($app['base_url'] ?? ''));
                if ($baseUrl !== '' && preg_match('#^https?://[^/]+(/.*)$#i', $baseUrl, $m)) {
                    $path = rtrim(preg_replace('#/index\.php$#', '', $m[1]), '/');
                    if ($path !== '' && $path !== '/') {
                        $cached = $path;

                        return $cached;
                    }
                }
            }
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath !== '.' && $basePath !== '/') {
            $cached = $basePath;

            return $cached;
        }

        $uriPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        if (preg_match('#^(/.+/public)(?:/|$)#', $uriPath, $m)) {
            $cached = $m[1];

            return $cached;
        }

        $cached = '';

        return $cached;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = '/'): string
    {
        $path = $path === '' ? '/' : $path;
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        $base = app_base_path();

        return ($base === '' ? '' : $base) . ($path === '/' ? '/' : $path);
    }
}

if (!function_exists('app_full_url')) {
    function app_full_url(string $path = '/'): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host . app_url($path);
    }
}

if (!function_exists('app_route')) {
    /** URL that always hits public/index.php (reliable for form POST on XAMPP subfolder installs). */
    function app_route(string $path = '/'): string
    {
        $path = $path === '' ? '/' : $path;
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        $base = app_base_path();
        $entry = ($base === '' ? '' : $base) . '/index.php';

        return $path === '/' ? $entry : $entry . $path;
    }
}

if (!function_exists('format_medicine_qty')) {
    function format_medicine_qty(mixed $quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '';
        }

        $value = (float) $quantity;
        if (abs($value - round($value)) < 0.001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $value, bool $withTime = true): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        return $withTime ? date('M j, Y g:i A', $ts) : date('M j, Y', $ts);
    }
}

if (!function_exists('app_name')) {
    function app_name(): string
    {
        return 'Barangay Health Center System';
    }
}

if (!function_exists('app_tagline')) {
    function app_tagline(): string
    {
        return 'Patient registry and queue management for barangay health centers.';
    }
}