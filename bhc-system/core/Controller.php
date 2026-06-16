<?php

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        $root = defined('BHC_ROOT') ? BHC_ROOT : dirname(__DIR__);
        require_once $root . '/core/helpers.php';
        $viewPath = $root . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: {$view}";
            return;
        }

        extract($data, EXTR_SKIP);
        require $root . '/views/layout/header.php';
        require $viewPath;
        require $root . '/views/layout/footer.php';
    }

    /**
     * Standalone printable document (no app sidebar) — used for medicine receipts.
     */
    protected function partial(string $view, array $data = []): void
    {
        $root = defined('BHC_ROOT') ? BHC_ROOT : dirname(__DIR__);
        require_once $root . '/core/helpers.php';
        $viewPath = $root . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "Partial not found: {$view}";
            return;
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }

    protected function viewReceipt(string $view, array $data = []): void
    {
        $root = defined('BHC_ROOT') ? BHC_ROOT : dirname(__DIR__);
        require_once $root . '/core/helpers.php';
        $viewPath = $root . '/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View not found: {$view}";
            return;
        }

        extract($data, EXTR_SKIP);
        $receiptViewPath = $viewPath;
        require $root . '/views/layout/receipt_document.php';
    }

    protected function redirect(string $path): void
    {
        // Make redirects work for subfolder installs (e.g. /bhc_system/public).
        // Keep absolute URLs untouched.
        if (!preg_match('#^https?://#i', $path)) {
            $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
            $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if ($basePath === '.' || $basePath === '/') $basePath = '';

            if ($path === '') $path = '/';
            if ($path[0] !== '/') $path = '/' . $path;

            // Avoid double-prefixing if already contains basePath.
            if ($basePath !== '' && !str_starts_with($path, $basePath . '/')
                && $path !== $basePath
            ) {
                $path = $basePath . $path;
            }
        }
        header("Location: {$path}");
        exit;
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            exit;
        }
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }

    /**
     * Block doctor accounts from clinic desk / queue management routes.
     */
    protected function requireClinicStaff(?string $denyMessage = null): void
    {
        $this->requireAuth();
        $role = (string) (Auth::user()['role'] ?? '');
        if ($role === 'doctor') {
            $message = $denyMessage
                ?? 'Queue and station management is handled by clinic staff. Use My patients for your consultation workflow.';
            $this->redirectWithFlash('/doctor', 'error', $message);
        }
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function redirectWithFlash(string $path, string $type, string $message): void
    {
        $this->flash($type, $message);
        $this->redirect($path);
    }

    protected function redirectWithFlashAndOpen(string $returnPath, string $documentPath, string $type, string $message): void
    {
        $openUrl = $documentPath;
        if (!str_contains($openUrl, 'print=')) {
            $openUrl .= str_contains($openUrl, '?') ? '&print=1' : '?print=1';
        }

        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
            'open_url' => $this->appUrl($openUrl),
        ];
        $this->redirect($returnPath);
    }

    protected function appUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '.' || $basePath === '/') {
            $basePath = '';
        }

        if ($path === '') {
            $path = '/';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if ($basePath !== '' && !str_starts_with($path, $basePath . '/')
            && $path !== $basePath
        ) {
            $path = $basePath . $path;
        }

        return $path;
    }

    /**
     * Send the user to their role dashboard with an explanatory banner.
     */
    protected function redirectToRoleDashboard(string $type, string $message): void
    {
        $role = (string) (Auth::user()['role'] ?? 'staff');
        $path = match ($role) {
            'admin' => '/admin',
            'doctor' => '/doctor',
            default => '/staff',
        };
        $this->redirectWithFlash($path, $type, $message);
    }

    /**
     * @param string|array<int,string> $roles
     */
    protected function requireRole(string|array $roles, ?string $denyMessage = null): void
    {
        $this->requireAuth();
        $role = (string) (Auth::user()['role'] ?? '');
        $allowed = is_array($roles) ? $roles : [$roles];
        if (in_array($role, $allowed, true)) {
            return;
        }

        $message = $denyMessage ?? 'You do not have permission to access that page. Administrator access is required.';
        $this->redirectToRoleDashboard('error', $message);
    }
}
