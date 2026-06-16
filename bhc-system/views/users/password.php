<?php
$target = $target ?? null;
$errors = $errors ?? [];
if (!$target) {
    echo '<div class="muted">User not found.</div>';
    return;
}
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Reset password</h1>
    <div class="muted">Set a new password for <strong><?= h($target['username']) ?></strong>.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(app_url('/users')) ?>">Back to list</a>
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

<div class="card card-narrow" style="margin-top: 14px;">
  <form method="POST" action="<?= h(app_route('/users/' . (int) $target['id'] . '/password')) ?>">
    <div style="margin-bottom: 14px;">
      <label>New password</label>
      <input type="password" name="password" autocomplete="new-password" required />
    </div>
    <div style="margin-bottom: 18px;">
      <label>Confirm new password</label>
      <input type="password" name="password_confirm" autocomplete="new-password" required />
    </div>
    <div class="row form-actions">
      <a class="btn" href="<?= h(app_url('/users')) ?>" style="box-shadow:none;">Cancel</a>
      <button class="btn ok" type="submit">Update password</button>
    </div>
  </form>
</div>
