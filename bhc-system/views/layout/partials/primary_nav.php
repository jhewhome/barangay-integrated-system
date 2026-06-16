<?php
/** @var string $navVariant sidebar|top */
$navVariant = $navVariant ?? 'sidebar';
$isTop = $navVariant === 'top';
$linkClass = $isTop ? 'top-link' : 'side-link';
$wrapClass = $isTop ? 'topnav-links' : 'side-section-links';
$showSectionLabels = !$isTop;

$patientsActive = str_starts_with($currentPath, '/patients');
$appointmentsActive = str_starts_with($currentPath, '/appointments');
$healthCenterNavActive = !$isDoctor && (
    str_starts_with($currentPath, '/stations')
    || str_starts_with($currentPath, '/queue')
    || str_starts_with($currentPath, '/coordinator')
);
?>
<div class="<?= h($wrapClass) ?>">
  <?php if ($showSectionLabels): ?>
    <div class="side-label"><?= $isAdmin ? 'DASHBOARD' : ($isDoctor ? 'DOCTOR MENU' : 'STAFF MENU') ?></div>
  <?php endif; ?>
  <a class="<?= h($linkClass) ?> <?= ($isDoctor ? $doctorMenuActive : ($currentPath === $dashboardPath)) ? 'active' : '' ?>" href="<?= htmlspecialchars($dashboardUrl) ?>">
    <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 10.8 12 4l8 6.8V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
    <?= $isAdmin ? 'Admin dashboard' : ($isDoctor ? 'My patients' : 'Staff dashboard') ?>
  </a>
  <?php if (!$isDoctor): ?>
    <?php if ($showSectionLabels): ?>
      <div class="side-label" style="margin-top: 8px;">PATIENT CARE</div>
    <?php endif; ?>
    <a class="<?= h($linkClass) ?> <?= $patientsActive ? 'active' : '' ?>" href="<?= htmlspecialchars($uPatients) ?>">
      <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M17 11h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 9v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Patients
    </a>
    <a class="<?= h($linkClass) ?> <?= $appointmentsActive ? 'active' : '' ?>" href="<?= htmlspecialchars($uAppointments) ?>">
      <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 7h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
      Appointments
    </a>
    <?php if ($isTop): ?>
      <details class="nav-dropdown nav-dropdown-health">
        <summary class="<?= $healthCenterNavActive ? 'active' : '' ?>" aria-label="Health Center menu">
          <svg class="nav-dropdown-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11h3l2-7h8l2 7h3v9H3v-9Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 20v-5h6v5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
          <span>Health Center</span>
          <?php if ($healthCenterNavActive): ?><span class="nav-dropdown-dot" aria-hidden="true">•</span><?php endif; ?>
          <svg class="nav-dropdown-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </summary>
        <div class="nav-dropdown-menu nav-dropdown-menu--health">
          <div class="nav-dropdown-heading">Station queues</div>
          <?php $navMode = 'top-dropdown'; require __DIR__ . '/health_center_nav.php'; ?>
        </div>
      </details>
    <?php else: ?>
      <?php if ($showSectionLabels): ?>
        <div class="side-label" style="margin-top: 8px;">HEALTH CENTER</div>
      <?php endif; ?>
      <?php $navMode = 'sidebar'; require __DIR__ . '/health_center_nav.php'; ?>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($isAdmin && !$isTop): ?>
    <?php if ($showSectionLabels): ?>
      <div class="side-label" style="margin-top: 8px;">ADMINISTRATION</div>
    <?php endif; ?>
    <a class="<?= h($linkClass) ?> <?= str_starts_with($currentPath, '/reports') ? 'active' : '' ?>" href="<?= htmlspecialchars($uReports) ?>">
      <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5a1 1 0 0 1 1-1h9l5 5v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M14 4v5h5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M7 14h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Reports
    </a>
    <a class="<?= h($linkClass) ?> <?= str_starts_with($currentPath, '/audit') ? 'active' : '' ?>" href="<?= htmlspecialchars($uAudit) ?>">
      <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V7l8-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 9v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Activity Log
    </a>
    <a class="<?= h($linkClass) ?> <?= str_starts_with($currentPath, '/medicines') ? 'active' : '' ?>" href="<?= htmlspecialchars($uMedicines) ?>">
      <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 6h12M8 12h12M8 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
      Medicine list
    </a>
    <a class="<?= h($linkClass) ?> <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>" href="<?= htmlspecialchars($uUsers) ?>">
      <svg class="side-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M19 8v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16 11h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Staff accounts
    </a>
  <?php endif; ?>
</div>
