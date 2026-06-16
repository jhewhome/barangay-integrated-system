<?php

class AuthController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function loginForm(): void
    {
        if (Auth::check()) {
            $role = (string) (Auth::user()['role'] ?? 'staff');
            $this->redirect($role === 'admin' ? '/admin' : '/staff');
        }
        $this->view('auth/login');
    }

    public function login(): void
    {
        $this->requirePost();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $user = $username === '' ? null : User::findByUsername($this->db, $username);
        if (!$user || (int) $user['is_active'] !== 1 || !password_verify($password, (string) $user['password_hash'])) {
            AuditLog::log($this->db, null, 'login_failed', 'user', null, ['username' => $username]);
            $this->view('auth/login', ['error' => 'Invalid username or password.']);
            return;
        }

        Auth::login($user);
        AuditLog::log($this->db, (int) $user['id'], 'login', 'user', (int) $user['id']);
        $role = (string) ($user['role'] ?? 'staff');
        $this->redirect($role === 'admin' ? '/admin' : '/staff');
    }

    public function logout(): void
    {
        $uid = Auth::user()['id'] ?? null;
        Auth::logout();
        AuditLog::log($this->db, $uid ? (int) $uid : null, 'logout', 'user', $uid ? (int) $uid : null);
        $this->redirect('/login');
    }

    public function gawadSso(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $return = GawadSso::sanitizeReturnPath((string) ($_GET['return'] ?? '/'));

        if ($token === '' || !GawadSso::isEnabled()) {
            $this->redirectWithFlash('/login', 'error', 'Gawad sign-in is not available.');
            return;
        }

        $claims = GawadSso::validateToken($token);
        if ($claims === null) {
            AuditLog::log($this->db, null, 'gawad_sso_failed', 'user', null, ['reason' => 'invalid_token']);
            $this->redirectWithFlash('/login', 'error', 'Gawad sign-in link expired or invalid. Open Health Center again from Gawad BIS.');
            return;
        }

        $user = User::findByUsername($this->db, $claims['username']);
        if (!$user || (int) $user['is_active'] !== 1) {
            AuditLog::log($this->db, null, 'gawad_sso_failed', 'user', null, [
                'username' => $claims['username'],
                'reason' => 'no_matching_user',
            ]);
            $this->redirectWithFlash(
                '/login',
                'error',
                'No active BHC account matches Gawad username "' . $claims['username'] . '". Create a BHC staff account with the same username.'
            );
            return;
        }

        Auth::login($user);
        AuditLog::log($this->db, (int) $user['id'], 'gawad_sso_login', 'user', (int) $user['id']);
        $this->redirect($return);
    }
}

