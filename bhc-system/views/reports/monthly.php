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
$filter = $filter ?? ReportPeriod::resolve([]);
$periodLabel = $periodLabel ?? ($filter['label'] ?? '');
$totals = $totals ?? ['waiting'=>0,'serving'=>0,'done'=>0,'skipped'=>0,'total'=>0,'avg_wait_seconds'=>0,'avg_service_seconds'=>0];
$byStation = $byStation ?? [];
require __DIR__ . '/../partials/report_print_styles.php';
?>

<div class="report-print-header">
  <div style="font-weight: 700; font-size: 18px;">Barangay Health Center — Queue Report</div>
  <div class="muted"><?= h($periodLabel) ?></div>
</div>

<?php require __DIR__ . '/../partials/report_nav.php'; ?>

<div class="row row-between page-header no-print">
  <div class="row-body">
    <h1 style="margin-bottom: 6px;">Queue report</h1>
    <div class="muted">Summary of queue tickets for <?= h($periodLabel) ?> (per station).</div>
  </div>
  <?php
    $formAction = '/reports/monthly';
    $exportPath = '/reports/monthly/export';
    require __DIR__ . '/../partials/report_period_filter.php';
  ?>
</div>

<div class="grid cols-3" style="margin-top: 14px;">
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Total tickets</div>
    <div class="big"><?= (int) $totals['total'] ?></div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Completed (Done)</div>
    <div class="big"><?= (int) $totals['done'] ?></div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Skipped</div>
    <div class="big"><?= (int) $totals['skipped'] ?></div>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <div class="row" style="justify-content: space-between;">
    <h2 style="margin:0;">Station breakdown</h2>
    <div class="row" style="gap: 8px;">
      <span class="pill waiting">Avg wait: <strong><?= h(fmt_duration((int) $totals['avg_wait_seconds'])) ?></strong></span>
      <span class="pill serving">Avg service: <strong><?= h(fmt_duration((int) $totals['avg_service_seconds'])) ?></strong></span>
    </div>
  </div>

  <div style="margin-top: 12px; overflow:auto; border-radius: 12px; border: 1px solid rgba(15, 23, 42, 0.08); background: var(--surface);">
    <table>
      <thead>
        <tr>
          <th>Station</th>
          <th>Total</th>
          <th>Done</th>
          <th>Skipped</th>
          <th>Waiting</th>
          <th>Serving</th>
          <th>Avg wait</th>
          <th>Avg service</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($byStation as $r): ?>
          <tr>
            <td><strong><?= h($r['station_name']) ?></strong></td>
            <td><?= (int) $r['total'] ?></td>
            <td><?= (int) $r['done'] ?></td>
            <td><?= (int) $r['skipped'] ?></td>
            <td><?= (int) $r['waiting'] ?></td>
            <td><?= (int) $r['serving'] ?></td>
            <td class="muted"><?= h(fmt_duration((int) $r['avg_wait_seconds'])) ?></td>
            <td class="muted"><?= h(fmt_duration((int) $r['avg_service_seconds'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="muted" style="margin-top: 10px;">
    Note: “Avg wait” is time from ticket creation → called. “Avg service” is called → completed (done/skipped).
  </div>
</div>

