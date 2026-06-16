<?php
/** @var array<string,mixed>|null $todayConsultation */
$todayConsultation = $todayConsultation ?? null;
if (!$todayConsultation) {
    return;
}

$diagnosis = trim((string) ($todayConsultation['diagnosis'] ?? ''));
$diagnosisPreview = $diagnosis;
if (mb_strlen($diagnosisPreview) > 72) {
    $diagnosisPreview = mb_substr($diagnosisPreview, 0, 71) . '…';
}
?>
<div class="notice consultation-today-notice" style="margin-bottom: 12px;" role="status">
  <strong>Consultation already recorded today.</strong>
  <?php if ($diagnosisPreview !== ''): ?>
    Diagnosis: <em><?= h($diagnosisPreview) ?></em>.
  <?php endif; ?>
  Saving will <strong>update</strong> this record, not create a duplicate.
</div>
