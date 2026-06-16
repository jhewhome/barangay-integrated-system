<?php
$stats = $stats ?? ['patients_total' => 0, 'queue_today' => ['waiting' => 0, 'serving' => 0, 'done' => 0, 'skipped' => 0], 'stations_active' => 0];
$user = $_SESSION['user'] ?? null;
$username = h((string) ($user['username'] ?? ''));
?>

<div class="card dashboard-hero-card" style="padding: 18px;">
  <div class="row row-between dashboard-hero page-header">
    <div class="row-body">
      <div class="row row-pills">
        <div class="pill serving">Administrator</div>
        <div class="pill">Balong Bato • San Juan City</div>
      </div>
      <h1 style="margin-top: 12px; margin-bottom: 6px;">Welcome, <?= $username ?></h1>
      <p class="muted dashboard-intro">
        This is your <strong>administration hub</strong> for the Barangay Health Center patient registry and multi-station queue.
        You have full access: daily clinic operations <em>and</em> oversight tools for staff accounts, monthly reports, and the activity log.
      </p>
      <ul class="muted dashboard-tips">
        <li><strong>Clinic floor:</strong> register patients, route tickets, call queues, recall skipped patients — same as staff.</li>
        <li><strong>Administration:</strong> add or reset staff logins, review ticket totals by month, audit who did what and when.</li>
        <li><strong>Waiting area:</strong> open a station display on a TV; tap <strong>Enable sound</strong> once so patients hear the call chime.</li>
      </ul>
    </div>
    <div class="row-actions welcome-actions" aria-label="Quick actions">
      <a class="btn ok" href="<?= h(app_url('/queue/1')) ?>" style="box-shadow:none;">Patient Routing</a>
      <a class="btn" href="<?= h(app_url('/users')) ?>" style="box-shadow:none;">Staff accounts</a>
      <a class="btn" href="<?= h(app_url('/reports')) ?>" style="box-shadow:none;">Reports</a>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <div class="row row-between card-header" style="margin-bottom: 10px;">
    <h2 style="margin:0;">Today at a glance</h2>
    <span class="pill waiting">All stations · today</span>
  </div>
  <div class="muted" style="margin-bottom: 12px; font-size: 14px;">
    Live counts for tickets created today across Triage, Consultation, and Pharmacy. Use <strong>Queue Stations</strong> or <strong>Queue Management</strong> to serve patients.
  </div>
  <div class="row row-stats-pills">
    <span class="pill">Patients in registry: <strong><?= (int) $stats['patients_total'] ?></strong></span>
    <span class="pill">Active stations: <strong><?= (int) $stats['stations_active'] ?></strong></span>
    <span class="pill waiting">Waiting: <strong><?= (int) $stats['queue_today']['waiting'] ?></strong></span>
    <span class="pill serving">Serving: <strong><?= (int) $stats['queue_today']['serving'] ?></strong></span>
    <span class="pill done">Done: <strong><?= (int) $stats['queue_today']['done'] ?></strong></span>
    <span class="pill skipped">Skipped: <strong><?= (int) $stats['queue_today']['skipped'] ?></strong></span>
  </div>
</div>

<?php require __DIR__ . '/../partials/appointment_reminders.php'; ?>

<h2 style="margin: 14px 0 10px;">Administration</h2>
<p class="muted" style="margin: 0 0 10px; font-size: 14px;">Tools only administrators can open. Staff accounts do not see these in the sidebar.</p>

<div class="grid cols-3">
  <div class="card" style="background: var(--surface2);">
    <div class="pill serving">Staff accounts</div>
    <div style="margin-top: 10px;"><strong>Manage logins</strong></div>
    <div class="muted" style="margin-top: 6px;">Add staff or admin users, reset passwords, activate or deactivate accounts.</div>
    <div class="row-actions row-actions-tight" style="margin-top: 12px;">
      <a class="btn ok" href="<?= h(app_url('/users')) ?>" style="box-shadow:none;">Open Staff accounts</a>
      <a class="btn" href="<?= h(app_url('/users/create')) ?>" style="box-shadow:none;">Add account</a>
    </div>
  </div>

  <div class="card" style="background: var(--surface2);">
    <div class="pill">Reports</div>
    <div style="margin-top: 10px;"><strong>Operational summaries</strong></div>
    <div class="muted" style="margin-top: 6px;">Queue, clinical, and appointment reports by day, week, month, or custom range.</div>
    <div class="row-actions row-actions-tight" style="margin-top: 12px;">
      <a class="btn ok" href="<?= h(app_url('/reports')) ?>" style="box-shadow:none;">Open reports</a>
    </div>
  </div>

  <div class="card" style="background: var(--surface2);">
    <div class="pill">Activity Log</div>
    <div style="margin-top: 10px;"><strong>Accountability</strong></div>
    <div class="muted" style="margin-top: 6px;">Logins, queue actions, and staff account changes.</div>
    <div class="row-actions row-actions-tight" style="margin-top: 12px;">
      <a class="btn ok" href="<?= h(app_url('/audit')) ?>" style="box-shadow:none;">Open log</a>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Daily operations</h2>
  <div class="muted" style="margin-bottom: 12px;">Same workflow as staff — use these during clinic hours.</div>
  <div class="grid cols-3">
    <div class="card" style="background: var(--surface2); padding: 14px;">
      <div class="pill waiting">Step 1</div>
      <strong style="display:block; margin-top: 8px;">Patient Registry</strong>
      <div class="muted" style="margin-top: 6px; font-size: 13px;">Add new patients or find existing records (BHC ID).</div>
      <div class="row-actions row-actions-tight" style="margin-top: 10px;">
        <a class="btn" href="<?= h(app_url('/patients')) ?>" style="box-shadow:none;">Browse</a>
        <a class="btn ok" href="<?= h(app_url('/patients/create')) ?>" style="box-shadow:none;">Add</a>
      </div>
    </div>
    <div class="card" style="background: var(--surface2); padding: 14px;">
      <div class="pill serving">Step 2</div>
      <strong style="display:block; margin-top: 8px;">Patient Routing</strong>
      <div class="muted" style="margin-top: 6px; font-size: 13px;">Reason for visit, assign station, print/show ticket + QR.</div>
      <div class="row-actions row-actions-tight" style="margin-top: 10px;">
        <a class="btn ok" href="<?= h(app_url('/queue/1')) ?>" style="box-shadow:none;">Open routing desk</a>
      </div>
    </div>
    <div class="card" style="background: var(--surface2); padding: 14px;">
      <div class="pill">Step 3</div>
      <strong style="display:block; margin-top: 8px;">Serve the queue</strong>
      <div class="muted" style="margin-top: 6px; font-size: 13px;">Call, complete, skip, or recall patients at each station.</div>
      <div class="row-actions row-actions-tight" style="margin-top: 10px;">
        <a class="btn ok" href="<?= h(app_url('/stations')) ?>" style="box-shadow:none;">Queue Stations</a>
        <a class="btn" href="<?= h(app_url('/coordinator')) ?>" style="box-shadow:none;">Queue Management</a>
      </div>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Waiting area displays</h2>
  <div class="muted">Open on a TV or monitor (no login). Tap <strong>Enable sound</strong> on the display once each day.</div>
  <div class="row-actions row-actions-tight display-links" style="margin-top: 12px;">
    <a class="btn" href="<?= h(app_url('/display/2')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Triage display</a>
    <a class="btn" href="<?= h(app_url('/display/3')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Consultation display</a>
    <a class="btn" href="<?= h(app_url('/display/4')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Pharmacy display</a>
  </div>
</div>
