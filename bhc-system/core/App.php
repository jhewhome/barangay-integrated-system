<?php

class App
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes["GET {$path}"] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes["POST {$path}"] = $handler;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Support subfolder installs (XAMPP) and non-rewrite URLs.
        // Examples:
        // - /bhc_system/public/index.php/ticket/7  -> /ticket/7
        // - /bhc_system/public/ticket/7          -> /ticket/7  (when rewrite works)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($baseDir !== '' && $baseDir !== '.') {
            if (str_starts_with($path, $baseDir)) {
                $path = substr($path, strlen($baseDir));
                if ($path === '') {
                    $path = '/';
                }
            }
        }

        $scriptFile = str_replace('\\', '/', $scriptName);
        if ($scriptFile !== '' && str_contains($path, '/index.php')) {
            $idx = strpos($path, '/index.php');
            if ($idx !== false) {
                $path = substr($path, $idx + strlen('/index.php'));
                if ($path === '') {
                    $path = '/';
                }
            }
        }

        // Normalize trailing slash (except root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $key => $handler) {
            [$routeMethod, $routePattern] = explode(' ', $key, 2);
            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->match($routePattern, $path);
            if ($params === null) {
                continue;
            }

            $handler($params);
            return;
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    /**
     * Matches route patterns like "/queue/{stationId}".
     *
     * @return array<string,string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));
        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];
        foreach ($patternParts as $i => $pp) {
            $sp = $pathParts[$i];
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $pp, $m) === 1) {
                $params[$m[1]] = $sp;
                continue;
            }
            if ($pp !== $sp) {
                return null;
            }
        }

        return $params;
    }
}
