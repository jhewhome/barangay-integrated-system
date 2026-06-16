<?php
$doctor = $doctor ?? [];
$errors = $errors ?? [];
$doctorLabel = User::doctorLabel($doctor);
$documentNamePreview = User::documentName($doctor);
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Name on documents</h1>
    <div class="muted">Set how your name appears on prescriptions, medical certificates, referral letters, and clinical recommendations.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(app_url('/doctor')) ?>">Back to My patients</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="card" style="margin-top: 14px; border-color: rgba(239, 62, 91, 0.35);">
    <ul style="margin: 0; padding-left: 20px;">
      <?php foreach ($errors as $e): ?>
        <li><?= h($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card card-narrow" id="document-name" style="margin-top: 14px;">
  <div class="muted" style="margin-bottom: 14px;">
    Signed in as <strong><?= h($doctorLabel) ?></strong>
    (<span class="muted"><?= h((string) ($doctor['username'] ?? '')) ?></span>).
  </div>
  <form method="POST" action="<?= h(app_route('/account/document-name')) ?>">
    <div style="margin-bottom: 14px;">
      <label for="doctorDisplayName">Display name</label>
      <input
        id="doctorDisplayName"
        name="display_name"
        maxlength="100"
        value="<?= h(trim((string) ($doctor['display_name'] ?? ''))) ?>"
        placeholder="e.g. Dr. Maria S. Cruz or Maria Cruz, MD"
      />
      <div class="muted" style="margin-top: 6px; font-size: 12px; line-height: 1.5;">
        Leave blank to use your login name (<strong><?= h((string) ($doctor['username'] ?? '')) ?></strong>).
        Documents already issued keep the name from when they were created.
      </div>
    </div>
    <div class="muted" style="margin-bottom: 14px; font-size: 13px;">
      Preview on documents:
      <strong><?= h($documentNamePreview !== '' ? $documentNamePreview : 'Dr. ' . ($doctor['username'] ?? '')) ?></strong>
    </div>
    <div class="row form-actions">
      <a class="btn" href="<?= h(app_url('/doctor')) ?>" style="box-shadow:none;">Cancel</a>
      <button class="btn ok" type="submit" style="box-shadow:none;">Save document name</button>
    </div>
  </form>
</div>
