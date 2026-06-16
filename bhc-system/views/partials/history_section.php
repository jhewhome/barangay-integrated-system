<?php
/**
 * Collapsible patient history section.
 *
 * @var string $sectionId
 * @var string $sectionTitle
 * @var bool $sectionOpen
 * @var string|null $sectionHint
 * @var string|null $sectionBadge
 * @var string|null $sectionBadgeType count|scheduled|today|status
 * @var string|null $sectionBadgeLabel
 */
$sectionId = (string) ($sectionId ?? '');
$sectionTitle = (string) ($sectionTitle ?? '');
$sectionOpen = (bool) ($sectionOpen ?? false);
$sectionHint = $sectionHint ?? null;
$sectionBadge = $sectionBadge ?? null;
$sectionBadgeType = $sectionBadgeType ?? null;
$sectionBadgeLabel = $sectionBadgeLabel ?? null;

if ($sectionBadge !== null && $sectionBadge !== '') {
    if ($sectionBadgeType === null) {
        if (is_numeric($sectionBadge)) {
            $sectionBadgeType = 'count';
        } elseif ($sectionBadge === 'Today') {
            $sectionBadgeType = 'today';
        } elseif ($sectionBadge === 'Scheduled') {
            $sectionBadgeType = 'scheduled';
        } else {
            $sectionBadgeType = 'status';
        }
    }

    if ($sectionBadgeType === 'count' && $sectionBadgeLabel === null) {
        $countValue = (int) $sectionBadge;
        $sectionBadgeLabel = $countValue === 1 ? 'record' : 'records';
    }
}
?>
<details class="history-section card" id="<?= h($sectionId) ?>"<?= $sectionOpen ? ' open' : '' ?>>
  <summary class="history-section-summary" title="Click to expand or collapse this section">
    <span class="history-section-leading">
      <span class="history-section-title"><?= h($sectionTitle) ?></span>
      <?php if ($sectionBadge !== null && $sectionBadge !== ''): ?>
        <?php
          $badgeAria = match ($sectionBadgeType) {
              'count' => $sectionBadge . ' ' . ($sectionBadgeLabel ?? 'records'),
              'today' => 'Clinical record exists for today',
              'scheduled' => 'Appointment already scheduled',
              default => (string) $sectionBadge,
          };
        ?>
        <span class="history-section-badge history-section-badge-<?= h((string) $sectionBadgeType) ?>" aria-label="<?= h($badgeAria) ?>">
          <?php if ($sectionBadgeType === 'count'): ?>
            <span class="history-section-badge-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
            </span>
            <span class="history-section-badge-value"><?= h((string) $sectionBadge) ?></span>
            <?php if ($sectionBadgeLabel): ?>
              <span class="history-section-badge-label"><?= h($sectionBadgeLabel) ?></span>
            <?php endif; ?>
          <?php elseif ($sectionBadgeType === 'scheduled'): ?>
            <span class="history-section-badge-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none"><path d="M7 3v3M17 3v3M4 8h16M6 12h4M6 16h3M14 12h4M14 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><rect x="4" y="6" width="16" height="14" rx="2" stroke="currentColor" stroke-width="2"/></svg>
            </span>
            <span class="history-section-badge-text">Scheduled</span>
          <?php elseif ($sectionBadgeType === 'today'): ?>
            <span class="history-section-badge-dot" aria-hidden="true"></span>
            <span class="history-section-badge-text">Today</span>
          <?php else: ?>
            <span class="history-section-badge-text"><?= h((string) $sectionBadge) ?></span>
          <?php endif; ?>
        </span>
      <?php endif; ?>
      <?php if ($sectionHint): ?>
        <span class="history-section-hint muted"><?= h($sectionHint) ?></span>
      <?php endif; ?>
    </span>
    <span class="history-section-toggle" aria-hidden="true">
      <span class="history-section-toggle-icon"></span>
      <span class="history-section-toggle-label"></span>
    </span>
  </summary>
  <div class="history-section-body">
