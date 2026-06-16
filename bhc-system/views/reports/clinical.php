<?php
$filter = $filter ?? ReportPeriod::resolve([]);
$periodLabel = $periodLabel ?? ($filter['label'] ?? '');
$consultTotals = $consultTotals ?? ['total' => 0, 'unique_patients' => 0];
$medTotals = $medTotals ?? ['total_lines' => 0, 'prescribed' => 0, 'dispensed' => 0, 'receipts' => 0];
$topDiagnoses = $topDiagnoses ?? [];
$topMedicines = $topMedicines ?? [];
$consultations = $consultations ?? [];
$medicines = $medicines ?? [];
require __DIR__ . '/../partials/report_print_styles.php';
?>

<div class="report-print-header">
  <div style="font-weight: 700; font-size: 18px;">Barangay Health Center — Clinical Summary</div>
  <div class="muted"><?= h($periodLabel) ?></div>
</div>

<?php require __DIR__ . '/../partials/report_nav.php'; ?>

<div class="row row-between page-header no-print">
  <div class="row-body">
    <h1 style="margin-bottom: 6px;">Clinical summary</h1>
    <div class="muted">Consultations, diagnoses, and medicines for <?= h($periodLabel) ?>.</div>
  </div>
  <?php
    $formAction = '/reports/clinical';
    $exportPath = '/reports/clinical/export';
    require __DIR__ . '/../partials/report_period_filter.php';
  ?>
</div>

<div class="grid cols-3" style="margin-top: 14px;">
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Consultations</div>
    <div class="big"><?= (int) $consultTotals['total'] ?></div>
    <div class="muted" style="font-size: 13px; margin-top: 4px;"><?= (int) $consultTotals['unique_patients'] ?> unique patients</div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Medicine lines</div>
    <div class="big"><?= (int) $medTotals['total_lines'] ?></div>
    <div class="muted" style="font-size: 13px; margin-top: 4px;">
      <?= (int) $medTotals['prescribed'] ?> prescribed · <?= (int) $medTotals['dispensed'] ?> dispensed
    </div>
  </div>
  <div class="card" style="background: var(--surface2);">
    <div class="muted">Receipts issued</div>
    <div class="big"><?= (int) $medTotals['receipts'] ?></div>
  </div>
</div>

<div class="grid cols-2" style="margin-top: 14px; gap: 14px;">
  <div class="card">
    <h2 style="margin: 0 0 10px;">Top diagnoses</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Diagnosis</th><th>Cases</th></tr></thead>
        <tbody>
          <?php if (empty($topDiagnoses)): ?>
            <tr><td colspan="2" class="muted">No consultations in this period.</td></tr>
          <?php else: ?>
            <?php foreach ($topDiagnoses as $row): ?>
              <tr>
                <td><?= h($row['diagnosis']) ?></td>
                <td><strong><?= (int) $row['case_count'] ?></strong></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <h2 style="margin: 0 0 10px;">Top medicines</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Medicine</th><th>Qty</th><th>Lines</th></tr></thead>
        <tbody>
          <?php if (empty($topMedicines)): ?>
            <tr><td colspan="3" class="muted">No medicines recorded in this period.</td></tr>
          <?php else: ?>
            <?php foreach ($topMedicines as $row): ?>
              <tr>
                <td><?= h($row['medicine_name']) ?> <span class="muted">(<?= h($row['unit']) ?>)</span></td>
                <td><?= h((string) $row['total_quantity']) ?></td>
                <td><strong><?= (int) $row['line_count'] ?></strong></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 10px;">Consultation detail</h2>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Patient</th><th>Diagnosis</th><th>Doctor</th><th>Recorded by</th></tr>
      </thead>
      <tbody>
        <?php if (empty($consultations)): ?>
          <tr><td colspan="5" class="muted">No records.</td></tr>
        <?php else: ?>
          <?php foreach ($consultations as $c): ?>
            <tr>
              <td><?= h($c['consultation_date']) ?></td>
              <td><?= h($c['full_name']) ?> <span class="muted">(<?= h($c['bhc_id']) ?>)</span></td>
              <td><?= h($c['diagnosis']) ?></td>
              <td class="muted"><?= h(trim((string) ($c['doctor_display_name'] ?? $c['doctor_username'] ?? '—'))) ?></td>
              <td class="muted"><?= h($c['recorded_by_name'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 10px;">Medicine detail</h2>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Patient</th><th>Medicine</th><th>Qty</th><th>Status</th><th>Receipt</th></tr>
      </thead>
      <tbody>
        <?php if (empty($medicines)): ?>
          <tr><td colspan="6" class="muted">No records.</td></tr>
        <?php else: ?>
          <?php foreach ($medicines as $m): ?>
            <tr>
              <td class="muted"><?= h(substr((string) ($m['created_at'] ?? ''), 0, 10)) ?></td>
              <td><?= h($m['full_name']) ?></td>
              <td><?= h($m['medicine_name']) ?></td>
              <td><?= h((string) $m['quantity']) ?> <?= h($m['unit']) ?></td>
              <td><span class="pill <?= ($m['dispense_status'] ?? '') === 'dispensed' ? 'done' : 'waiting' ?>"><?= h(strtoupper((string) ($m['dispense_status'] ?? ''))) ?></span></td>
              <td><?= !empty($m['receipt_issued']) ? 'Yes' : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
