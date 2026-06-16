<?php
$ticketUrl = app_full_url('/ticket/' . (int) $ticket['id']);
?>

<style>
  .qr-full {
    min-height: calc(100vh - 40px);
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 14px;
    align-items: center;
  }
  @media (max-width: 900px) { .qr-full { grid-template-columns: 1fr; } }
  .qr-box { display:flex; justify-content:center; align-items:center; padding: 20px; }
  .qr-ticket { font-size: 72px; font-weight: 900; letter-spacing: 1px; line-height: 1; }
  .qr-station { font-size: 22px; font-weight: 800; }
  .qr-help { font-size: 14px; color: var(--muted); }
</style>

<div class="qr-full">
  <div class="card qr-box">
    <div id="qrBig"></div>
  </div>

  <div class="card">
    <div class="pill waiting">Scan to view ticket</div>
    <div style="margin-top: 12px;" class="qr-station"><?= h($station['name'] ?? 'Station') ?></div>
    <div style="margin-top: 10px;" class="qr-ticket"><?= h($ticket['ticket_no']) ?></div>
    <div class="qr-help" style="margin-top: 10px;">Open the camera app, scan the QR, then bookmark the page.</div>
    <div class="qr-help" style="margin-top: 10px; word-break: break-all;">
      URL: <strong><?= h($ticketUrl) ?></strong>
    </div>
    <div class="row" style="justify-content: flex-end; margin-top: 14px;">
      <button class="btn" type="button" onclick="document.documentElement.requestFullscreen?.()">Fullscreen</button>
      <a class="btn" href="<?= h(app_url('/ticket/' . (int) $ticket['id'])) ?>">Back</a>
    </div>
  </div>
</div>

<script src="<?= h(app_url('/assets/qrcode.min.js')) ?>"></script>
<script>
  (function () {
    try {
      var el = document.getElementById('qrBig');
      if (!el || !window.QRCode) return;
      new QRCode(el, { width: 420, height: 420, correctLevel: QRCode.CorrectLevel ? QRCode.CorrectLevel.H : 2 }).makeCode(<?= json_encode($ticketUrl) ?>);
    } catch (e) {}
  })();
</script>

