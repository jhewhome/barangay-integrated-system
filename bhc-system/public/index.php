<?php

// XAMPP: index in public/ with app folders one level up.
// InfinityFree: everything inside htdocs/ (config, views, etc. next to index.php).
if (!defined('BHC_ROOT')) {
    define('BHC_ROOT', is_dir(__DIR__ . '/config') ? __DIR__ : dirname(__DIR__));
}

require_once BHC_ROOT . '/config/database.php';
require_once BHC_ROOT . '/core/helpers.php';
require_once BHC_ROOT . '/core/App.php';
require_once BHC_ROOT . '/core/Controller.php';
require_once BHC_ROOT . '/core/Auth.php';
require_once BHC_ROOT . '/core/GawadIntegration.php';
require_once BHC_ROOT . '/core/GawadSso.php';
require_once BHC_ROOT . '/core/GawadStaffSync.php';
require_once BHC_ROOT . '/core/ReportMonth.php';
require_once BHC_ROOT . '/core/ReportPeriod.php';

require_once BHC_ROOT . '/models/Station.php';
require_once BHC_ROOT . '/models/Patient.php';
require_once BHC_ROOT . '/models/QueueTicket.php';
require_once BHC_ROOT . '/models/User.php';
require_once BHC_ROOT . '/models/AuditLog.php';
require_once BHC_ROOT . '/models/PatientAppointment.php';
require_once BHC_ROOT . '/models/ConsultationRecord.php';
require_once BHC_ROOT . '/models/MedicineDispensing.php';
require_once BHC_ROOT . '/models/MedicineCatalog.php';
require_once BHC_ROOT . '/models/DoctorComment.php';
require_once BHC_ROOT . '/models/ClinicalDocument.php';
require_once BHC_ROOT . '/models/PatientVisit.php';
require_once BHC_ROOT . '/models/TriageRecord.php';
require_once BHC_ROOT . '/models/DailyOperations.php';
require_once BHC_ROOT . '/models/PatientAccess.php';

require_once BHC_ROOT . '/controllers/HomeController.php';
require_once BHC_ROOT . '/controllers/AuthController.php';
require_once BHC_ROOT . '/controllers/AuditController.php';
require_once BHC_ROOT . '/controllers/ReportController.php';
require_once BHC_ROOT . '/controllers/PatientController.php';
require_once BHC_ROOT . '/controllers/QueueController.php';
require_once BHC_ROOT . '/controllers/CoordinatorController.php';
require_once BHC_ROOT . '/controllers/UserController.php';
require_once BHC_ROOT . '/controllers/AccountController.php';
require_once BHC_ROOT . '/controllers/AppointmentController.php';
require_once BHC_ROOT . '/controllers/ClinicalController.php';
require_once BHC_ROOT . '/controllers/DoctorController.php';
require_once BHC_ROOT . '/controllers/MedicineCatalogController.php';

session_start();

$app = new App();

$home = new HomeController();
$auth = new AuthController();
$audit = new AuditController();
$reports = new ReportController();
$patients = new PatientController();
$queue = new QueueController();
$coordinator = new CoordinatorController();
$users = new UserController();
$account = new AccountController();
$appointments = new AppointmentController();
$clinical = new ClinicalController();
$doctor = new DoctorController();
$medicines = new MedicineCatalogController();

// Home / dashboards
$app->get('/', fn () => $home->index());
$app->get('/staff', fn () => $home->staff());
$app->get('/admin', fn () => $home->admin());
$app->get('/doctor', fn () => $doctor->index());
$app->get('/doctor/queue-snapshot', fn () => $doctor->queueSnapshot());

// Auth
$app->get('/login', fn () => $auth->loginForm());
$app->post('/login', fn () => $auth->login());
$app->get('/logout', fn () => $auth->logout());
$app->get('/auth/gawad', fn () => $auth->gawadSso());

// Patients
$app->get('/patients', fn () => $patients->index());
$app->get('/patients/create', fn () => $patients->create());
$app->get('/patients/search', fn () => $patients->search());
$app->get('/patients/{id}/appointment-today', fn ($p) => $patients->appointmentToday((int) $p['id']));
$app->get('/patients/{id}/queue-status', fn ($p) => $patients->queueStatus((int) $p['id']));
$app->post('/patients', fn () => $patients->store());
$app->get('/patients/{id}/edit', fn ($p) => $patients->edit((int) $p['id']));
$app->get('/patients/{id}/history', fn ($p) => $patients->history((int) $p['id']));
$app->post('/patients/{id}/update', fn ($p) => $patients->update((int) $p['id']));
$app->post('/patients/{id}/link-gawad', fn ($p) => $patients->linkGawad((int) $p['id']));
$app->post('/patients/{id}/archive', fn ($p) => $patients->archive((int) $p['id']));
$app->post('/patients/{id}/restore', fn ($p) => $patients->restore((int) $p['id']));
$app->post('/patients/{id}/appointments', fn ($p) => $appointments->storeForPatient((int) $p['id']));
$app->post('/patients/{id}/consultations', fn ($p) => $clinical->storeConsultationForPatient((int) $p['id']));

// Appointments (clinic-wide)
$app->get('/appointments', fn () => $appointments->index());
$app->post('/appointments/{id}/status', fn ($p) => $appointments->updateStatus((int) $p['id']));

// Clinical records (consultation / pharmacy)
$app->get('/clinical/receipt/{id}', fn ($p) => $clinical->receipt((int) $p['id']));
$app->get('/clinical/documents/{id}', fn ($p) => $clinical->document((int) $p['id']));
$app->get('/clinical/documents/{id}/print', fn ($p) => $clinical->document((int) $p['id'], true));
$app->post('/clinical/consultations/{id}/issue-receipt', fn ($p) => $clinical->issueReceipt((int) $p['id']));
$app->post('/queue/{stationId}/consultation/{ticketId}', fn ($p) => $clinical->storeConsultation((int) $p['stationId'], (int) $p['ticketId']));
$app->post('/queue/{stationId}/triage/{ticketId}', fn ($p) => $queue->storeTriage((int) $p['stationId'], (int) $p['ticketId']));
$app->post('/queue/{stationId}/dispense/{ticketId}', fn ($p) => $clinical->storeDispensing((int) $p['stationId'], (int) $p['ticketId']));
$app->post('/queue/{stationId}/assign-doctor/{ticketId}', fn ($p) => $queue->assignDoctor((int) $p['stationId'], (int) $p['ticketId']));

// Doctor portal
$app->post('/doctor/call-next', fn () => $doctor->callNext());
$app->post('/doctor/tickets/{ticketId}/call', fn ($p) => $doctor->callTicket((int) $p['ticketId']));
$app->post('/doctor/tickets/{ticketId}/complete', fn ($p) => $doctor->completeTicket((int) $p['ticketId']));
$app->post('/doctor/tickets/{ticketId}/skip', fn ($p) => $doctor->skipTicket((int) $p['ticketId']));
$app->get('/doctor/patients/{id}', fn ($p) => $doctor->patient((int) $p['id']));
$app->post('/doctor/patients/{id}/comments', fn ($p) => $doctor->storeComment((int) $p['id']));
$app->post('/doctor/patients/{id}/consultation', fn ($p) => $doctor->storeConsultation((int) $p['id']));
$app->post('/doctor/patients/{id}/medical-certificate', fn ($p) => $doctor->issueMedicalCertificate((int) $p['id']));
$app->post('/doctor/patients/{id}/referral', fn ($p) => $doctor->issueReferral((int) $p['id']));
$app->post('/doctor/patients/{id}/recommendation', fn ($p) => $doctor->issueRecommendation((int) $p['id']));

// Medicine list (admin — name picker only, no stock)
$app->get('/medicines', fn () => $medicines->index());
$app->get('/medicines/create', fn () => $medicines->create());
$app->post('/medicines', fn () => $medicines->store());
$app->get('/medicines/{id}/edit', fn ($p) => $medicines->edit((int) $p['id']));
$app->post('/medicines/{id}', fn ($p) => $medicines->update((int) $p['id']));

// Account (logged-in user)
$app->get('/account/password', fn () => $account->passwordForm());
$app->post('/account/password', fn () => $account->passwordUpdate());
$app->get('/account/document-name', fn () => $account->documentNameForm());
$app->post('/account/document-name', fn () => $account->documentNameUpdate());

// Audit
$app->get('/audit', fn () => $audit->index());

// Staff accounts (admin)
$app->get('/users', fn () => $users->index());
$app->get('/users/create', fn () => $users->create());
$app->post('/users', fn () => $users->store());
$app->post('/users/sync-gawad', fn () => $users->syncFromGawad());
$app->post('/users/{id}/deactivate', fn ($p) => $users->deactivate((int) $p['id']));
$app->post('/users/{id}/activate', fn ($p) => $users->activate((int) $p['id']));
$app->get('/users/{id}/password', fn ($p) => $users->passwordForm((int) $p['id']));
$app->post('/users/{id}/password', fn ($p) => $users->passwordUpdate((int) $p['id']));

// Reports
$app->get('/reports', fn () => $reports->index());
$app->get('/reports/daily', fn () => $reports->daily());
$app->get('/reports/daily/export', fn () => $reports->dailyExport());
$app->get('/reports/monthly', fn () => $reports->monthly());
$app->get('/reports/monthly/export', fn () => $reports->monthlyExport());
$app->get('/reports/clinical', fn () => $reports->clinical());
$app->get('/reports/clinical/export', fn () => $reports->clinicalExport());
$app->get('/reports/appointments', fn () => $reports->appointments());
$app->get('/reports/appointments/export', fn () => $reports->appointmentsExport());

// Coordinator
$app->get('/coordinator', fn () => $coordinator->index());

// Stations + queues
$app->get('/stations', fn () => $queue->stations());
$app->get('/queue/{stationId}', fn ($p) => $queue->index((int) $p['stationId']));
$app->get('/queue/{stationId}/enqueue', fn ($p) => $queue->enqueueRedirect((int) $p['stationId']));
$app->post('/queue/{stationId}/enqueue', fn ($p) => $queue->enqueue((int) $p['stationId']));
$app->post('/queue/{stationId}/call-next', fn ($p) => $queue->callNext((int) $p['stationId']));
$app->post('/queue/{stationId}/call/{ticketId}', fn ($p) => $queue->callTicket((int) $p['stationId'], (int) $p['ticketId']));
$app->post('/queue/{stationId}/complete/{ticketId}', fn ($p) => $queue->complete((int) $p['stationId'], (int) $p['ticketId']));
$app->post('/queue/{stationId}/skip/{ticketId}', fn ($p) => $queue->skip((int) $p['stationId'], (int) $p['ticketId']));
$app->post('/queue/{stationId}/recall/{ticketId}', fn ($p) => $queue->recall((int) $p['stationId'], (int) $p['ticketId']));
$app->get('/ticket/{ticketId}', fn ($p) => $queue->ticket((int) $p['ticketId']));
$app->get('/ticket/{ticketId}/qr', fn ($p) => $queue->ticketQr((int) $p['ticketId']));
$app->get('/display/{stationId}', fn ($p) => $queue->display((int) $p['stationId']));

$app->run();
