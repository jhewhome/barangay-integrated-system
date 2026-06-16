<?php
$user = $_SESSION['user'] ?? null;
$useSidebar = isset($user) && is_array($user);

// Base path helper for subfolder installs (XAMPP) and dev server.
// Examples:
// - dev server: SCRIPT_NAME=/index.php => basePath=""
// - XAMPP subfolder: SCRIPT_NAME=/bhc_system/public/index.php => basePath="/bhc_system/public"
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($basePath === '.' || $basePath === '/') $basePath = '';

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$currentPath = str_replace('\\', '/', $currentPath);
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
  $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}
// Support non-rewrite URLs like /public/index.php/login
$idx = strpos($currentPath, '/index.php');
if ($idx !== false) {
  $currentPath = substr($currentPath, $idx + strlen('/index.php')) ?: '/';
}
if ($currentPath !== '/' && str_ends_with($currentPath, '/')) {
  $currentPath = rtrim($currentPath, '/');
}

function url_path(string $basePath, string $path): string
{
  $path = $path === '' ? '/' : $path;
  if ($path[0] !== '/') $path = '/' . $path;
  return ($basePath === '' ? '' : $basePath) . ($path === '/' ? '/' : $path);
}

$uHome = url_path($basePath, '/');
$uStaffHome = url_path($basePath, '/staff');
$uAdminHome = url_path($basePath, '/admin');
$uDoctorHome = url_path($basePath, '/doctor');
$authUser = $user ?? null;
$isAdmin = is_array($authUser) && (($authUser['role'] ?? '') === 'admin');
$isDoctor = is_array($authUser) && (($authUser['role'] ?? '') === 'doctor');
$dashboardPath = $isAdmin ? '/admin' : ($isDoctor ? '/doctor' : ($authUser ? '/staff' : '/'));
$doctorMenuActive = $isDoctor && ($currentPath === '/doctor' || str_starts_with($currentPath, '/doctor/'));
$dashboardUrl = $isAdmin ? $uAdminHome : ($isDoctor ? $uDoctorHome : ($authUser ? $uStaffHome : $uHome));
$uLogin = url_path($basePath, '/login');
$uLogout = url_path($basePath, '/logout');
$uPatients = url_path($basePath, '/patients');
$uAppointments = url_path($basePath, '/appointments');
$uStations = url_path($basePath, '/stations');
$uCoordinator = url_path($basePath, '/coordinator');
$uQueueRegistration = url_path($basePath, '/queue/1');
$uQueueTriage = url_path($basePath, '/queue/2');
$uQueueConsultation = url_path($basePath, '/queue/3');
$uQueuePharmacy = url_path($basePath, '/queue/4');
$uReports = url_path($basePath, '/reports');
$uAudit = url_path($basePath, '/audit');
$uUsers = url_path($basePath, '/users');
$uMedicines = url_path($basePath, '/medicines');
$uLogo = url_path($basePath, '/assets/bhs_logo_v3.png');
$uTooltip = url_path($basePath, '/assets/tooltip.js');
$uAccountPassword = url_path($basePath, '/account/password');
$uAccountDocumentName = url_path($basePath, '/account/document-name');
$appYear = (int) date('Y');
$bhcDevelopedFor = 'Brgy. Balong Bato - Brgy. Health Center';
$bhcDeveloperCredit = 'MSIT/PUP Graduate School - PUP Manila';
$appName = app_name();
$appTagline = app_tagline();

$flash = null;
$flashOpenUrl = null;
if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
  $flash = $_SESSION['flash'];
  $flashOpenUrl = !empty($flash['open_url']) ? (string) $flash['open_url'] : null;
  unset($_SESSION['flash']);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= h($appName) ?></title>
    <link rel="icon" href="<?= h(app_url('/favicon.ico')) ?>" sizes="any" />
    <link rel="shortcut icon" href="<?= h(app_url('/favicon.ico')) ?>" />
    <style>
      :root {
        /* Cheerful clinic palette (light, calm, no harsh gradients) */
        --bg:#f6f9ff;
        --surface:#ffffff;
        --surface2:#f2f7ff;
        --text:#0f172a;
        --muted:#54617a;
        --pri:#2f6bff;     /* bright blue */
        --pri2:#16c2b6;    /* teal */
        --danger:#ef3e5b;
        --ok:#14b87a;
        --ring: rgba(47, 107, 255, 0.25);
        --content-max: 1400px;
      }
      * { box-sizing: border-box; }
      body {
        margin:0;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
      }
      a { color: inherit; text-decoration: none; }
      .container {
        width: 100%;
        max-width: var(--content-max);
        margin: 0 auto;
        padding: 22px;
      }
      /* Logged-in pages: single width column under .main (content + footer) */
      .main-content {
        width: 100%;
        max-width: var(--content-max);
        margin-left: auto;
        margin-right: auto;
        padding: 22px;
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        overflow-x: clip;
      }
      .page-header-card {
        margin-bottom: 14px;
      }
      .page-header-card .row-actions {
        flex-shrink: 0;
      }
      .main-toolbar {
        width: 100%;
        max-width: var(--content-max);
        margin-left: auto;
        margin-right: auto;
        padding: 12px 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
      }
      .main-content .app-footer {
        margin-top: auto;
        width: 100%;
        padding: 24px 0 0;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        background: transparent;
        backdrop-filter: none;
      }
      .main-content .app-footer-inner {
        max-width: none;
        width: 100%;
        margin: 0;
        padding: 0;
      }
      .card-narrow {
        max-width: 520px;
        margin-left: auto;
        margin-right: auto;
      }

      /* App layout — top navigation (mobile drawer for small screens) */
      .layout { display: block; min-height: 100vh; }
      .sidebar {
        width: min(300px, 92vw);
        padding: 20px 16px;
        background: rgba(255,255,255,0.98);
        border-right: 1px solid rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(10px);
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        height: 100dvh;
        max-height: 100dvh;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.22);
        display: none;
        flex-direction: column;
        min-height: 0;
        z-index: 45;
        transform: translateX(-110%);
        transition: transform 180ms cubic-bezier(.2,.9,.2,1);
      }
      .main {
        width: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }
      .topbar {
        display: flex;
        position: sticky;
        top: 0;
        z-index: 40;
        background: rgba(255,255,255,0.92);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(10px);
        overflow: visible;
      }
      .top-toolbar {
        flex-wrap: nowrap;
        gap: 10px;
        overflow: visible;
      }
      .nav-menu-btn { flex-shrink: 0; }
      .topnav {
        display: flex;
        align-items: center;
        flex: 1 1 auto;
        min-width: 0;
        overflow: visible;
      }
      .topnav-links {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 0 4px;
        overflow: visible;
      }
      .top-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        border-radius: 10px;
        color: var(--muted);
        border: 1px solid transparent;
        font-weight: 600;
        font-size: 14px;
        line-height: 1.2;
        white-space: nowrap;
        transition: background 120ms ease, border-color 120ms ease, color 120ms ease;
      }
      .top-link .side-ico {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
      }
      .top-link:hover {
        background: rgba(47, 107, 255, 0.07);
        color: var(--text);
        border-color: rgba(47, 107, 255, 0.12);
      }
      .top-link.active {
        background: rgba(47, 107, 255, 0.14);
        border-color: rgba(47, 107, 255, 0.22);
        color: var(--text);
        font-weight: 800;
      }
      .top-link.active .side-ico { color: rgba(47, 107, 255, 1); }
      .topnav-links .nav-dropdown > summary {
        border: 1px solid transparent;
        background: transparent;
        color: var(--muted);
        font-weight: 600;
        font-size: 14px;
        padding: 9px 12px;
        border-radius: 10px;
        transition: background 120ms ease, border-color 120ms ease, color 120ms ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        flex-shrink: 0;
      }
      .topnav-links .nav-dropdown > summary:hover {
        background: rgba(47, 107, 255, 0.07);
        color: var(--text);
        border-color: rgba(47, 107, 255, 0.12);
      }
      .topnav-links .nav-dropdown > summary.active,
      .topnav-links .nav-dropdown[open] > summary {
        background: rgba(47, 107, 255, 0.14);
        border-color: rgba(47, 107, 255, 0.22);
        color: var(--text);
        font-weight: 800;
      }
      .topnav-links .nav-dropdown > summary.active .nav-dropdown-ico,
      .topnav-links .nav-dropdown[open] > summary .nav-dropdown-ico {
        color: rgba(47, 107, 255, 1);
      }
      .topnav-links .nav-dropdown {
        position: relative;
        flex-shrink: 0;
      }
      .topnav-links .nav-dropdown-menu {
        left: 0;
        right: auto;
        z-index: 80;
      }
      .topnav-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        flex-shrink: 0;
      }
      .nav-dropdown { position: relative; }
      .nav-dropdown > summary {
        list-style: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1px solid rgba(15, 23, 42, 0.10);
        background: rgba(255,255,255,0.85);
        font-weight: 700;
        font-size: 13px;
        color: var(--text);
        white-space: nowrap;
      }
      .nav-dropdown > summary::-webkit-details-marker { display: none; }
      .nav-dropdown-ico {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        color: rgba(84, 97, 122, 0.95);
      }
      .nav-dropdown-caret {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
        color: rgba(84, 97, 122, 0.85);
        transition: transform 0.15s ease, color 0.15s ease;
      }
      .nav-dropdown[open] > summary .nav-dropdown-ico,
      .nav-dropdown[open] > summary .nav-dropdown-caret {
        color: var(--pri);
      }
      .nav-dropdown[open] > summary .nav-dropdown-caret {
        transform: rotate(180deg);
      }
      .nav-dropdown[open] > summary {
        background: rgba(47, 107, 255, 0.08);
        border-color: rgba(47, 107, 255, 0.2);
      }
      .nav-dropdown-dot {
        color: var(--pri);
        font-size: 11px;
        line-height: 1;
      }
      .nav-dropdown-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        min-width: 210px;
        background: var(--surface);
        border: 1px solid rgba(15, 23, 42, 0.10);
        border-radius: 12px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        padding: 6px;
        z-index: 60;
      }
      .nav-dropdown-menu a {
        display: block;
        padding: 10px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        color: var(--text);
      }
      .nav-dropdown-menu a:hover {
        background: rgba(47, 107, 255, 0.08);
      }
      .nav-dropdown-menu a.active {
        background: rgba(47, 107, 255, 0.12);
        color: var(--pri);
      }
      .nav-dropdown-menu--health {
        min-width: 250px;
        max-height: min(70vh, 520px);
        overflow-y: auto;
      }
      .nav-dropdown-heading {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--muted);
        padding: 8px 12px 4px;
      }
      .nav-dropdown-menu a.nav-sub-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
      }
      .nav-sub-ico {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        color: rgba(84, 97, 122, 0.95);
      }
      .nav-dropdown-menu a.nav-sub-link.active .nav-sub-ico,
      .nav-dropdown-menu a.nav-sub-link:hover .nav-sub-ico {
        color: var(--pri);
      }
      .side-health-group {
        display: block;
        margin-top: 4px;
      }
      .side-health-group > summary {
        width: 100%;
        justify-content: flex-start;
        border-left: 4px solid transparent;
        font-weight: 600;
      }
      .side-health-group > summary.active {
        border-left-color: rgba(47, 107, 255, 1);
      }
      .side-health-sub {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 4px 0 6px 8px;
        margin-left: 6px;
        border-left: 2px solid rgba(47, 107, 255, 0.12);
      }
      .side-link.side-link-sub {
        padding: 10px 12px 10px 10px;
        font-size: 14px;
        gap: 10px;
      }
      .nav-user-label {
        font-size: 12px;
        color: var(--muted);
        padding: 4px 12px 8px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        margin-bottom: 4px;
      }
      .side-section-links {
        display: flex;
        flex-direction: column;
        gap: 10px;
      }
      .icon-btn {
        border: 1px solid rgba(15, 23, 42, 0.10);
        background: rgba(255,255,255,0.85);
        color: var(--text);
        border-radius: 12px;
        padding: 10px 12px;
        font-weight: 900;
        cursor: pointer;
      }
      .sidebar-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.36);
        backdrop-filter: blur(2px);
        z-index: 35;
      }

      .side-brand {
        flex-shrink: 0;
        display:flex;
        align-items: center;
        gap: 10px;
        padding: 10px 10px;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.10);
      }
      .side-brand .brand-logo { height: 58px; }
      .side-section {
        margin-top: 18px;
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding-bottom: 4px;
      }
      .side-label {
        font-size: 12px;
        color: var(--muted);
        font-weight: 800;
        letter-spacing: 0.6px;
        padding: 6px 12px 4px;
        margin-bottom: 2px;
      }
      .side-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 14px;
        border-radius: 12px;
        color: var(--muted);
        border: 1px solid transparent;
        border-left: 4px solid transparent;
        font-weight: 600;
        line-height: 1.25;
        transition: background 120ms ease, border-color 120ms ease, color 120ms ease;
      }
      .side-ico {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        color: rgba(84, 97, 122, 0.95);
      }
      .side-link:hover {
        background: rgba(47, 107, 255, 0.07);
        color: var(--text);
        border-color: rgba(47, 107, 255, 0.12);
      }
      .side-link:hover .side-ico { color: rgba(47, 107, 255, 0.95); }
      .side-link.active {
        background: rgba(47, 107, 255, 0.14);
        border-color: rgba(47, 107, 255, 0.22);
        border-left-color: rgba(47, 107, 255, 1);
        color: var(--text);
        font-weight: 800;
        box-shadow: inset 0 0 0 1px rgba(47, 107, 255, 0.08);
      }
      .side-link.active .side-ico {
        color: rgba(47, 107, 255, 1);
        filter: drop-shadow(0 0 0.5px rgba(47, 107, 255, 0.4));
      }
      .side-link.active .side-ico path,
      .side-link.active .side-ico line {
        stroke-width: 2.5;
      }
      .side-bottom {
        margin-top: auto;
        flex-shrink: 0;
        width: 100%;
      }
      .side-footer {
        padding: 14px 10px 12px;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
      }
      .side-meta {
        padding: 12px 12px 16px;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
        font-size: 11px;
        line-height: 1.5;
        color: var(--muted);
        background: rgba(47, 107, 255, 0.04);
        border-radius: 0 0 8px 8px;
      }
      .side-meta-title {
        font-size: 12px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: 0.2px;
      }
      .side-meta-sub { margin-top: 4px; }
      .side-meta-copy { margin-top: 6px; font-size: 10px; opacity: 0.9; }
      .side-meta-credit { margin-top: 10px; font-size: 10px; line-height: 1.45; opacity: 0.92; }
      .side-actions { display:flex; gap: 10px; align-items:center; }
      .side-actions a.btn { color: #ffffff; }

      .app-footer {
        margin-top: 32px;
        padding: 20px 22px 28px;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(8px);
      }
      .app-footer-inner {
        width: 100%;
        max-width: var(--content-max);
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        gap: 12px 24px;
        justify-content: space-between;
        align-items: flex-start;
        font-size: 13px;
        color: var(--muted);
        line-height: 1.5;
      }
      .app-footer-brand strong {
        display: block;
        color: var(--text);
        font-weight: 800;
        font-size: 14px;
        margin-bottom: 4px;
      }
      .app-footer-links {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 16px;
        align-items: center;
      }
      .app-footer a {
        color: var(--pri);
        font-weight: 700;
        text-decoration: none;
      }
      .app-footer a:hover { text-decoration: underline; }
      .app-footer-copy { font-size: 12px; opacity: 0.95; }
      @media (max-width: 600px) {
        .app-footer-inner { flex-direction: column; }
      }

      /* Top nav: hide horizontal links on small screens; use drawer */
      @media (min-width: 901px) {
        .nav-menu-btn { display: none; }
        .sidebar { display: none !important; }
      }
      @media (max-width: 900px) {
        .topnav { display: none; }
        .nav-menu-btn { display: inline-flex; }
        .sidebar { display: flex; }
        body.sidebar-open .sidebar { transform: translateX(0); }
        body.sidebar-open .sidebar-backdrop { display: block; }
        body.sidebar-open { overflow: hidden; }
        .container { padding: 18px; }
        .main-content { padding: 18px; }
        .main-toolbar { padding-left: 18px; padding-right: 18px; }
      }

      /* Simple (logged-out) header */
      .simple-top {
        position: sticky;
        top: 0;
        z-index: 40;
        background: rgba(255,255,255,0.82);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(10px);
      }
      .simple-top-inner {
        width: 100%;
        max-width: var(--content-max);
        margin: 0 auto;
        padding: 14px 22px;
        display: flex;
        align-items: center;
        gap: 12px;
      }
      .simple-top a { color: var(--muted); padding: 8px 10px; border-radius: 10px; }
      .simple-top a.active { color: var(--text); background: rgba(47, 107, 255, 0.10); border: 1px solid rgba(47, 107, 255, 0.18); }
      .simple-top a.btn { color: #ffffff; }
      /* Logged-out pages (home, login): footer sticks to bottom when content is short */
      body.layout-simple {
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
      }
      body.layout-simple .simple-top {
        flex-shrink: 0;
      }
      body.layout-simple .layout-simple-main {
        flex: 1 0 auto;
        width: 100%;
      }
      body.layout-simple .app-footer {
        flex-shrink: 0;
        margin-top: auto;
      }
      .brand { display: inline-flex; align-items: center; gap: 10px; font-weight: 800; letter-spacing: 0.2px; color: #0f172a; white-space: nowrap; }
      .brand-text { display: none; }
      .brand-logo {
        width: auto;
        height: 76px;
        border-radius: 12px;
        object-fit: contain;
        background: rgba(255,255,255,0.92);
        border: 0;
      }
      .card {
        background: var(--surface);
        border: 1px solid rgba(15, 23, 42, 0.10);
        border-radius: 14px;
        padding: 16px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
      }
      .grid { display:grid; gap: 14px; }
      .grid > * { min-width: 0; }
      .grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .table-wrap {
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      .table-wrap table { margin-top: 0; }
      .data-table:not(.list-table) th,
      .data-table:not(.list-table) td {
        word-break: break-word;
        vertical-align: top;
      }
      .data-table .col-compact { white-space: nowrap; width: 1%; }
      .row { display:flex; gap: 10px; align-items: center; flex-wrap: wrap; }
      .row-between {
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
      }
      .row-body {
        flex: 1 1 260px;
        min-width: 0;
        max-width: 100%;
      }
      .row-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
        flex: 1 1 200px;
      }
      .row-actions-tight { justify-content: flex-start; }
      .form-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        width: 100%;
        margin-top: 4px;
      }
      .form-actions > .btn,
      .form-actions > a.btn,
      .form-actions > button.btn {
        min-width: 10.5rem;
        padding-left: 20px;
        padding-right: 20px;
      }
      .form-submit-actions {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
      }
      .row-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
      }
      .dashboard-hero .row-actions {
        flex: 1 1 100%;
      }
      .dashboard-intro {
        margin: 0 0 10px;
        line-height: 1.55;
        max-width: 52rem;
      }
      .dashboard-tips {
        margin: 0;
        padding-left: 1.25rem;
        line-height: 1.6;
        max-width: 52rem;
        font-size: 14px;
      }
      .row-stats-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
      }
      form.reports-filter { align-items: flex-end; }
      .reports-filter-period,
      .reports-filter-field { min-width: 150px; flex: 1 1 140px; }
      .reports-filter-period select,
      .reports-filter-field input { width: 100%; }
      .reports-filter-submit { align-self: flex-end; }
      .table-wrap.patients-table-wrap,
      .table-wrap.appointments-table-wrap,
      .list-table-wrap {
        overflow-x: visible;
        border-radius: 12px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: var(--surface);
      }
      .list-page-card {
        padding: 18px 20px;
      }
      .list-search-bar {
        gap: 12px;
        margin-bottom: 18px;
        align-items: flex-end;
      }
      .data-table.list-table {
        width: 100%;
        table-layout: auto;
      }
      .list-table th,
      .list-table td {
        padding: 13px 16px;
        vertical-align: middle;
        line-height: 1.45;
        word-break: break-word;
      }
      .list-table thead th {
        padding-top: 14px;
        padding-bottom: 14px;
        font-size: 12px;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        white-space: nowrap;
        background: rgba(15, 23, 42, 0.02);
        color: var(--muted);
        font-weight: 600;
      }
      .list-table tbody tr:last-child td {
        border-bottom: 0;
      }
      .list-table-empty-row td,
      .table-empty-row td {
        padding: 28px 16px;
        text-align: center;
      }
      .list-table .col-actions {
        width: 1%;
        white-space: nowrap;
      }
      .list-table .col-id {
        width: 1%;
        white-space: nowrap;
      }
      .appt-row-actions {
        display: inline-flex;
        align-items: center;
        gap: 6px;
      }
      .appt-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 7px 11px;
        border-radius: 9px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: #fff;
        font-size: 12px;
        font-weight: 700;
        color: var(--text);
        cursor: pointer;
        text-decoration: none;
        line-height: 1.2;
        white-space: nowrap;
      }
      .appt-action:hover {
        background: rgba(47, 107, 255, 0.06);
        border-color: rgba(47, 107, 255, 0.2);
        color: var(--pri);
      }
      .appt-action-route {
        color: var(--pri);
        border-color: rgba(47, 107, 255, 0.22);
        background: rgba(47, 107, 255, 0.06);
      }
      .appt-action-queue {
        color: #9a3412;
        border-color: rgba(234, 88, 12, 0.28);
        background: rgba(251, 146, 60, 0.12);
      }
      .appt-action-queue:hover {
        color: #9a3412;
        border-color: rgba(234, 88, 12, 0.4);
        background: rgba(251, 146, 60, 0.18);
      }
      .appt-menu {
        position: relative;
      }
      .appt-menu > summary {
        list-style: none;
      }
      .appt-menu > summary::-webkit-details-marker {
        display: none;
      }
      .appt-action-menu::after {
        content: " ▾";
        font-size: 10px;
        opacity: 0.7;
      }
      .appt-menu[open] > summary {
        background: rgba(47, 107, 255, 0.1);
        border-color: rgba(47, 107, 255, 0.25);
      }
      .appt-menu-panel {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        min-width: 168px;
        background: var(--surface);
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 10px;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        padding: 6px;
        z-index: 20;
      }
      .appt-menu-form,
      .appt-menu form {
        margin: 0;
      }
      .appt-menu-item {
        display: block;
        width: 100%;
        text-align: left;
        padding: 9px 11px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        font-size: 13px;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
        text-decoration: none;
      }
      .appt-menu-item:hover {
        background: rgba(47, 107, 255, 0.08);
        color: var(--pri);
      }
      .appt-menu-item-danger,
      .appt-menu-cancel {
        color: var(--danger);
      }
      .appt-menu-item-danger:hover,
      .appt-menu-cancel:hover {
        background: rgba(239, 62, 91, 0.08);
        color: var(--danger);
      }
      .appt-menu-done {
        color: #065f46;
      }
      .appt-menu-done:hover {
        background: rgba(20, 184, 122, 0.1);
      }
      @media (max-width: 900px) {
        .table-wrap.patients-table-wrap,
        .table-wrap.appointments-table-wrap,
        .list-table-wrap {
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }
      }
      @media (max-width: 900px) {
        .grid.cols-2, .grid.cols-3 { grid-template-columns: 1fr; }
        .dashboard-hero.row-between,
        .page-header.row-between,
        .row.page-header.row-between {
          flex-direction: column;
          align-items: stretch;
          justify-content: flex-start;
          gap: 10px;
        }
        .dashboard-hero .row-body,
        .page-header .row-body {
          flex: 0 0 auto;
          width: 100%;
          max-width: 100%;
        }
        .page-header.row-between .row-actions,
        .dashboard-hero .row-actions,
        .page-header .row-actions {
          flex: 0 0 auto;
          width: 100%;
          max-width: 100%;
          flex-direction: column;
          align-items: stretch;
          justify-content: flex-start;
        }
        .page-header .row-actions > a.btn,
        .page-header .row-actions > button.btn,
        .dashboard-hero .row-actions > a.btn,
        .dashboard-hero .row-actions > button.btn {
          display: flex;
          width: 100%;
          max-width: 100%;
          min-height: 44px;
          box-sizing: border-box;
        }
        .card-header.row-between {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;
        }
        .display-links.row-actions-tight {
          flex-direction: column;
          align-items: stretch;
          width: 100%;
        }
        .display-links.row-actions-tight > .btn {
          width: 100%;
          max-width: 100%;
        }
        .form-actions {
          flex-direction: column;
          align-items: stretch;
          gap: 8px;
        }
        .form-actions > .btn,
        .form-actions > a.btn,
        .form-actions > button.btn {
          width: 100%;
          max-width: 100%;
          min-width: 0;
          min-height: 44px;
          display: flex;
          box-sizing: border-box;
        }
      }
      @media (max-width: 640px) {
        .btn, a.btn, button.btn {
          min-height: 44px;
          padding: 12px 14px;
          font-size: 14px;
        }
        .row-between {
          flex-direction: column;
          align-items: stretch;
          justify-content: flex-start;
          gap: 10px;
        }
        .row-body {
          flex: 0 0 auto;
          width: 100%;
          max-width: 100%;
        }
        .page-header.row-between,
        .row.page-header.row-between {
          gap: 8px;
        }
        .page-header .row-body h1 {
          margin-bottom: 4px;
        }
        .row-actions,
        .row-actions-tight {
          flex: 0 0 auto;
          width: 100%;
          flex-direction: column;
          align-items: stretch;
          justify-content: flex-start;
          gap: 8px;
        }
        .row-actions > a.btn,
        .row-actions > button.btn,
        .row-actions > .btn,
        .row-actions > form,
        .row.btn-stack-mobile > a.btn,
        .row.btn-stack-mobile > button.btn,
        .row.btn-stack-mobile > form {
          width: 100%;
          max-width: 100%;
        }
        .row-actions > form,
        .row.btn-stack-mobile > form {
          display: block;
          margin: 0;
        }
        .row-actions > form .btn,
        .row.btn-stack-mobile > form .btn {
          width: 100%;
        }
        .page-header.row,
        .row.page-header {
          flex-direction: column;
          align-items: stretch;
          justify-content: flex-start;
        }
        .row-queue-item {
          flex-direction: column;
          align-items: stretch;
          gap: 10px;
        }
        .row-queue-item > form {
          width: 100%;
        }
        .row-queue-item > form .btn {
          width: 100%;
          min-height: 44px;
        }
        .simple-top-inner {
          flex-wrap: wrap;
        }
        form.reports-filter {
          align-items: stretch;
        }
        .reports-filter-period,
        .reports-filter-field,
        .reports-filter-submit {
          width: 100%;
          min-width: 0;
        }
        .reports-filter-submit .btn {
          width: 100%;
        }
        .row-pills .pill {
          flex: 1 1 auto;
          max-width: 100%;
        }
        .row-pills .pill:last-child {
          flex: 1 1 100%;
        }
        .dashboard-intro,
        .dashboard-tips {
          max-width: none;
        }
      }
      .span-2 { grid-column: 1 / -1; }
      
      /* Ensure consistent ordering for queue station cards */
      .queue-grid .queue-col-left { order: 1; }
      .queue-grid .queue-col-right { order: 2; }
      h1,h2 { margin: 0 0 12px; }
      .muted { color: var(--muted); }
      table { width: 100%; border-collapse: collapse; }
      .data-table { table-layout: fixed; }
      th, td { padding: 10px 10px; border-bottom: 1px solid rgba(15, 23, 42, 0.08); text-align: left; }
      th { color: var(--muted); font-weight: 600; font-size: 13px; }
      .btn {
        border:0;
        padding: 10px 12px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 700;
        color: #ffffff;
        background: linear-gradient(135deg, var(--pri), var(--pri2));
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 10px 20px rgba(47, 107, 255, 0.16);
      }
      .btn:hover { filter: brightness(1.03); }
      .btn:focus { outline: 2px solid var(--ring); outline-offset: 2px; }
      .btn.danger { background: var(--danger); border-color: rgba(15, 23, 42, 0.08); box-shadow:none; }
      .btn.ok { background: var(--ok); border-color: rgba(15, 23, 42, 0.08); box-shadow:none; }
      a.btn, button.btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-sizing: border-box;
        max-width: 100%;
        white-space: normal;
        line-height: 1.25;
      }
      input, select, textarea {
        width: 100%;
        display: block;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: var(--surface2);
        color: var(--text);
        font-family: inherit;
        font-size: 14px;
        line-height: 1.45;
        box-sizing: border-box;
        max-width: 100%;
      }
      input[type="radio"],
      input[type="checkbox"] {
        width: auto;
        display: inline-block;
        padding: 0;
        margin: 0;
        border: none;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
        flex-shrink: 0;
        accent-color: var(--pri);
        cursor: pointer;
      }
      input[type="radio"]:focus,
      input[type="checkbox"]:focus {
        outline: 2px solid var(--ring);
        outline-offset: 2px;
        background: transparent;
      }
      textarea {
        min-height: 88px;
        resize: vertical;
      }
      textarea::placeholder,
      input::placeholder {
        color: #94a3b8;
        opacity: 1;
      }
      input:focus, select:focus, textarea:focus {
        outline: 2px solid var(--ring);
        outline-offset: 2px;
        border-color: rgba(47, 107, 255, 0.35);
        background: var(--surface);
      }
      label { font-size: 13px; color: var(--muted); display:block; margin-bottom: 6px; }
      .required-mark {
        color: var(--danger);
        font-weight: 600;
      }
      label:has(+ input[required]:not([type="radio"]):not([type="checkbox"])),
      label:has(+ select[required]),
      label:has(+ textarea[required]) {
        color: #b4233a;
        font-weight: 600;
      }
      label:has(+ input[required]:not([type="radio"]):not([type="checkbox"]))::after,
      label:has(+ select[required])::after,
      label:has(+ textarea[required])::after {
        content: ' *';
        color: var(--danger);
        font-weight: 700;
      }
      input[required]:not([type="radio"]):not([type="checkbox"]),
      select[required],
      textarea[required] {
        border-color: rgba(239, 62, 91, 0.42);
        background: rgba(239, 62, 91, 0.04);
        box-shadow: inset 3px 0 0 var(--danger);
      }
      input[required]:not([type="radio"]):not([type="checkbox"]):focus,
      select[required]:focus,
      textarea[required]:focus {
        border-color: rgba(239, 62, 91, 0.55);
        background: var(--surface);
        box-shadow: inset 3px 0 0 var(--danger), 0 0 0 2px rgba(239, 62, 91, 0.14);
        outline: none;
      }
      .patient-residency-block:has(.residency-choice-input[required]) .patient-residency-title::after {
        content: ' (required)';
        color: var(--danger);
        font-weight: 600;
        font-size: 13px;
      }
      .patient-residency-block:has(.residency-choice-input[required]) .residency-choice-group {
        box-shadow: inset 0 0 0 1px rgba(239, 62, 91, 0.22);
        border-radius: 12px;
        padding: 2px;
      }
      label + textarea,
      label + input,
      label + select {
        margin-top: 0;
      }
      textarea + label,
      input + label,
      select + label {
        margin-top: 10px;
      }
      .patient-routing-form label {
        font-size: 16px;
        font-weight: bold;
        color: var(--text);
      }
      .error { padding: 10px 12px; border-radius: 12px; background: rgba(239,62,91,0.10); border: 1px solid rgba(239,62,91,0.25); color: #7a0f20; }
      .notice { padding: 10px 12px; border-radius: 12px; background: rgba(47,107,255,0.08); border: 1px solid rgba(47,107,255,0.22); color: #1e3a8a; margin-bottom: 14px; }
      .notice.ok-banner { background: rgba(20,184,122,0.10); border-color: rgba(20,184,122,0.25); color: #065f46; }
      .error.flash-banner { margin-bottom: 14px; }
      .pill { display:inline-flex; align-items:center; gap:8px; padding: 6px 10px; border-radius: 999px; font-size: 12px; border: 1px solid rgba(15, 23, 42, 0.10); color: var(--muted); background: rgba(255,255,255,0.65); }
      .pill.waiting { border-color: rgba(47,107,255,0.25); color: #1f3a8a; }
      .pill.serving { border-color: rgba(20,184,122,0.25); color: #065f46; }
      .pill.done { border-color: rgba(15,23,42,0.14); color: #334155; }
      .pill.skipped { border-color: rgba(239,62,91,0.25); color: #7a0f20; }
      .big { font-size: 36px; font-weight: 800; letter-spacing: 0.8px; }

      /* Autocomplete results */
      .auto-wrap { position: relative; }
      .auto-backdrop {
        position: fixed;
        inset: 0;
        background: transparent;
        z-index: 25;
        display: none;
      }
      .auto-results {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        background: var(--surface);
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 12px;
        box-shadow: 0 18px 30px rgba(15, 23, 42, 0.10);
        overflow: hidden;
        z-index: 30;
      }
      .auto-item {
        width: 100%;
        text-align: left;
        padding: 10px 12px;
        background: transparent;
        border: 0;
        cursor: pointer;
        color: var(--text);
        font-weight: 700;
      }
      .auto-item:hover { background: rgba(47, 107, 255, 0.07); }
      .auto-sub { display:block; font-weight: 600; color: var(--muted); font-size: 12px; margin-top: 2px; }
      .auto-results { max-height: 320px; overflow: auto; }
      .patient-duplicate-results {
        max-height: min(320px, 40vh);
      }

      /* Tooltips: fixed layer via tooltip.js (avoids overflow clipping) */
      .has-tip { position: relative; }
      .has-tip[data-tip] { cursor: help; }
      #tooltip-layer {
        position: fixed;
        inset: 0;
        z-index: 100000;
        pointer-events: none;
        overflow: visible;
      }
      #tooltip-layer .tip-float {
        position: fixed;
        margin: 0;
      }
      .tip-float {
        position: fixed;
        z-index: 100001;
        display: block;
        max-width: min(320px, calc(100vw - 16px));
        padding: 8px 12px;
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.94);
        color: #ffffff;
        font-size: 12px;
        line-height: 1.4;
        font-weight: 600;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
        pointer-events: none;
        white-space: normal;
        word-wrap: break-word;
        text-align: left;
      }
      /* CSS-only fallback when JS is disabled */
      html:not(.tooltips-js) .has-tip[data-tip]::after {
        content: attr(data-tip);
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        top: calc(100% + 10px);
        bottom: auto;
        width: max-content;
        max-width: min(320px, calc(100vw - 24px));
        padding: 8px 10px;
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.92);
        color: #ffffff;
        font-size: 12px;
        line-height: 1.35;
        white-space: normal;
        text-align: left;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        opacity: 0;
        pointer-events: none;
        transition: opacity 120ms ease, transform 120ms ease;
        z-index: 50;
      }
      html:not(.tooltips-js) .row-actions .has-tip:first-child[data-tip]::after {
        left: 0;
        transform: translateX(0);
      }
      html:not(.tooltips-js) .row-actions .has-tip:last-child[data-tip]::after {
        left: auto;
        right: 0;
        transform: translateX(0);
      }
      html:not(.tooltips-js) .has-tip:hover::after,
      html:not(.tooltips-js) .has-tip:focus::after {
        opacity: 1;
        transform: translateX(-50%) translateY(2px);
      }
      html:not(.tooltips-js) .row-actions .has-tip:first-child:hover::after,
      html:not(.tooltips-js) .row-actions .has-tip:first-child:focus::after {
        transform: translateY(2px);
      }
      html:not(.tooltips-js) .row-actions .has-tip:last-child:hover::after,
      html:not(.tooltips-js) .row-actions .has-tip:last-child:focus::after {
        transform: translateY(2px);
      }
      @media (max-width: 600px) {
        html:not(.tooltips-js) .has-tip[data-tip]::after {
          max-width: min(280px, calc(100vw - 20px));
        }
      }

      .go-to-top {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 40;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px 10px 12px;
        border-radius: 999px;
        border: 1px solid rgba(47, 107, 255, 0.28);
        background: rgba(255, 255, 255, 0.96);
        color: var(--pri, #2f6bff);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 12px 24px rgba(47, 107, 255, 0.18);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(10px);
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease, background 0.18s ease, border-color 0.18s ease;
      }
      .go-to-top.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
      }
      .go-to-top:hover {
        cursor: pointer;
        background: #fff;
        border-color: rgba(47, 107, 255, 0.4);
        box-shadow: 0 14px 28px rgba(47, 107, 255, 0.22);
      }
      .go-to-top:active {
        cursor: pointer;
        transform: translateY(1px) scale(0.98);
      }
      .go-to-top:focus {
        outline: none;
      }
      .go-to-top:focus-visible {
        outline: 2px solid rgba(47, 107, 255, 0.45);
        outline-offset: 2px;
      }
      .go-to-top-icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
      }
      @media (max-width: 600px) {
        .go-to-top {
          right: 14px;
          bottom: 14px;
          padding: 10px 12px;
        }
        .go-to-top-label {
          position: absolute;
          width: 1px;
          height: 1px;
          padding: 0;
          margin: -1px;
          overflow: hidden;
          clip: rect(0, 0, 0, 0);
          white-space: nowrap;
          border: 0;
        }
      }
      @media (prefers-reduced-motion: reduce) {
        .go-to-top {
          transition: none;
        }
      }
    </style>
  </head>
  <body<?= $useSidebar ? '' : ' class="layout-simple"' ?>>
    <?php if ($useSidebar): ?>
      <div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

      <div class="layout">
        <aside class="sidebar" aria-label="Mobile navigation">
          <a class="side-brand" href="<?= htmlspecialchars($dashboardUrl) ?>" aria-label="<?= h($appName) ?> home">
            <img class="brand-logo" src="<?= htmlspecialchars($uLogo) ?>" alt="<?= h($appName) ?> logo" />
            <span class="brand-text"><?= h($appName) ?></span>
          </a>

          <div class="side-section">
            <?php $navVariant = 'sidebar'; require __DIR__ . '/partials/primary_nav.php'; ?>
          </div>

          <div class="side-bottom">
            <div class="side-footer">
              <div class="pill" style="width: 100%; justify-content: space-between;">
                <span><?= $isAdmin ? 'Administrator' : ($isDoctor ? 'Doctor' : 'Staff') ?></span>
                <strong><?= htmlspecialchars((string) ($user['username'] ?? '')) ?></strong>
              </div>
              <div class="side-actions" style="margin-top: 10px; flex-direction: column;">
                <a class="btn" href="<?= htmlspecialchars($uAccountPassword) ?>" style="box-shadow:none; width: 100%; text-align:center;">Change password</a>
                <?php if ($isDoctor): ?>
                  <a class="btn" href="<?= htmlspecialchars($uAccountDocumentName) ?>" style="box-shadow:none; width: 100%; text-align:center;">Name on documents</a>
                <?php endif; ?>
                <a class="btn" href="<?= htmlspecialchars($uLogout) ?>" style="box-shadow:none; width: 100%; text-align:center;">Logout</a>
              </div>
            </div>
            <div class="side-meta" aria-label="System information">
              <div class="side-meta-title"><?= h($appName) ?></div>
              <div class="side-meta-sub"><?= h($appTagline) ?></div>
              <div class="side-meta-credit">Developed for <?= htmlspecialchars($bhcDevelopedFor) ?> &mdash; <?= htmlspecialchars($bhcDeveloperCredit) ?></div>
              <div class="side-meta-copy">&copy; <?= $appYear ?> <?= h($appName) ?></div>
            </div>
          </div>
        </aside>

        <div class="main">
          <header class="topbar">
            <div class="main-toolbar top-toolbar">
              <button class="icon-btn nav-menu-btn" type="button" onclick="document.body.classList.toggle('sidebar-open')" aria-label="Open menu">☰</button>
              <a class="brand" href="<?= htmlspecialchars($dashboardUrl) ?>" aria-label="<?= h($appName) ?> home" style="flex-shrink:0;">
                <img class="brand-logo" style="height: 40px;" src="<?= htmlspecialchars($uLogo) ?>" alt="<?= h($appName) ?> logo" />
              </a>
              <nav class="topnav" aria-label="Main navigation">
                <?php $navVariant = 'top'; require __DIR__ . '/partials/primary_nav.php'; ?>
              </nav>
              <div class="topnav-actions">
                <?php if ($isAdmin): ?>
                  <?php
                    $adminNavActive = str_starts_with($currentPath, '/reports')
                        || str_starts_with($currentPath, '/audit')
                        || str_starts_with($currentPath, '/medicines')
                        || str_starts_with($currentPath, '/users');
                  ?>
                  <details class="nav-dropdown nav-dropdown-admin">
                    <summary aria-label="Administration menu">
                      <svg class="nav-dropdown-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5a1 1 0 0 1 1-1h9l5 5v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 4v5h5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M7 14h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                      <span>Administration</span>
                      <?php if ($adminNavActive): ?><span class="nav-dropdown-dot" aria-hidden="true">•</span><?php endif; ?>
                      <svg class="nav-dropdown-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>
                    <div class="nav-dropdown-menu">
                      <a class="<?= str_starts_with($currentPath, '/reports') ? 'active' : '' ?>" href="<?= htmlspecialchars($uReports) ?>">Reports</a>
                      <a class="<?= str_starts_with($currentPath, '/audit') ? 'active' : '' ?>" href="<?= htmlspecialchars($uAudit) ?>">Activity log</a>
                      <a class="<?= str_starts_with($currentPath, '/medicines') ? 'active' : '' ?>" href="<?= htmlspecialchars($uMedicines) ?>">Medicine list</a>
                      <a class="<?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>" href="<?= htmlspecialchars($uUsers) ?>">Staff accounts</a>
                    </div>
                  </details>
                <?php endif; ?>
                <details class="nav-dropdown nav-dropdown-user">
                  <summary aria-label="Account menu">
                    <svg class="nav-dropdown-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    <span><?= htmlspecialchars((string) ($user['username'] ?? 'Account')) ?></span>
                    <svg class="nav-dropdown-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </summary>
                  <div class="nav-dropdown-menu">
                    <div class="nav-user-label"><?= $isAdmin ? 'Administrator' : ($isDoctor ? 'Doctor' : 'Staff') ?></div>
                    <a href="<?= htmlspecialchars($uAccountPassword) ?>">Change password</a>
                    <?php if ($isDoctor): ?>
                      <a class="<?= str_starts_with($currentPath, '/account/document-name') ? 'active' : '' ?>" href="<?= htmlspecialchars($uAccountDocumentName) ?>">Name on documents</a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($uLogout) ?>">Logout</a>
                  </div>
                </details>
              </div>
            </div>
          </header>

          <div class="main-content">
            <?php if ($flash): ?>
              <?php
                $flashType = (string) ($flash['type'] ?? 'error');
                $flashClass = match ($flashType) {
                  'ok' => 'notice ok-banner',
                  'info' => 'notice',
                  default => 'error flash-banner',
                };
              ?>
              <div class="<?= htmlspecialchars($flashClass) ?>" role="status">
                <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
              </div>
            <?php endif; ?>
    <?php else: ?>
      <div class="simple-top">
        <div class="simple-top-inner">
          <a class="brand" href="<?= htmlspecialchars($uHome) ?>" aria-label="<?= h($appName) ?> home" style="margin-right:auto;">
            <img class="brand-logo" style="height: 44px;" src="<?= htmlspecialchars($uLogo) ?>" alt="<?= h($appName) ?> logo" />
          </a>
          <a href="<?= htmlspecialchars($uHome) ?>" class="<?= $currentPath === '/' ? 'active' : '' ?>">Home</a>
          <a href="<?= htmlspecialchars($uLogin) ?>" class="<?= str_starts_with($currentPath, '/login') ? 'active' : '' ?>">Login</a>
        </div>
      </div>
      <main class="layout-simple-main">
      <div class="container">
        <?php if ($flash): ?>
          <?php
            $flashType = (string) ($flash['type'] ?? 'error');
            $flashClass = match ($flashType) {
              'ok' => 'notice ok-banner',
              'info' => 'notice',
              default => 'error flash-banner',
            };
          ?>
          <div class="<?= htmlspecialchars($flashClass) ?>" role="status">
            <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
          </div>
        <?php endif; ?>
    <?php endif; ?>