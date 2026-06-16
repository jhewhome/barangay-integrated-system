<?php

class UserController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function index(): void
    {
        $this->requireRole('admin');
        $this->view('users/index', [
            'users' => User::all($this->db),
            'currentUserId' => (int) (Auth::user()['id'] ?? 0),
            'gawadIntegrationEnabled' => GawadIntegration::isEnabled(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin');
        $this->view('users/create', ['errors' => [], 'old' => []]);
    }

    public function store(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password_confirm'] ?? '');
        $role = strtolower(trim((string) ($_POST['role'] ?? 'staff')));

        $errors = $this->validateNewUser($username, $password, $password2, $role);
        if (!empty($errors)) {
            $this->view('users/create', [
                'errors' => $errors,
                'old' => [
                    'username' => $username,
                    'role' => $role,
                    'display_name' => trim((string) ($_POST['display_name'] ?? '')),
                ],
            ]);
            return;
        }

        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $id = User::create($this->db, $username, $password, $role, $displayName !== '' ? $displayName : null);
        AuditLog::log($this->db, (int) Auth::user()['id'], 'user_create', 'user', $id, [
            'username' => $username,
            'role' => $role,
        ]);
        $this->redirect('/users');
    }

    public function deactivate(int $id): void
    {
        $this->requireRole('admin');
        $this->requirePost();
        $this->toggleActive($id, false);
    }

    public function activate(int $id): void
    {
        $this->requireRole('admin');
        $this->requirePost();
        $this->toggleActive($id, true);
    }

    public function passwordForm(int $id): void
    {
        $this->requireRole('admin');
        $user = User::findById($this->db, $id);
        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }
        $this->view('users/password', [
            'target' => $user,
            'errors' => [],
        ]);
    }

    public function passwordUpdate(int $id): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        $user = User::findById($this->db, $id);
        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password_confirm'] ?? '');
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            $this->view('users/password', [
                'target' => $user,
                'errors' => $errors,
            ]);
            return;
        }

        User::updatePassword($this->db, $id, $password);
        AuditLog::log($this->db, (int) Auth::user()['id'], 'user_password_reset', 'user', $id, [
            'username' => $user['username'],
        ]);
        $this->redirect('/users');
    }

    private function toggleActive(int $id, bool $active): void
    {
        $target = User::findById($this->db, $id);
        if (!$target) {
            $this->redirect('/users');
        }

        $currentId = (int) (Auth::user()['id'] ?? 0);
        if (!$active && $id === $currentId) {
            $this->redirect('/users');
        }

        if (!$active && $target['role'] === 'admin' && User::countActiveAdmins($this->db, $id) < 1) {
            $this->redirect('/users');
        }

        User::setActive($this->db, $id, $active);
        AuditLog::log(
            $this->db,
            $currentId,
            $active ? 'user_activate' : 'user_deactivate',
            'user',
            $id,
            ['username' => $target['username']]
        );
        $this->redirect('/users');
    }

  /**
   * @return array<int, string>
   */
    private function validateNewUser(string $username, string $password, string $password2, string $role): array
    {
        $errors = [];
        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3–50 characters (letters, numbers, dot, underscore, hyphen).';
        }
        if (User::usernameExists($this->db, $username)) {
            $errors[] = 'That username is already taken.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }
        if (!in_array($role, ['staff', 'admin', 'doctor'], true)) {
            $errors[] = 'Role must be staff, doctor, or admin.';
        }
        return $errors;
    }

    public function syncFromGawad(): void
    {
        $this->requireRole('admin');
        $this->requirePost();

        if (!GawadIntegration::isEnabled()) {
            $this->redirectWithFlash('/users', 'error', 'Gawad integration is not configured on BHC.');
            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password_confirm'] ?? '');
        $dryRun = !empty($_POST['dry_run']);

        if (strlen($password) < 8) {
            $this->redirectWithFlash('/users', 'error', 'Default password must be at least 8 characters.');
            return;
        }
        if ($password !== $password2) {
            $this->redirectWithFlash('/users', 'error', 'Passwords do not match.');
            return;
        }

        $result = GawadStaffSync::sync(
            $this->db,
            $password,
            $dryRun,
            (int) (Auth::user()['id'] ?? 0)
        );

        $type = !empty($result['error']) ? 'error' : 'ok';
        if (!$dryRun && empty($result['created']) && empty($result['error'])) {
            $type = 'info';
        }

        AuditLog::log($this->db, (int) (Auth::user()['id'] ?? 0), 'gawad_staff_sync', 'user', null, [
            'dry_run' => $dryRun,
            'created_count' => count($result['created']),
            'skipped_count' => count($result['skipped']),
        ]);

        $this->redirectWithFlash('/users', $type, GawadStaffSync::summaryMessage($result));
    }
}
