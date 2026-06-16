<?php

class HomeController extends Controller
{
    private function dashboardStats(): array
    {
        $db = (new Database())->getConnection();
        return [
            'patients_total' => Patient::countAll($db),
            'queue_today' => QueueTicket::totalsToday($db),
            'stations_active' => count(Station::allActive($db)),
        ];
    }

    public function index(): void
    {
        if (!Auth::check()) {
            $this->view('home/public', ['stats' => $this->dashboardStats()]);
            return;
        }

        $role = (string) (Auth::user()['role'] ?? 'staff');
        if ($role === 'admin') {
            $this->redirect('/admin');
        }
        if ($role === 'doctor') {
            $this->redirect('/doctor');
        }
        $this->redirect('/staff');
    }

    public function staff(): void
    {
        $this->requireAuth();
        $role = (string) (Auth::user()['role'] ?? '');
        if ($role === 'admin') {
            $this->redirectToRoleDashboard(
                'info',
                'The staff dashboard is for staff accounts. You were redirected to the admin dashboard.'
            );
        }
        if ($role === 'doctor') {
            $this->redirectToRoleDashboard(
                'info',
                'Use the doctor dashboard to view your assigned patients.'
            );
        }
        $db = (new Database())->getConnection();
        $this->view('home/staff', [
            'stats' => $this->dashboardStats(),
            'appointmentsToday' => PatientAppointment::scheduledForDate($db, date('Y-m-d'), 30),
        ]);
    }

    public function admin(): void
    {
        $role = (string) (Auth::user()['role'] ?? '');
        if ($role === 'doctor') {
            $this->redirectToRoleDashboard('info', 'The admin dashboard is for administrators only.');
        }
        $this->requireRole(
            'admin',
            'The admin dashboard is for administrator accounts only. You were redirected to the staff dashboard.'
        );
        $db = (new Database())->getConnection();
        $this->view('home/admin', [
            'stats' => $this->dashboardStats(),
            'appointmentsToday' => PatientAppointment::scheduledForDate($db, date('Y-m-d'), 30),
        ]);
    }
}
