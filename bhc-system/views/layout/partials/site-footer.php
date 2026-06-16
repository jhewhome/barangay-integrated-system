<?php
/** @var string $uHome */
/** @var int $appYear */
/** @var string $currentPath */
$uHome = $uHome ?? '/';
$appYear = $appYear ?? (int) date('Y');
$path = (string) ($currentPath ?? '');
// Keep patient TV / ticket pages uncluttered
if (str_starts_with($path, '/display') || str_starts_with($path, '/ticket')) {
    return;
}
$bp = (string) ($basePath ?? '');
$appName = app_name();
$appTagline = app_tagline();
?>
<footer class="app-footer" role="contentinfo">
  <div class="app-footer-inner">
    <div class="app-footer-brand">
      <strong><?= h($appName) ?></strong>
      <span><?= h($appTagline) ?></span>
    </div>
    <div class="app-footer-links">
      <a href="<?= htmlspecialchars($uHome) ?>">Home</a>
      <a href="<?= htmlspecialchars(url_path($bp, '/login')) ?>">Login</a>
      <a href="<?= htmlspecialchars(url_path($bp, '/display/2')) ?>" target="_blank" rel="noopener">Triage display</a>
      <a href="<?= htmlspecialchars(url_path($bp, '/display/4')) ?>" target="_blank" rel="noopener">Pharmacy display</a>
    </div>
    <div class="app-footer-copy">
      Developed for <?= htmlspecialchars($bhcDevelopedFor ?? 'Brgy. Balong Bato - Brgy. Health Center') ?> &mdash; <?= htmlspecialchars($bhcDeveloperCredit ?? 'MSIT/PUP Graduate School - PUP Manila') ?><br>
      &copy; <?= (int) $appYear ?> <?= h($appName) ?>.
    </div>
  </div>
</footer>
