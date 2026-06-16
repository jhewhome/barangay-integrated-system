<?php
$errors = $errors ?? [];
$old = $old ?? [];
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Add account</h1>
    <div class="muted">Create a staff, doctor, or admin login.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(app_url('/users')) ?>">Back to list</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="card" style="margin-top: 14px; border-color: rgba(239, 62, 91, 0.35);">
    <strong>Please fix the following:</strong>
    <ul style="margin: 10px 0 0; padding-left: 20px;">
      <?php foreach ($errors as $e): ?>
        <li><?= h($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card card-narrow" style="margin-top: 14px;">
  <form method="POST" action="<?= h(app_route('/users')) ?>">
    <div style="margin-bottom: 14px;">
      <label>Username</label>
      <input name="username" value="<?= h($old['username'] ?? '') ?>" autocomplete="off" required placeholder="e.g. nurse1" />
      <div class="muted" style="margin-top: 6px; font-size: 12px;">3–50 characters: letters, numbers, dot, underscore, hyphen.</div>
    </div>
    <div style="margin-bottom: 14px;">
      <label>Password</label>
      <input type="password" name="password" autocomplete="new-password" required />
      <div class="muted" style="margin-top: 6px; font-size: 12px;">Minimum 8 characters.</div>
    </div>
    <div style="margin-bottom: 14px;">
      <label>Confirm password</label>
      <input type="password" name="password_confirm" autocomplete="new-password" required />
    </div>
    <div style="margin-bottom: 14px;">
      <label>Display name (optional)</label>
      <input name="display_name" value="<?= h($old['display_name'] ?? '') ?>" placeholder="e.g. Dr. Maria S. Cruz (doctors can change this later on My patients)" />
    </div>
    <div style="margin-bottom: 18px;">
      <label>Role</label>
      <select name="role" required>
        <option value="staff" <?= ($old['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
        <option value="doctor" <?= ($old['role'] ?? '') === 'doctor' ? 'selected' : '' ?>>Doctor</option>
        <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </div>
    <div class="row form-actions">
      <a class="btn" href="<?= h(app_url('/users')) ?>" style="box-shadow:none;">Cancel</a>
      <button class="btn ok" type="submit">Create account</button>
    </div>
  </form>
</div>
