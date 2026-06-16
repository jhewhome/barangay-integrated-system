<?php


$stats = $stats ?? ['patients_total' => 0, 'queue_today' => ['waiting' => 0, 'serving' => 0, 'done' => 0, 'skipped' => 0], 'stations_active' => 0];

$user = $_SESSION['user'] ?? null;

$username = h((string) ($user['username'] ?? ''));

?>



<div class="card dashboard-hero-card" style="padding: 18px;">

  <div class="row row-between dashboard-hero page-header">

    <div class="row-body">

      <div class="row row-pills">

        <div class="pill serving">Staff workspace</div>

        <div class="pill">Balong Bato • San Juan City</div>

      </div>

      <h1 style="margin-top: 12px; margin-bottom: 6px;">Welcome, <?= $username ?></h1>

      <p class="muted dashboard-intro">

        Your <strong>staff workspace</strong> for daily clinic work: register patients, route them to the right station,

        and manage queues at Triage, Consultation, or Pharmacy.

      </p>

      <ul class="muted dashboard-tips">

        <li>Follow <strong>Steps 1–3</strong> below when a patient arrives.</li>

        <li>Use <strong>Queue Management</strong> on busy days to watch all stations from one screen.</li>

        <li>Skipped a patient by mistake? Open their station and use <strong>Recall</strong> if they are still on site.</li>

      </ul>

    </div>

    <div class="row-actions welcome-actions" aria-label="Quick actions">

      <a class="btn ok" href="<?= h(app_url('/queue/1')) ?>" style="box-shadow:none;">Patient Routing</a>

      <?php $isAdmin = false; require __DIR__ . '/../partials/patient_register_actions.php'; ?>

    </div>

  </div>

</div>



<div class="grid cols-3" style="margin-top: 14px;">

  <div class="card">

    <div class="pill waiting">Step 1</div>

    <h2 style="margin: 10px 0 6px;">Patient Registry</h2>

    <div class="muted">Register patients from Gawad BIS, or use emergency walk-in when needed. Browse existing BHC records below.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <?php $isAdmin = false; $wrapClass = 'row-actions row-actions-tight'; require __DIR__ . '/../partials/patient_register_actions.php'; ?>

      <a class="btn" href="<?= h(app_url('/patients')) ?>" style="box-shadow:none;">Browse</a>

    </div>

  </div>



  <div class="card">

    <div class="pill serving">Step 2</div>

    <h2 style="margin: 10px 0 6px;">Patient Routing</h2>

    <div class="muted">Search patient, enter reason for visit, assign station, create ticket + QR.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <a class="btn ok" href="<?= h(app_url('/queue/1')) ?>" style="box-shadow:none;">Open routing desk</a>

    </div>

  </div>



  <div class="card">

    <div class="pill">Step 3</div>

    <h2 style="margin: 10px 0 6px;">Serve the queue</h2>

    <div class="muted">Call next, complete, or skip at your service station.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <a class="btn ok" href="<?= h(app_url('/stations')) ?>" style="box-shadow:none;">Queue Stations</a>

      <a class="btn" href="<?= h(app_url('/coordinator')) ?>" style="box-shadow:none;">Queue Management</a>

    </div>

  </div>

</div>



<div class="card" style="margin-top: 14px;">

  <div class="row row-between card-header" style="margin-bottom: 10px;">

    <h2 style="margin:0;">Today at a glance</h2>

    <span class="pill waiting">Live queue counts</span>

  </div>

  <div class="muted" style="margin-bottom: 12px; font-size: 14px;">

    Ticket activity for today across all service stations. Counts update as you call and complete patients.

  </div>

  <div class="row row-stats-pills">

    <span class="pill">Patients in registry: <strong><?= (int) $stats['patients_total'] ?></strong></span>

    <span class="pill waiting">Waiting: <strong><?= (int) $stats['queue_today']['waiting'] ?></strong></span>

    <span class="pill serving">Serving: <strong><?= (int) $stats['queue_today']['serving'] ?></strong></span>

    <span class="pill done">Done: <strong><?= (int) $stats['queue_today']['done'] ?></strong></span>

    <span class="pill skipped">Skipped: <strong><?= (int) $stats['queue_today']['skipped'] ?></strong></span>

  </div>

</div>

<?php require __DIR__ . '/../partials/appointment_reminders.php'; ?>

<div class="card" style="margin-top: 14px;">

  <h2 style="margin: 0 0 8px;">Waiting area displays</h2>

  <div class="muted">Open on a TV or monitor in the waiting area (no login). Tap <strong>Enable sound</strong> on the display once each day.</div>

  <div class="row-actions row-actions-tight display-links" style="margin-top: 12px;">

    <a class="btn" href="<?= h(app_url('/display/2')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Triage display</a>

    <a class="btn" href="<?= h(app_url('/display/3')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Consultation display</a>

    <a class="btn" href="<?= h(app_url('/display/4')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Pharmacy display</a>

  </div>

</div>


