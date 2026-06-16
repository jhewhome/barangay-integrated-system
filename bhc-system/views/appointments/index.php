<?php
$appointments = $appointments ?? [];
$from = (string) ($from ?? date('Y-m-d'));
$to = (string) ($to ?? date('Y-m-d', strtotime('+30 days')));

function appt_status_pill(string $status): string
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

<div class="card page-header-card" style="padding: 16px 18px;">
  <div class="row row-between page-header">
    <div class="row-body">
      <h1 style="margin-bottom: 4px;">Upcoming Appointments</h1>
      <div class="muted">Scheduled follow-up visits across all patients. Click a patient name to open their record.</div>
    </div>
  </div>
</div>

<div class="card list-page-card">
  <form method="GET" action="<?= h(app_url('/appointments')) ?>" class="appointments-filter list-search-bar grid cols-3">
    <div>
      <label>From</label>
      <input type="date" name="from" value="<?= h($from) ?>" />
    </div>
    <div>
      <label>To</label>
      <input type="date" name="to" value="<?= h($to) ?>" />
    </div>
    <div class="appointments-filter-submit">
      <button class="btn ok" type="submit" style="box-shadow: none;">Filter</button>
    </div>
  </form>

  <div class="table-wrap list-table-wrap appointments-table-wrap">
    <table class="data-table list-table appointments-table">
      <thead>
        <tr>
          <th class="col-date">Date</th>
          <th class="col-time">Time</th>
          <th class="col-patient">Patient</th>
          <th class="col-id">BHC ID</th>
          <th class="col-contact">Contact</th>
          <th class="col-purpose">Purpose</th>
          <th class="col-station">Station</th>
          <th class="col-actions">Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr class="appointments-empty-row list-table-empty-row">
            <td colspan="8" class="muted">No scheduled appointments in this date range.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($appointments as $a): ?>
            <?php $patientId = (int) $a['patient_id']; ?>
            <tr>
              <td class="col-date" data-label="Date"><strong><?= h($a['appointment_date']) ?></strong></td>
              <td class="col-time muted" data-label="Time"><?= !empty($a['appointment_time']) ? h(substr((string) $a['appointment_time'], 0, 5)) : '—' ?></td>
              <td class="col-patient" data-label="Patient">
                <a href="<?= h(app_url('/patients/' . (int) $patientId . '/history')) ?>"><?= h($a['full_name']) ?></a>
              </td>
              <td class="col-id" data-label="BHC ID"><?= h($a['bhc_id']) ?></td>
              <td class="col-contact muted" data-label="Contact"><?= h($a['contact_number'] ?? '—') ?></td>
              <td class="col-purpose" data-label="Purpose"><?= h($a['purpose'] ?? '—') ?></td>
              <td class="col-station muted" data-label="Station"><?= h($a['station_name'] ?? '—') ?></td>
              <td class="col-actions" data-label="Manage">
                <div class="appt-row-actions">
                  <?php $routeAppointmentId = (int) $a['id']; require __DIR__ . '/../partials/patient_route_action.php'; ?>
                  <details class="appt-menu">
                    <summary class="appt-action appt-action-menu">Status</summary>
                    <div class="appt-menu-panel">
                      <form method="POST" action="<?= h(app_route('/appointments/' . (int) $a['id'] . '/status')) ?>">
                        <input type="hidden" name="return_to" value="appointments" />
                        <input type="hidden" name="status" value="completed" />
                        <button type="submit" class="appt-menu-item appt-menu-done">Mark completed</button>
                      </form>
                      <form method="POST" action="<?= h(app_route('/appointments/' . (int) $a['id'] . '/status')) ?>">
                        <input type="hidden" name="return_to" value="appointments" />
                        <input type="hidden" name="status" value="no_show" />
                        <button type="submit" class="appt-menu-item">No-show</button>
                      </form>
                      <form method="POST" action="<?= h(app_route('/appointments/' . (int) $a['id'] . '/status')) ?>">
                        <input type="hidden" name="return_to" value="appointments" />
                        <input type="hidden" name="status" value="cancelled" />
                        <button type="submit" class="appt-menu-item appt-menu-cancel">Cancel appointment</button>
                      </form>
                    </div>
                  </details>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .appointments-filter {
    align-items: end;
  }
  .appointments-filter-submit {
    display: flex;
    align-items: flex-end;
  }
  .appointments-table .col-date,
  .appointments-table .col-time {
    white-space: nowrap;
    width: 1%;
  }
  .appointments-table .col-contact {
    max-width: 130px;
    word-break: break-word;
  }
  .appointments-table .col-purpose {
    min-width: 140px;
    word-break: break-word;
  }
  .appointments-table .col-station {
    white-space: nowrap;
  }
  .appointments-table .col-patient {
    min-width: 140px;
  }
  .appointments-table .col-patient a {
    font-weight: 600;
  }
  @media (max-width: 900px) {
    .appointments-filter {
      grid-template-columns: 1fr;
    }
    .appointments-filter-submit .btn {
      width: 100%;
    }
    .appointments-table {
      min-width: 880px;
    }
  }
</style>

<script>
(function () {
  var menus = document.querySelectorAll('.appt-menu');
  menus.forEach(function (menu) {
    menu.addEventListener('toggle', function () {
      if (!menu.open) return;
      menus.forEach(function (other) {
        if (other !== menu) other.open = false;
      });
    });
  });
})();
</script>
