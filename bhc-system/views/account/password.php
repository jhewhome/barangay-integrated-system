<?php
$errors = $errors ?? [];
$user = $_SESSION['user'] ?? null;
$dashboardUrl = match ($user['role'] ?? '') {
    'admin' => '/admin',
    'doctor' => '/doctor',
    default => '/staff',
};
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Change password</h1>
    <div class="muted">Update your login password for <strong><?= h($user['username'] ?? '') ?></strong>.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h($dashboardUrl) ?>">Back to dashboard</a>
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
  <form method="POST" action="<?= h(app_route('/account/password')) ?>">
    <div style="margin-bottom: 14px;">
      <label>New password</label>
      <input type="password" name="password" autocomplete="new-password" required />
    </div>
    <div style="margin-bottom: 18px;">
      <label>Confirm new password</label>
      <input type="password" name="password_confirm" autocomplete="new-password" required />
    </div>
    <div class="muted" style="margin-bottom: 14px; font-size: 13px;">Minimum 8 characters. Admins can also reset passwords under Staff accounts.</div>
    <div class="row form-actions">
      <a class="btn" href="<?= h($dashboardUrl) ?>" style="box-shadow:none;">Cancel</a>
      <button class="btn ok" type="submit">Update password</button>
    </div>
  </form>
</div>
