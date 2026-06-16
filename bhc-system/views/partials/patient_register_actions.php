<?php
/**
 * Patient registration actions — admin: direct add; staff: Gawad BIS + emergency walk-in only.
 *
 * Expected vars (optional): $isAdmin, $btnClass, $btnStyle, $wrapClass
 */
$isAdmin = $isAdmin ?? ((Auth::user()['role'] ?? '') === 'admin');
$btnClass = $btnClass ?? 'btn ok';
$btnStyle = $btnStyle ?? 'box-shadow:none;';
$wrapClass = $wrapClass ?? 'row-actions';
$addUrl = app_url('/patients/create');
$emergencyUrl = app_url('/patients/create?emergency=1');
$gawadResidentsUrl = GawadIntegration::residentsIndexUrl();
?>
<div class="<?= h($wrapClass) ?>">
  <?php if ($isAdmin): ?>
    <a class="<?= h($btnClass) ?>" href="<?= h($addUrl) ?>" style="<?= h($btnStyle) ?>">Add patient</a>
  <?php else: ?>
    <?php if ($gawadResidentsUrl): ?>
      <a class="btn" href="<?= h($gawadResidentsUrl) ?>" target="_blank" rel="noopener noreferrer" style="<?= h($btnStyle) ?>">Register via Gawad BIS</a>
    <?php endif; ?>
    <a class="btn" href="<?= h($emergencyUrl) ?>" style="<?= h($btnStyle) ?>" title="For urgent cases when the patient cannot be registered in Gawad BIS first">Emergency walk-in</a>
  <?php endif; ?>
</div>
