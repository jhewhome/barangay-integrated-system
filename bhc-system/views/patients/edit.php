<?php
$patient = $patient ?? null;
$errors = $errors ?? [];
$old = $old ?? [];
if (!$patient) {
    echo '<div class="muted">Patient not found.</div>';
    return;
}
$p = array_merge($patient, $old);
$patientIsArchived = Patient::isArchived($patient);
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Edit patient</h1>
    <div class="muted">BHC ID: <strong><?= h($patient['bhc_id']) ?></strong> (cannot be changed)<?php if ($patientIsArchived): ?> · <span class="pill skipped">Archived</span><?php endif; ?></div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(app_url('/patients')) ?>">Back</a>
  </div>
</div>

<?php if ($patientIsArchived): ?>
  <div class="notice warn" style="margin-top: 14px;" role="status">
    This patient is archived and hidden from the active registry. Visit history is retained. Restore the record to route them again.
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="card" style="margin-top: 14px; border-color: rgba(239, 62, 91, 0.35);">
    <ul style="margin: 0; padding-left: 20px;">
      <?php foreach ($errors as $e): ?>
        <li><?= h($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="<?= h(app_route('/patients/' . (int) $patient['id'] . '/update')) ?>" style="margin-top: 14px;">
  <div class="card">
    <?php require __DIR__ . '/../partials/patient_form.php'; ?>
    <div class="row form-actions" style="margin-top: 14px;">
      <button class="btn ok" type="submit">Save changes</button>
    </div>
  </div>
</form>

<?php if (!empty($isAdmin)): ?>
  <div class="card" style="margin-top: 14px; border-color: rgba(239, 62, 91, 0.2);">
    <div style="font-weight: 600; margin-bottom: 6px;">Administrator actions</div>
    <p class="muted" style="font-size: 13px; margin: 0 0 12px; line-height: 1.45;">
      Archiving hides the patient from the registry list but keeps all consultations, medicines, and documents. Use this instead of deleting records.
    </p>
    <?php
      $patientId = (int) $patient['id'];
      $returnTo = '/patients/' . $patientId . '/edit';
      require __DIR__ . '/../partials/patient_archive_actions.php';
    ?>
  </div>
<?php endif; ?>
