<?php
function fmt_duration(int $seconds): string
{
  if ($seconds <= 0) return '—';
  $mins = (int) floor($seconds / 60);
  $hrs = (int) floor($mins / 60);
  $mins = $mins % 60;
  if ($hrs > 0) return $hrs . 'h ' . $mins . 'm';
  return $mins . 'm';
}
$date = $date ?? date('Y-m-d');
$dateLabel = $dateLabel ?? $date;
$totals = $totals ?? ['waiting'=>0,'serving'=>0,'done'=>0,'skipped'=>0,'total'=>0];
$visitStats = $visitStats ?? ['total'=>0,'open'=>0,'completed'=>0];
$triageStats = $triageStats ?? ['total'=>0,'with_vitals'=>0];
$byStation = $byStation ?? [];
$topReasons = $topReasons ?? [];
$hourlyVolume = $hourlyVolume ?? [];
$maxHourly = 1;
foreach ($hourlyVolume as $hrow) {
  $maxHourly = max($maxHourly, (int) ($hrow['ticket_count'] ?? 0));
}
require __DIR__ . '/../partials/report_print_styles.php';
?>

<div class="report-print-header">
  <div style="font-weight: 700; font-size: 18px;">Barangay Health Center — Daily Operations Report</div>
  <div class="muted"><?= h($dateLabel) ?></div>
</div>

<?php require __DIR__ . '/../partials/report_nav.php'; ?>

<div class="row row-between page-header no-print">
  <div class="row-body">
    <h1 style="margin-bottom: 6px;">Daily operations</h1>
    <div class="muted">End-of-day summary: visits, queue tickets, triage, reasons, and peak hours for <?= h($dateLabel) ?>.</div>
  </div>
  <form method="GET" action="<?= h(app_url('/reports/daily')) ?>" class="row-actions row-actions-tight reports-filter">
    <div class="reports-filter-field">
      <label>Date</label>
      <input type="date" name="date" value="<?= h($date) ?>" />
    </div>
    <div class="reports-filter-submit">
      <button class="btn" type="submit">View</button>
      <a class="btn ok" href="<?= h(app_url('/reports/daily/export?date=' . urlencode($date))) ?>" style="box-shadow:none;">Export CSV</a>
      <button class="btn" type="button" onclick="window.print()" style="box-shadow:none;">Print</button>
    </div>
  </form>
</div>

<div class="grid cols-3" style="margin-top: 14px;">
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Queue tickets</div>
    <div class="big"><?= (int) $totals['total'] ?></div>
    <div class="muted" style="font-size: 13px; margin-top: 4px;">
      Done <?= (int) $totals['done'] ?> · Skipped <?= (int) $totals['skipped'] ?>
    </div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Patient visits</div>
    <div class="big"><?= (int) $visitStats['total'] ?></div>
    <div class="muted" style="font-size: 13px; margin-top: 4px;">
      Open <?= (int) $visitStats['open'] ?> · Completed <?= (int) $visitStats['completed'] ?>
    </div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Triage records</div>
    <div class="big"><?= (int) $triageStats['total'] ?></div>
    <div class="muted" style="font-size: 13px; margin-top: 4px;">
      <?= (int) $triageStats['with_vitals'] ?> with vitals captured
    </div>
  </div>
</div>

<div class="grid cols-2" style="margin-top: 14px; gap: 14px;">
  <div class="card">
    <h2 style="margin: 0 0 10px;">Tickets by station</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Station</th>
            <th>Total</th>
            <th>Done</th>
            <th>Skipped</th>
            <th>Avg wait</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($byStation)): ?>
            <tr><td colspan="5" class="muted">No tickets for this date.</td></tr>
          <?php else: ?>
            <?php foreach ($byStation as $r): ?>
              <?php if ((int) $r['total'] === 0) continue; ?>
              <tr>
                <td><strong><?= h($r['station_name']) ?></strong></td>
                <td><?= (int) $r['total'] ?></td>
                <td><?= (int) $r['done'] ?></td>
                <td><?= (int) $r['skipped'] ?></td>
                <td class="muted"><?= h(fmt_duration((int) $r['avg_wait_seconds'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <h2 style="margin: 0 0 10px;">Reason for visit</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Reason</th><th>Tickets</th></tr></thead>
        <tbody>
          <?php if (empty($topReasons)): ?>
            <tr><td colspan="2" class="muted">No routing reasons recorded.</td></tr>
          <?php else: ?>
            <?php foreach ($topReasons as $row): ?>
              <tr>
                <td><?= h($row['reason_label']) ?></td>
                <td><strong><?= (int) $row['ticket_count'] ?></strong></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 10px;">Arrivals by hour</h2>
  <p class="muted" style="margin: 0 0 12px; font-size: 13px;">Queue tickets created per hour (clinic hours 7:00–17:00).</p>
  <div style="display: grid; gap: 8px;">
    <?php foreach ($hourlyVolume as $row): ?>
      <?php
        $count = (int) ($row['ticket_count'] ?? 0);
        $pct = $maxHourly > 0 ? (int) round(($count / $maxHourly) * 100) : 0;
      ?>
      <div style="display: grid; grid-template-columns: 52px 1fr 36px; gap: 10px; align-items: center; font-size: 13px;">
        <span class="muted"><?= sprintf('%02d:00', (int) $row['hour']) ?></span>
        <div style="background: var(--surface2); border-radius: 6px; height: 18px; overflow: hidden;">
          <div style="width: <?= $pct ?>%; height: 100%; background: rgba(47, 107, 255, 0.55);"></div>
        </div>
        <strong><?= $count ?></strong>
      </div>
    <?php endforeach; ?>
  </div>
</div>
