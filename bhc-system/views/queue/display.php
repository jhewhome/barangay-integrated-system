<?php
$displayStationId = (int) $station['id'];
$showNames = (($_GET['show'] ?? 'tickets') === 'names'); // tickets | names
$nowTicket = $nowServing ? (string) $nowServing['ticket_no'] : '';
$uQueueAlert = app_url('/assets/queue-alert.js');
?>

<meta http-equiv="refresh" content="3">

<div id="soundEnableBanner" class="notice" style="margin-bottom: 14px;">
  <strong>Sound alerts</strong> — Tap <strong>Enable sound</strong> once so patients hear a chime when a new ticket is called (required by the browser).
</div>

<div class="card">
  <div class="row row-between page-header">
    <div class="row-body">
      <div class="pill serving">Waiting Area Display</div>
      <h1 style="margin-top: 10px; margin-bottom: 6px;"><?= h($station['name']) ?></h1>
      <div class="muted">Auto-refreshes every 3 seconds. Flash + chime when Now serving changes.</div>
    </div>
    <div class="row-actions">
      <button id="btnEnableSound" class="btn ok" type="button" style="box-shadow:none;">Enable sound</button>
      <a class="btn" href="<?= h(app_url('/display/' . $displayStationId . '?show=' . ($showNames ? 'tickets' : 'names'))) ?>" style="box-shadow:none;">
        <?= $showNames ? 'Hide names' : 'Show names' ?>
      </a>
      <button class="btn" type="button" onclick="document.documentElement.requestFullscreen?.()" style="box-shadow:none;">Fullscreen</button>
      <a class="btn" href="<?= h(app_url('/queue/' . $displayStationId)) ?>">Staff view</a>
    </div>
  </div>
</div>

<div class="grid cols-2" style="margin-top: 14px; align-items: stretch;">
  <div id="nowServingCard" class="card" style="min-height: 240px;">
    <h2>Now serving</h2>
    <?php if ($nowServing): ?>
      <div class="big" style="font-size: 72px; line-height: 1;"><?= h($nowServing['ticket_no']) ?></div>
      <?php if ($showNames): ?>
        <div style="margin-top: 10px; font-weight: 800; font-size: 26px;"><?= h($nowServing['full_name']) ?></div>
      <?php endif; ?>
      <div class="muted">Please proceed to the station when called.</div>
    <?php else: ?>
      <div class="muted">Waiting for the next patient…</div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Next (waiting)</h2>
    <?php if (empty($waiting)): ?>
      <div class="muted">No waiting tickets.</div>
    <?php else: ?>
      <div class="grid" style="gap: 10px; margin-top: 10px;">
        <?php foreach ($waiting as $w): ?>
          <div class="card" style="padding: 12px; background: var(--surface2);">
            <div class="row" style="justify-content: space-between; align-items: baseline;">
              <div style="font-size: 28px; font-weight: 900; letter-spacing: 0.6px;"><?= h($w['ticket_no']) ?></div>
              <div class="pill waiting">WAITING</div>
            </div>
            <?php if ($showNames): ?>
              <div class="muted" style="margin-top: 6px; font-weight: 700;"><?= h($w['full_name']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="<?= h($uQueueAlert) ?>"></script>
<script>
  (function () {
    var Q = window.BhcQueueAlert;
    if (!Q) return;
    Q.wireEnableButton('btnEnableSound', 'soundEnableBanner');
    Q.watchDisplayServing(
      'bhc_nowserving_station_<?= (int) $station['id'] ?>',
      <?= json_encode($nowTicket) ?>,
      document.getElementById('nowServingCard')
    );
  })();
</script>

