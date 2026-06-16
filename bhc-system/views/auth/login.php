<div class="grid cols-2">
  <div class="card">
    <h1>Login</h1>
    <div class="muted">Sign in with your username and password to manage patient records, queues, and station operations.</div>

    <?php if (!empty($error)): ?>
      <div class="error" style="margin-top: 14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($uLogin ?? '/login') ?>" style="margin-top: 14px;">
      <div class="grid cols-2">
        <div class="span-2">
          <label>Username</label>
          <input name="username" autocomplete="username" required />
        </div>
        <div class="span-2">
          <label>Password</label>
          <input type="password" name="password" autocomplete="current-password" required />
        </div>
        <div class="row span-2 form-actions">
          <button class="btn ok" type="submit">Login</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card">
    <h2><?= h(app_name()) ?></h2>
    <div class="muted">Balong Bato • San Juan City</div>
    <div class="card" style="margin-top: 12px; background: var(--surface2);">
      <div style="margin-top: 4px;"><strong>Need an account?</strong></div>
      <div class="muted" style="margin-top: 6px;">Ask an <strong>administrator</strong> to create your account under <strong>Staff accounts</strong> in the system.</div>
    </div>
    <div class="muted" style="margin-top: 14px;">
      <strong>Waiting area displays</strong> (no login):
    </div>
    <div class="row" style="margin-top: 10px;">
      <a class="btn" href="<?= h(app_url('/display/2')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Triage display</a>
      <a class="btn" href="<?= h(app_url('/display/4')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Pharmacy display</a>
    </div>
    <div class="row" style="margin-top: 12px;">
      <a class="btn" href="<?= htmlspecialchars($uHome ?? '/') ?>" style="box-shadow:none;">Back to home</a>
    </div>
    <div class="muted" style="margin-top: 16px; padding-top: 12px; border-top: 1px solid rgba(15, 23, 42, 0.08); font-size: 12px; line-height: 1.5;">
      Developed for <?= htmlspecialchars($bhcDevelopedFor ?? 'Brgy. Balong Bato - Brgy. Health Center') ?> &mdash; <?= htmlspecialchars($bhcDeveloperCredit ?? 'MSIT/PUP Graduate School - PUP Manila') ?>
    </div>
  </div>
</div>
