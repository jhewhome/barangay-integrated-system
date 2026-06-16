<?php



class AccountController extends Controller

{

    private PDO $db;



    public function __construct()

    {

        $this->db = (new Database())->getConnection();

    }



    public function passwordForm(): void

    {

        $this->requireAuth();

        $user = Auth::user();

        $this->view('account/password', [

            'errors' => [],

        ]);

    }



    public function passwordUpdate(): void

    {

        $this->requireAuth();

        $this->requirePost();



        $authUser = Auth::user();

        $id = (int) ($authUser['id'] ?? 0);

        $user = User::findById($this->db, $id);

        if (!$user) {

            $this->redirect('/login');

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

            $this->view('account/password', ['errors' => $errors]);

            return;

        }



        User::updatePassword($this->db, $id, $password);

        AuditLog::log($this->db, $id, 'user_password_change', 'user', $id, [

            'username' => $user['username'],

        ]);



        $this->redirectWithFlash('/account/password', 'ok', 'Your password has been updated.');
    }

    public function documentNameForm(): void
    {
        $this->requireRole('doctor');
        $doctorId = (int) (Auth::user()['id'] ?? 0);

        $this->view('account/document_name', [
            'doctor' => User::findById($this->db, $doctorId),
            'errors' => [],
        ]);
    }

    public function documentNameUpdate(): void
    {
        $this->requireRole('doctor');
        $this->requirePost();

        $doctorId = (int) (Auth::user()['id'] ?? 0);
        $displayName = trim((string) ($_POST['display_name'] ?? ''));

        if (strlen($displayName) > 100) {
            $this->view('account/document_name', [
                'doctor' => User::findById($this->db, $doctorId),
                'errors' => ['Document name must be 100 characters or less.'],
            ]);
            return;
        }

        User::updateDisplayName($this->db, $doctorId, $displayName);

        AuditLog::log($this->db, $doctorId, 'profile_update', 'user', $doctorId, [
            'field' => 'display_name',
        ]);

        $this->redirectWithFlash(
            '/account/document-name',
            'ok',
            'Document name saved. It will appear on newly issued clinical documents.'
        );
    }

}
