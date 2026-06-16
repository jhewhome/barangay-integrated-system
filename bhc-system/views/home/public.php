<?php
$stats = $stats ?? ['patients_total' => 0, 'queue_today' => ['waiting' => 0, 'serving' => 0, 'done' => 0, 'skipped' => 0], 'stations_active' => 0];
?>

<div class="card" style="padding: 18px;">
  <div class="row" style="justify-content: space-between; align-items: flex-start;">
    <div>
      <div class="row">
        <div class="pill serving">Balong Bato • San Juan City</div>
        <div class="pill waiting">Multi-station queue</div>
        <div class="pill">Paperless QR tickets</div>
      </div>
      <h1 style="margin-top: 12px; margin-bottom: 6px;"><?= h(app_name()) ?></h1>
      <div class="muted">
        <?= h(app_tagline()) ?>
      </div>
    </div>
    <div class="row" style="justify-content: flex-end;">
      <a class="btn ok" href="<?= h(app_url('/login')) ?>">Login</a>
      <a class="btn" href="<?= h(app_url('/display/2')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Waiting display</a>
    </div>
  </div>

  <div class="grid cols-3" style="margin-top: 14px;">
    <div class="card" style="background: var(--surface2);">
      <div class="pill">Account access</div>
      <div style="margin-top: 10px;"><strong>Login</strong></div>
      <div class="muted" style="margin-top: 6px;">For staff and administrator accounts. Use your assigned username and password.</div>
      <div class="row" style="margin-top: 12px;">
        <a class="btn ok" href="<?= h(app_url('/login')) ?>" style="box-shadow:none;">Login</a>
      </div>
    </div>
    <div class="card" style="background: var(--surface2);">
      <div class="pill waiting">Today</div>
      <div style="margin-top: 10px;"><strong>Patients registered: <?= (int) $stats['patients_total'] ?></strong></div>
      <div class="muted" style="margin-top: 6px;">Queue tickets today: waiting <?= (int) $stats['queue_today']['waiting'] ?>, serving <?= (int) $stats['queue_today']['serving'] ?>.</div>
    </div>
    <div class="card" style="background: var(--surface2);">
      <div class="pill">Waiting area</div>
      <div style="margin-top: 10px;"><strong>Patient display (TV)</strong></div>
      <div class="muted" style="margin-top: 6px;">Shows Now serving and next tickets with audio cue.</div>
      <div class="row" style="margin-top: 12px;">
        <a class="btn" href="<?= h(app_url('/display/2')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Triage</a>
        <a class="btn" href="<?= h(app_url('/display/4')) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Pharmacy</a>
      </div>
    </div>
  </div>
</div>
