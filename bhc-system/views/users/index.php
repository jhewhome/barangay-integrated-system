<?php
$users = $users ?? [];
$currentUserId = (int) ($currentUserId ?? 0);
$gawadIntegrationEnabled = !empty($gawadIntegrationEnabled);
?>

<div class="row row-between page-header">
  <div class="row-body">
    <div class="pill serving">Admin</div>
    <h1 style="margin-top: 10px;">Staff accounts</h1>
    <div class="muted">Add and manage staff logins. Deactivated users cannot sign in.<?php if ($gawadIntegrationEnabled): ?> Import from Gawad BIS to create matching usernames for Health Center SSO.<?php endif; ?></div>
  </div>
  <div class="row-actions">
    <a class="btn ok" href="<?= h(app_url('/users/create')) ?>">Add staff account</a>
  </div>
</div>

<?php if ($gawadIntegrationEnabled): ?>
<div class="card" style="margin-top: 14px; border-left: 4px solid var(--pri, #2f6bff);">
  <h2 style="margin: 0 0 8px;">Import from Gawad BIS</h2>
  <div class="muted" style="margin-bottom: 12px;">
    Creates BHC accounts for Gawad users that do not exist yet (same username). Gawad <strong>Administrator</strong> → BHC admin; other Gawad roles → BHC staff. Existing usernames are skipped. Set a temporary password — users should change it after first login.
  </div>
  <form method="POST" action="<?= h(app_route('/users/sync-gawad')) ?>" class="grid cols-2" style="gap: 12px; align-items: end;">
    <div>
      <label for="syncDefaultPassword">Temporary password for new accounts</label>
      <input id="syncDefaultPassword" type="password" name="password" autocomplete="new-password" required minlength="8" />
    </div>
    <div>
      <label for="syncDefaultPasswordConfirm">Confirm password</label>
      <input id="syncDefaultPasswordConfirm" type="password" name="password_confirm" autocomplete="new-password" required minlength="8" />
    </div>
    <div class="span-2 row form-actions" style="margin-top: 0;">
      <label style="display: inline-flex; align-items: center; gap: 8px; margin: 0; font-weight: normal;">
        <input type="checkbox" name="dry_run" value="1" />
        Preview only (do not create accounts)
      </label>
      <button class="btn ok" type="submit">Import Gawad staff</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card" style="margin-top: 14px;">
  <table>
    <thead>
      <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Status</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="5" class="muted">No accounts yet.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <?php
            $uid = (int) $u['id'];
            $isActive = (int) $u['is_active'] === 1;
            $isSelf = $uid === $currentUserId;
          ?>
          <tr>
            <td>
              <strong><?= h($u['username']) ?></strong>
              <?php if ($isSelf): ?>
                <span class="pill serving" style="margin-left: 6px;">You</span>
              <?php endif; ?>
            </td>
            <td><?= h(match ($u['role'] ?? '') { 'admin' => 'Admin', 'doctor' => 'Doctor', default => 'Staff' }) ?><?= !empty($u['display_name']) ? ' <span class="muted">(' . h($u['display_name']) . ')</span>' : '' ?></td>
            <td>
              <?php if ($isActive): ?>
                <span class="pill done">Active</span>
              <?php else: ?>
                <span class="pill skipped">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="muted"><?= h($u['created_at']) ?></td>
            <td>
              <div class="row-actions row-actions-tight">
                <a class="btn" href="<?= h(app_url('/users/' . $uid . '/password')) ?>" style="box-shadow:none; padding: 8px 10px; font-size: 13px;">Reset password</a>
                <?php if ($isActive && !$isSelf): ?>
                  <form method="POST" action="<?= h(app_route('/users/' . $uid . '/deactivate')) ?>" style="margin:0;" onsubmit="return confirm('Deactivate this account?');">
                    <button class="btn danger" type="submit" style="box-shadow:none; padding: 8px 10px; font-size: 13px;">Deactivate</button>
                  </form>
                <?php elseif (!$isActive): ?>
                  <form method="POST" action="<?= h(app_route('/users/' . $uid . '/activate')) ?>" style="margin:0;">
                    <button class="btn ok" type="submit" style="box-shadow:none; padding: 8px 10px; font-size: 13px;">Activate</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Role guide</h2>
  <div class="muted">
    <strong>Staff</strong> — Patient Registry, Queue Stations, Queue Management.<br>
    <strong>Admin</strong> — All staff features plus Reports, Activity Log, and Staff accounts.
  </div>
</div>
