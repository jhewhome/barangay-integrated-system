<?php
/** @var int $patientId */
/** @var array<string,mixed> $patient */
/** @var string|null $returnTo */

$patientId = (int) ($patientId ?? 0);
$patient = $patient ?? [];
$returnTo = $returnTo ?? ($_SERVER['REQUEST_URI'] ?? '/patients');
$isArchived = Patient::isArchived($patient);
?>

<?php if ($isArchived): ?>
  <form method="POST" action="<?= h(app_route('/patients/' . (int) $patientId . '/restore')) ?>" class="patient-restore-form" onsubmit="return confirm('Restore this patient to the active registry?');">
    <input type="hidden" name="return_to" value="<?= h($returnTo) ?>" />
    <button class="btn ok" type="submit" style="box-shadow:none;">Restore patient</button>
  </form>
<?php else: ?>
  <form method="POST" action="<?= h(app_route('/patients/' . (int) $patientId . '/archive')) ?>" class="patient-archive-form" onsubmit="return confirm('Archive this patient? They will be hidden from the registry but all visit history is kept.');">
    <button class="btn danger" type="submit" style="box-shadow:none;">Archive patient</button>
  </form>
<?php endif; ?>
