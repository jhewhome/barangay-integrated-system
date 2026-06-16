<?php
$ticketUrl = app_full_url('/ticket/' . (int) $ticket['id']);
$uQueueAlert = app_url('/assets/queue-alert.js');
$backToStationUrl = app_url('/queue/' . (int) $ticket['station_id'] . '?enqueued=' . (int) $ticket['id']);
?>

<meta http-equiv="refresh" content="5">

<div id="soundEnableBanner" class="notice" style="margin-bottom: 14px;">
  <strong>Call alert</strong> — Tap <strong>Enable sound</strong> to hear a chime when your ticket is called.
</div>

<div class="card">
  <div class="row row-between page-header">
    <div class="row-body">
      <div class="pill waiting">Queue ticket</div>
      <h1 style="margin-top: 10px; margin-bottom: 6px;"><?= h($station['name'] ?? 'Station') ?></h1>
      <div class="muted">Show this screen to confirm you are in the queue.</div>
    </div>
    <div class="row-actions">
      <button id="btnEnableSound" class="btn ok" type="button" style="box-shadow:none;">Enable sound</button>
      <a class="btn" href="<?= h($backToStationUrl) ?>">Back to station</a>
    </div>
  </div>
</div>

<div class="grid cols-2" style="margin-top: 14px;">
  <div class="card">
    <h2>Your ticket</h2>
    <div class="big"><?= h($ticket['ticket_no']) ?></div>
    <div><strong><?= h($ticket['full_name']) ?></strong> <span class="muted">(<?= h($ticket['bhc_id']) ?>)</span></div>
    <div class="muted" style="margin-top: 6px;">Status: <strong><?= strtoupper(h($ticket['status'])) ?></strong></div>
    <?php if ($ticket['status'] === 'serving'): ?>
      <div id="ticketServingAlert" class="pill serving" style="margin-top: 10px;">You are being called now — please proceed.</div>
    <?php endif; ?>
    <?php if (!empty($ticket['reason'])): ?>
      <div class="muted">Reason: <strong><?= h($ticket['reason']) ?></strong></div>
    <?php endif; ?>
    <div class="muted">Created: <?= h($ticket['created_at']) ?></div>
    <div class="row" style="justify-content: flex-end; margin-top: 12px;">
      <a class="btn" href="<?= h(app_url('/ticket/' . (int) $ticket['id'] . '/qr')) ?>" target="_blank" rel="noopener">Fullscreen QR</a>
    </div>
  </div>

  <div class="card">
    <h2>Scan QR to keep your ticket</h2>
    <div class="muted">Patients on the same Wi‑Fi/LAN can scan this to reopen their ticket anytime.</div>
    <div class="row" style="margin-top: 10px; justify-content: space-between;">
      <div class="pill waiting">Paperless</div>
      <div class="muted" style="font-size: 12px;">Uses base URL: <strong><?= h(app_full_url('/')) ?></strong></div>
    </div>
    <div class="card" style="margin-top: 12px; background: var(--surface2);">
      <div id="qrTicket" style="display:flex; justify-content:center; padding: 12px;"></div>
      <div class="muted" style="word-break: break-all; font-size: 12px;"><?= h($ticketUrl) ?></div>
    </div>

    <div style="margin-top: 14px;">
      <h2 style="margin-bottom: 6px;">Now serving</h2>
    <?php if ($nowServing): ?>
      <div class="big" style="font-size: 30px;"><?= h($nowServing['ticket_no']) ?></div>
      <div class="muted">If your ticket becomes next, please prepare to be called.</div>
    <?php else: ?>
      <div class="muted">No one is currently being served. Please wait.</div>
    <?php endif; ?>
    </div>
  </div>
</div>

<script src="<?= h(app_url('/assets/qrcode.min.js')) ?>"></script>
<script src="<?= h($uQueueAlert) ?>"></script>
<script>
  (function () {
    try {
      var el = document.getElementById('qrTicket');
      if (!el || !window.QRCode) return;
      new QRCode(el, { width: 220, height: 220 });
      el.innerHTML = '';
      new QRCode(el, { width: 220, height: 220, correctLevel: QRCode.CorrectLevel ? QRCode.CorrectLevel.H : 2 }).makeCode(<?= json_encode($ticketUrl) ?>);
    } catch (e) {}
  })();
  (function () {
    var Q = window.BhcQueueAlert;
    if (!Q) return;
    Q.wireEnableButton('btnEnableSound', 'soundEnableBanner');
    Q.watchTicketServing(
      <?= (int) $ticket['id'] ?>,
      <?= json_encode((string) $ticket['status']) ?>,
      document.getElementById('ticketServingAlert') || document.querySelector('.big')
    );
  })();
</script>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin-bottom: 6px;">Next in line (waiting)</h2>
  <div class="muted">These are the next tickets for today in this station.</div>
  <table style="margin-top: 10px;">
    <thead>
      <tr>
        <th>Ticket</th>
        <th>Patient</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($waiting)): ?>
        <tr><td colspan="3" class="muted">No waiting tickets.</td></tr>
      <?php else: ?>
        <?php foreach ($waiting as $w): ?>
          <tr style="<?= (int) $w['id'] === (int) $ticket['id'] ? 'background: rgba(47, 107, 255, 0.06);' : '' ?>">
            <td><strong><?= h($w['ticket_no']) ?></strong></td>
            <td><?= h($w['full_name']) ?> <span class="muted">(<?= h($w['bhc_id']) ?>)</span></td>
            <td class="muted"><?= h($w['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

