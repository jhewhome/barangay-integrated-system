<?php
$board = $board ?? [];
$base = isset($basePath) ? (string) $basePath : '';
function coord_url(string $base, string $path): string
{
  if ($path[0] !== '/') $path = '/' . $path;
  return ($base === '' ? '' : $base) . $path;
}
?>

<meta http-equiv="refresh" content="5">

<div class="row row-between page-header">
  <div class="row-body">
    <h1 style="margin-bottom: 6px;">Queue Management</h1>
    <div class="muted">Manage all service stations from one screen. Auto-refreshes every 5 seconds.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(coord_url($base, '/stations')) ?>" style="box-shadow:none;">Queue Stations</a>
  </div>
</div>

<div class="grid cols-3" style="margin-top: 14px;">
  <?php foreach ($board as $row): ?>
    <?php
      $station = $row['station'];
      $sid = (int) $station['id'];
      $nowServing = $row['nowServing'];
      $waiting = $row['waiting'];
      $skipped = $row['skipped'] ?? [];
      $c = $row['counts'];
    ?>
    <div class="card" style="background: var(--surface2);">
      <div class="row" style="justify-content: space-between; align-items: flex-start;">
        <div>
          <div class="pill serving"><?= h($station['name']) ?></div>
          <div class="row" style="margin-top: 8px; gap: 8px;">
            <span class="pill waiting">Waiting: <strong><?= (int) $c['waiting'] ?></strong></span>
            <span class="pill serving">Serving: <strong><?= (int) $c['serving'] ?></strong></span>
          </div>
        </div>
        <a class="btn" href="<?= h(coord_url($base, '/queue/' . $sid)) ?>" style="box-shadow:none; font-size: 12px; padding: 8px 10px;">Open</a>
      </div>

      <div class="card" style="margin-top: 12px; padding: 12px;">
        <div class="muted" style="font-size: 12px;">Now serving</div>
        <?php if ($nowServing): ?>
          <div style="margin-top: 6px;"><strong><?= h($nowServing['ticket_no']) ?></strong></div>
          <div class="muted"><?= h($nowServing['full_name']) ?></div>
          <div class="row-actions row-actions-tight" style="margin-top: 10px;">
            <form method="POST" action="<?= h(coord_url($base, '/queue/' . $sid . '/complete/' . (int) $nowServing['id'])) ?>">
              <button class="btn ok" type="submit" style="box-shadow:none;">Complete</button>
            </form>
            <form method="POST" action="<?= h(coord_url($base, '/queue/' . $sid . '/skip/' . (int) $nowServing['id'])) ?>">
              <button class="btn danger" type="submit" style="box-shadow:none;">Skip</button>
            </form>
          </div>
          <div class="muted" style="margin-top: 8px; font-size: 12px;">Complete/Skip will auto-call the next waiting patient.</div>
        <?php else: ?>
          <div class="muted" style="margin-top: 6px;">No active ticket.</div>
          <form method="POST" action="<?= h(coord_url($base, '/queue/' . $sid . '/call-next')) ?>" style="margin-top: 10px;">
            <button class="btn" type="submit" style="box-shadow:none;">Call next</button>
          </form>
        <?php endif; ?>
      </div>

      <div style="margin-top: 12px;">
        <div class="muted" style="font-size: 12px; margin-bottom: 8px;">Next waiting</div>
        <?php if (empty($waiting)): ?>
          <div class="muted">None</div>
        <?php else: ?>
          <?php foreach ($waiting as $w): ?>
            <div class="row row-queue-item" style="justify-content: space-between; margin-bottom: 8px; padding: 8px; background: var(--surface); border-radius: 10px; border: 1px solid rgba(15,23,42,0.08);">
              <div style="min-width: 0;">
                <strong><?= h($w['ticket_no']) ?></strong>
                <span class="muted"> — <?= h($w['full_name']) ?></span>
              </div>
              <form method="POST" action="<?= h(coord_url($base, '/queue/' . $sid . '/call/' . (int) $w['id'])) ?>">
                <button class="btn" type="submit" style="box-shadow:none; padding: 6px 10px;" <?= $nowServing ? 'disabled' : '' ?>>Call</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if (!empty($skipped)): ?>
        <div style="margin-top: 12px;">
          <div class="muted" style="font-size: 12px; margin-bottom: 8px;">Skipped today (recall if patient returned)</div>
          <?php foreach ($skipped as $sk): ?>
            <div class="row row-queue-item" style="justify-content: space-between; margin-bottom: 8px; padding: 8px; background: var(--surface); border-radius: 10px; border: 1px solid rgba(239,62,91,0.15);">
              <div style="min-width: 0;">
                <strong><?= h($sk['ticket_no']) ?></strong>
                <span class="pill skipped" style="margin-left: 6px;">SKIPPED</span>
                <span class="muted"> — <?= h($sk['full_name']) ?></span>
              </div>
              <form method="POST" action="<?= h(coord_url($base, '/queue/' . $sid . '/recall/' . (int) $sk['id'])) ?>">
                <button class="btn" type="submit" style="box-shadow:none; padding: 6px 10px;">Recall</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
