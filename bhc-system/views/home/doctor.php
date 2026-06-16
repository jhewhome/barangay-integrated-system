<?php
$serving = $serving ?? [];
$waiting = $waiting ?? [];
$completed = $completed ?? [];
$doctor = $doctor ?? [];
$canCallNext = (bool) ($canCallNext ?? false);
$consultationBusy = (bool) ($consultationBusy ?? false);
$stationServing = $stationServing ?? null;
$doctorLabel = User::doctorLabel($doctor);
$assignedActive = array_merge($serving, $waiting);
$assignedCount = count($assignedActive);
$queueSignature = (string) ($queueSignature ?? '');
$queueSnapshotUrl = app_url('/doctor/queue-snapshot');
$uQueueAlert = app_url('/assets/queue-alert.js');
?>

<style>
  .doctor-panel {
    margin-top: 14px;
    background: linear-gradient(135deg, rgba(47, 107, 255, 0.07) 0%, rgba(47, 107, 255, 0.02) 100%);
    border: 1px solid rgba(47, 107, 255, 0.18);
    box-shadow: 0 10px 28px rgba(47, 107, 255, 0.08);
  }
  .doctor-panel-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px 16px;
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px solid rgba(47, 107, 255, 0.12);
  }
  .doctor-panel-kicker {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 4px;
  }
  .doctor-panel-name {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    color: var(--text);
  }
  .doctor-panel-meta {
    margin-top: 6px;
    font-size: 13px;
    color: var(--muted);
  }
  .doctor-panel-count {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(47, 107, 255, 0.1);
    color: var(--text);
    font-size: 13px;
    font-weight: 600;
  }
  .doctor-assigned-empty {
    margin: 0;
    padding: 18px 16px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.65);
    text-align: center;
    color: var(--muted);
  }
  .doctor-assigned-table .data-table tbody tr.is-serving {
    background: rgba(34, 197, 94, 0.08);
  }
  .doctor-refresh-banner {
    display: none;
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(47, 107, 255, 0.1);
    border: 1px solid rgba(47, 107, 255, 0.22);
    color: var(--text);
    font-size: 14px;
  }
  .doctor-refresh-banner.is-visible {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px 14px;
  }
  .doctor-live-meta {
    margin-top: 6px;
    font-size: 13px;
    color: var(--muted);
  }
</style>

<div class="row row-between page-header">
  <div class="row-body">
    <h1 style="margin-bottom: 0;">My Patients</h1>
    <div class="doctor-live-meta">Auto-refreshes every 5 seconds when your queue changes.</div>
  </div>
  <div class="row-actions">
    <button id="btnDoctorEnableSound" class="btn" type="button" style="box-shadow:none;">Enable sound</button>
    <?php if ($canCallNext): ?>
      <form method="POST" action="<?= h(app_route('/doctor/call-next')) ?>">
        <button class="btn ok" type="submit" style="box-shadow: none;">Call next patient</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div id="doctorRefreshBanner" class="doctor-refresh-banner" role="status" aria-live="polite">
  <span id="doctorRefreshBannerText">Your patient queue was updated.</span>
  <button id="btnDoctorRefreshNow" class="btn ok" type="button" style="box-shadow:none;">Refresh now</button>
</div>

<?php if ($consultationBusy && $stationServing): ?>
  <div class="card" style="margin-top: 14px; background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.25);">
    <div class="muted">
      Consultation is currently serving <strong><?= h($stationServing['full_name'] ?? '') ?></strong>
      (<?= h($stationServing['ticket_no'] ?? '') ?>)
      <?php if (!empty($stationServing['doctor_display_name']) || !empty($stationServing['doctor_username'])): ?>
        for <?= h(trim((string) ($stationServing['doctor_display_name'] ?? $stationServing['doctor_username'] ?? ''))) ?>.
      <?php else: ?>.<?php endif; ?>
      Please wait until the room is free before calling your next patient.
    </div>
  </div>
<?php endif; ?>

<div class="card doctor-panel" id="doctorAssignedPanel">
  <div class="doctor-panel-head">
    <div>
      <div class="doctor-panel-kicker">Consulting physician</div>
      <div class="doctor-panel-name"><?= h($doctorLabel) ?></div>
      <div class="doctor-panel-meta">
        Patients assigned to you at Consultation today. Call the next patient, record the visit, then complete the consultation.
      </div>
    </div>
    <div class="doctor-panel-count">
      <span><?= (int) $assignedCount ?></span>
      <span><?= $assignedCount === 1 ? 'patient assigned' : 'patients assigned' ?></span>
    </div>
  </div>

  <h2 style="margin: 0 0 10px; font-size: 16px;">Assigned patients</h2>
  <?php if (empty($assignedActive)): ?>
    <p class="doctor-assigned-empty">No patients are assigned to you yet. Staff will route patients to Consultation and assign them to you.</p>
  <?php else: ?>
    <div class="table-wrap doctor-assigned-table">
      <table class="data-table">
        <thead>
          <tr>
            <th>Status</th>
            <th>Ticket</th>
            <th>Patient</th>
            <th>Reason</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignedActive as $t): ?>
            <?php $isServing = ($t['status'] ?? '') === 'serving'; ?>
            <tr class="<?= $isServing ? 'is-serving' : '' ?>">
              <td>
                <span class="pill <?= $isServing ? 'serving' : 'waiting' ?>"><?= $isServing ? 'Serving' : 'Waiting' ?></span>
              </td>
              <td><strong><?= h($t['ticket_no']) ?></strong></td>
              <td>
                <strong><?= h($t['full_name']) ?></strong>
                <div class="muted" style="font-size: 12px;"><?= h($t['bhc_id']) ?></div>
              </td>
              <td class="muted"><?= h($t['reason'] ?? '—') ?></td>
              <td>
                <div class="row-actions row-actions-tight">
                  <?php if ($isServing): ?>
                    <a class="btn ok" href="<?= h(app_url('/doctor/patients/' . (int) $t['patient_id'])) ?>" style="padding:6px 10px;font-size:12px;box-shadow:none;">Open record</a>
                    <form method="POST" action="<?= h(app_route('/doctor/tickets/' . (int) $t['id'] . '/complete')) ?>">
                      <button class="btn ok" type="submit" style="padding:6px 10px;font-size:12px;box-shadow:none;">Complete</button>
                    </form>
                    <form method="POST" action="<?= h(app_route('/doctor/tickets/' . (int) $t['id'] . '/skip')) ?>">
                      <button class="btn danger" type="submit" style="padding:6px 10px;font-size:12px;box-shadow:none;">Skip</button>
                    </form>
                  <?php else: ?>
                    <?php if (!$consultationBusy && empty($serving)): ?>
                      <form method="POST" action="<?= h(app_route('/doctor/tickets/' . (int) $t['id'] . '/call')) ?>">
                        <button class="btn ok" type="submit" style="padding:6px 10px;font-size:12px;box-shadow:none;">Call</button>
                      </form>
                    <?php endif; ?>
                    <a class="btn" href="<?= h(app_url('/doctor/patients/' . (int) $t['patient_id'])) ?>" style="padding:6px 10px;font-size:12px;box-shadow:none;">View</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 10px;">Completed today</h2>
  <?php if (empty($completed)): ?>
    <p class="muted" style="margin: 0;">No completed visits yet today.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Ticket</th><th>Patient</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($completed as $t): ?>
            <tr>
              <td><?= h($t['ticket_no']) ?></td>
              <td><?= h($t['full_name']) ?></td>
              <td><span class="pill <?= h($t['status']) ?>"><?= h(strtoupper((string) $t['status'])) ?></span></td>
              <td><a class="btn" href="<?= h(app_url('/doctor/patients/' . (int) $t['patient_id'])) ?>" style="padding:6px 10px;font-size:12px;box-shadow:none;">History</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top: 14px; background: var(--surface2);">
  <div class="muted">
    <strong>Workflow:</strong> Staff routes a patient to Consultation and assigns you. Use <strong>Call next patient</strong> (or <strong>Call</strong> on a waiting row), open the record, save diagnosis and prescription, then <strong>Complete consultation</strong>. The next assigned patient is called automatically when available.
  </div>
</div>

<script src="<?= h($uQueueAlert) ?>"></script>
<script>
(function () {
  var snapshotUrl = <?= json_encode($queueSnapshotUrl, JSON_UNESCAPED_SLASHES) ?>;
  var lastSignature = <?= json_encode($queueSignature, JSON_UNESCAPED_SLASHES) ?>;
  var lastWaitingCount = <?= (int) count($waiting) ?>;
  var pollMs = 5000;
  var banner = document.getElementById('doctorRefreshBanner');
  var bannerText = document.getElementById('doctorRefreshBannerText');
  var refreshBtn = document.getElementById('btnDoctorRefreshNow');
  var assignedPanel = document.getElementById('doctorAssignedPanel');
  var Q = window.BhcQueueAlert;
  var pendingReload = false;

  if (Q && Q.wireEnableButton) {
    Q.wireEnableButton('btnDoctorEnableSound');
  }

  function isEditingForm() {
    var active = document.activeElement;
    if (!active) return false;
    var tag = (active.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
      return true;
    }
    return false;
  }

  function describeUpdate(data) {
    var waiting = Array.isArray(data.waiting) ? data.waiting : [];
    if (waiting.length === 1) {
      return 'New patient assigned: ' + waiting[0].full_name + ' (' + waiting[0].ticket_no + ').';
    }
    if ((data.waiting_count || 0) > 0) {
      return 'Your queue was updated — ' + data.waiting_count + ' patient(s) waiting.';
    }
    return 'Your patient queue was updated.';
  }

  function notifyAndReload(data, playSound) {
    if (playSound && Q && Q.playChime) {
      Q.playChime();
    }
    if (Q && Q.flashElement && assignedPanel) {
      Q.flashElement(assignedPanel, 1600);
    }
    window.location.reload();
  }

  function showPendingBanner(data) {
    pendingReload = true;
    if (bannerText) {
      bannerText.textContent = describeUpdate(data) + ' Refresh when ready.';
    }
    if (banner) {
      banner.classList.add('is-visible');
    }
    if (Q && Q.playChime) {
      Q.playChime();
    }
    if (Q && Q.flashElement && assignedPanel) {
      Q.flashElement(assignedPanel, 1600);
    }
  }

  function handleSnapshot(data) {
    if (!data || typeof data.signature !== 'string') return;
    if (data.signature === lastSignature) return;

    var waitingCount = data.waiting_count || 0;
    var hasNewWaiting = waitingCount > lastWaitingCount;
    lastSignature = data.signature;
    lastWaitingCount = waitingCount;

    if (isEditingForm()) {
      showPendingBanner(data);
      return;
    }

    notifyAndReload(data, hasNewWaiting);
  }

  function pollQueue() {
    if (pendingReload && !isEditingForm()) {
      window.location.reload();
      return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', snapshotUrl, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status !== 200) return;
      try {
        handleSnapshot(JSON.parse(xhr.responseText));
      } catch (e) {}
    };
    xhr.send();
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      window.location.reload();
    });
  }

  setInterval(pollQueue, pollMs);
})();
</script>
