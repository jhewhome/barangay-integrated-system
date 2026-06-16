<?php
function report_nav_active(string $path): bool
{
    global $currentPath;
    $currentPath = $currentPath ?? '';
    if ($path === '/reports') {
        return $currentPath === '/reports';
    }
    return str_starts_with($currentPath, $path);
}
function report_nav_class(string $path): string
{
    return report_nav_active($path) ? 'btn report-tab-active' : 'btn';
}
?>
<div class="row-actions row-actions-tight no-print report-nav" style="margin-bottom: 14px; flex-wrap: wrap;">
  <a class="<?= report_nav_class('/reports/daily') ?>" href="<?= h(app_url('/reports/daily')) ?>" style="box-shadow:none;">Daily ops</a>
  <a class="<?= report_nav_class('/reports/monthly') ?>" href="<?= h(app_url('/reports/monthly')) ?>" style="box-shadow:none;">Queue</a>
  <a class="<?= report_nav_class('/reports/clinical') ?>" href="<?= h(app_url('/reports/clinical')) ?>" style="box-shadow:none;">Clinical</a>
  <a class="<?= report_nav_class('/reports/appointments') ?>" href="<?= h(app_url('/reports/appointments')) ?>" style="box-shadow:none;">Appointments</a>
  <a class="<?= report_nav_class('/reports') ?>" href="<?= h(app_url('/reports')) ?>" style="box-shadow:none;">All reports</a>
</div>
<style>
  .btn.report-tab-active {
    background: var(--surface2);
    color: var(--text);
    box-shadow: none;
    border: 1px solid rgba(47, 107, 255, 0.35);
  }
</style>
