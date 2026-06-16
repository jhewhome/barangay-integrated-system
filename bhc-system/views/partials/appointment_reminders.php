<?php
$appointmentsToday = $appointmentsToday ?? [];
$todayLabel = date('F j, Y');
?>

<div class="card" style="margin-top: 14px; border-color: rgba(47, 107, 255, 0.2);">
  <div class="row row-between card-header" style="margin-bottom: 10px;">
    <h2 style="margin:0;">Appointment reminders</h2>
    <span class="pill waiting"><?= h($todayLabel) ?></span>
  </div>
  <div class="muted" style="margin-bottom: 12px; font-size: 14px;">
    Patients with a <strong>scheduled visit today</strong>. Route them at Patient Routing when they arrive.
  </div>

  <?php if (empty($appointmentsToday)): ?>
    <p class="muted" style="margin: 0;">No scheduled appointments for today.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Patient</th>
            <th>BHC ID</th>
            <th>Purpose</th>
            <th>Station</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($appointmentsToday as $a): ?>
            <tr>
              <td>
                <?php if (!empty($a['appointment_time'])): ?>
                  <strong><?= h(substr((string) $a['appointment_time'], 0, 5)) ?></strong>
                <?php else: ?>
                  <span class="muted">Any time</span>
                <?php endif; ?>
              </td>
              <td><?= h($a['full_name']) ?></td>
              <td class="muted"><?= h($a['bhc_id']) ?></td>
              <td><?= h($a['purpose'] ?? '—') ?></td>
              <td class="muted"><?= h($a['station_name'] ?? '—') ?></td>
              <td>
                <a class="btn ok" href="<?= h(app_url('/queue/1?patient_id=' . (int) $a['patient_id'])) ?>" style="padding: 6px 10px; font-size: 12px; box-shadow: none;">Route</a>
                <a class="btn" href="<?= h(app_url('/patients/' . (int) $a['patient_id'] . '/history')) ?>" style="padding: 6px 10px; font-size: 12px; box-shadow: none;">History</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="row-actions row-actions-tight" style="margin-top: 12px;">
      <a class="btn" href="<?= h(app_url('/appointments?from=' . date('Y-m-d') . '&to=' . date('Y-m-d'))) ?>" style="box-shadow: none;">View all in Appointments</a>
    </div>
  <?php endif; ?>
</div>
