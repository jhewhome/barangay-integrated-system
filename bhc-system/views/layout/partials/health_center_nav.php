<?php
/**
 * Health Center submenu (Gawad BIS–style grouping).
 * @var string $navMode top-dropdown|sidebar|sidebar-inline
 */
$navMode = $navMode ?? 'top-dropdown';
$isTopDropdown = $navMode === 'top-dropdown';
$isSidebarGroup = $navMode === 'sidebar';
$isSidebarInline = $navMode === 'sidebar-inline';

$queueRegistrationActive = preg_match('#^/queue/1(?:/|$)#', $currentPath) === 1;
$queueTriageActive = preg_match('#^/queue/2(?:/|$)#', $currentPath) === 1;
$queueConsultationActive = preg_match('#^/queue/3(?:/|$)#', $currentPath) === 1;
$queuePharmacyActive = preg_match('#^/queue/4(?:/|$)#', $currentPath) === 1;
$stationsActive = str_starts_with($currentPath, '/stations')
    || (str_starts_with($currentPath, '/queue') && !$queueRegistrationActive && !$queueTriageActive && !$queueConsultationActive && !$queuePharmacyActive);
$coordinatorActive = str_starts_with($currentPath, '/coordinator');
$healthItems = [
    ['label' => 'Registration Queue', 'href' => $uQueueRegistration, 'active' => $queueRegistrationActive, 'icon' => 'person-plus'],
    ['label' => 'Triage / Vitals', 'href' => $uQueueTriage, 'active' => $queueTriageActive, 'icon' => 'heart-pulse'],
    ['label' => 'Consultation', 'href' => $uQueueConsultation, 'active' => $queueConsultationActive, 'icon' => 'clipboard'],
    ['label' => 'Pharmacy Queue', 'href' => $uQueuePharmacy, 'active' => $queuePharmacyActive, 'icon' => 'capsule'],
    ['label' => 'All Stations', 'href' => $uStations, 'active' => $stationsActive, 'icon' => 'stations'],
    ['label' => 'Queue Management', 'href' => $uCoordinator, 'active' => $coordinatorActive, 'icon' => 'sliders'],
];

if (!function_exists('bhc_nav_health_icon')) {
    function bhc_nav_health_icon(string $name): string
    {
        return match ($name) {
            'person-plus' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M20 8v6M23 11h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            'heart-pulse' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20.5S4 15.5 4 9.5a4 4 0 0 1 7-2.3A4 4 0 0 1 18 9.5c0 6-6 11-6 11Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 11v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            'clipboard' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2"/><path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2H9V5Z" stroke="currentColor" stroke-width="2"/></svg>',
            'capsule' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.5 8.5 15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="3" y="8" width="8" height="8" rx="4" transform="rotate(-45 7 12)" stroke="currentColor" stroke-width="2"/></svg>',
            'people' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/></svg>',
            'calendar' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 7h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
            'stations' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 18V9a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v9" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 18v-4h8v4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
            'sliders' => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            default => '<svg class="nav-sub-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>',
        };
    }
}

if ($isTopDropdown): ?>
  <?php foreach ($healthItems as $item): ?>
    <a class="nav-sub-link<?= !empty($item['active']) ? ' active' : '' ?>" href="<?= htmlspecialchars((string) $item['href']) ?>">
      <?= bhc_nav_health_icon((string) $item['icon']) ?>
      <span><?= h((string) $item['label']) ?></span>
    </a>
  <?php endforeach; ?>
<?php elseif ($isSidebarInline): ?>
  <?php foreach ($healthItems as $item): ?>
    <a class="side-link side-link-sub<?= !empty($item['active']) ? ' active' : '' ?>" href="<?= htmlspecialchars((string) $item['href']) ?>">
      <?= bhc_nav_health_icon((string) $item['icon']) ?>
      <?= h((string) $item['label']) ?>
    </a>
  <?php endforeach; ?>
<?php else: ?>
  <details class="nav-dropdown nav-dropdown-health side-health-group"<?= !empty($healthCenterNavActive) ? ' open' : '' ?>>
    <summary class="<?= !empty($healthCenterNavActive) ? 'active' : '' ?>" aria-label="Health Center menu">
      <svg class="nav-dropdown-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 11h3l2-7h8l2 7h3v9H3v-9Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 20v-5h6v5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
      <span>Health Center</span>
      <?php if (!empty($healthCenterNavActive)): ?><span class="nav-dropdown-dot" aria-hidden="true">•</span><?php endif; ?>
      <svg class="nav-dropdown-caret" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </summary>
    <div class="side-health-sub">
      <?php $navMode = 'sidebar-inline'; require __DIR__ . '/health_center_nav.php'; ?>
    </div>
  </details>
<?php endif; ?>
