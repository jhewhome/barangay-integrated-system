<?php
$filter = $filter ?? ReportPeriod::resolve([]);
$periodLabel = $periodLabel ?? ($filter['label'] ?? '');
$totals = $totals ?? ['total' => 0, 'scheduled' => 0, 'completed' => 0, 'cancelled' => 0, 'no_show' => 0, 'unique_patients' => 0];
$showRate = (int) ($showRate ?? 0);
$appointments = $appointments ?? [];
require __DIR__ . '/../partials/report_print_styles.php';

function appt_pill(string $status): string
{
    return match ($status) {
        'scheduled' => 'waiting',
        'completed' => 'done',
        'cancelled' => 'skipped',
        'no_show' => 'skipped',
        default => $status,
    };
}
?>

<div class="report-print-header">
  <div style="font-weight: 700; font-size: 18px;">Barangay Health Center — Appointments Report</div>
  <div class="muted"><?= h($periodLabel) ?></div>
</div>

<?php require __DIR__ . '/../partials/report_nav.php'; ?>

<div class="row row-between page-header no-print">
  <div class="row-body">
    <h1 style="margin-bottom: 6px;">Appointments report</h1>
    <div class="muted">Follow-up visits for <?= h($periodLabel) ?>.</div>
  </div>
  <?php
    $formAction = '/reports/appointments';
    $exportPath = '/reports/appointments/export';
    require __DIR__ . '/../partials/report_period_filter.php';
  ?>
</div>

<div class="grid cols-3" style="margin-top: 14px;">
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Total appointments</div>
    <div class="big"><?= (int) $totals['total'] ?></div>
    <div class="muted" style="font-size: 13px; margin-top: 4px;"><?= (int) $totals['unique_patients'] ?> unique patients</div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Completed / No-show / Cancelled</div>
    <div style="margin-top: 8px;">
      <span class="pill done">Done <?= (int) $totals['completed'] ?></span>
      <span class="pill skipped" style="margin-left: 6px;">No-show <?= (int) $totals['no_show'] ?></span>
      <span class="pill skipped" style="margin-left: 6px;">Cancelled <?= (int) $totals['cancelled'] ?></span>
    </div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Still scheduled</div>
    <div class="big"><?= (int) $totals['scheduled'] ?></div>
    <?php if ($showRate > 0): ?>
      <div class="muted" style="font-size: 13px; margin-top: 4px;">Completion rate (of resolved): <?= $showRate ?>%</div>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 10px;">Appointment list</h2>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Patient</th>
          <th>BHC ID</th>
          <th>Purpose</th>
          <th>Station</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr><td colspan="7" class="muted">No appointments in this period.</td></tr>
        <?php else: ?>
          <?php foreach ($appointments as $a): ?>
            <tr>
              <td><?= h($a['appointment_date']) ?></td>
              <td class="muted"><?= !empty($a['appointment_time']) ? h(substr((string) $a['appointment_time'], 0, 5)) : '—' ?></td>
              <td><?= h($a['full_name']) ?></td>
              <td><?= h($a['bhc_id']) ?></td>
              <td><?= h($a['purpose'] ?? '') ?></td>
              <td class="muted"><?= h($a['station_name'] ?? '—') ?></td>
              <td><span class="pill <?= appt_pill((string) $a['status']) ?>"><?= h(strtoupper(str_replace('_', ' ', (string) $a['status']))) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
