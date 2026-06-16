<?php
$stationId = (int) $station['id'];
$queuePath = static fn (string $suffix = '') => app_route('/queue/' . $stationId . $suffix);
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1><?= h($station['name']) ?> — Station Queue</h1>
    <div class="muted">Daily queue (resets by date). Ticket format: <strong><?= h($station['name']) ?></strong> uses its station code (e.g., TR-001).</div>
  </div>
  <div class="row-actions">
    <a class="btn has-tip" data-tip="Go back to the station list to select another queue." href="<?= h(app_url('/stations')) ?>">All Stations</a>
    <a class="btn has-tip" data-tip="Open a TV/display view for patients (Now Serving + Next)."
       href="<?= h(app_url('/display/' . $stationId)) ?>" target="_blank" rel="noopener">Patient display</a>
  </div>
</div>

<?php if (!empty($enqueuedTicket)): ?>
  <div class="card" style="margin-top: 14px; background: var(--surface2);">
    <div class="row" style="justify-content: space-between;">
      <div>
        <div class="pill waiting">Added to queue</div>
        <div style="margin-top: 8px;"><strong><?= h($enqueuedTicket['full_name']) ?></strong> <span class="muted">(<?= h($enqueuedTicket['bhc_id']) ?>)</span></div>
        <div class="muted">Ticket: <strong><?= h($enqueuedTicket['ticket_no']) ?></strong> • Status: <strong><?= strtoupper(h($enqueuedTicket['status'])) ?></strong></div>
      </div>
      <div class="row">
        <a class="btn" href="<?= h(app_url('/ticket/' . (int) $enqueuedTicket['id'])) ?>">Open ticket screen</a>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
$routingOnly = ((int) $station['id'] === 1);
$isConsultation = ((int) $station['id'] === 3);
$isTriage = ((int) $station['id'] === 2);
$isPharmacy = ((int) $station['id'] === 4);
$consultationRecord = $consultationRecord ?? null;
$ticketMedicines = $ticketMedicines ?? [];
$triageRecord = $triageRecord ?? null;
$doctors = $doctors ?? [];
$prefillPatientId = (int) ($prefillPatientId ?? 0);
$prefillAppointment = $prefillAppointment ?? null;
$prefillAppointmentId = (int) ($prefillAppointmentId ?? 0);
$prefillReason = trim((string) ($prefillAppointment['purpose'] ?? ''));
$prefillStationId = (int) ($prefillAppointment['station_id'] ?? 0);
$servingTodayAppointment = $servingTodayAppointment ?? null;
$todayAppointmentPatientIds = $todayAppointmentPatientIds ?? [];
if ($routingOnly || $isConsultation) {
    require __DIR__ . '/../partials/followup_appointment_styles.php';
}
?>

<div class="grid cols-2 queue-grid" style="margin-top: 14px;">
  <?php if (!$routingOnly): ?>
    <div class="card queue-col-left">
      <h2>Now serving</h2>
      <?php if ($nowServing): ?>
        <div class="big"><?= h($nowServing['ticket_no']) ?></div>
        <div><strong><?= h($nowServing['full_name']) ?></strong> <span class="muted">(<?= h($nowServing['bhc_id']) ?>)</span></div>
        <div class="muted">Called at: <?= h($nowServing['called_at']) ?></div>

        <?php if ($isTriage): ?>
          <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(15,23,42,.08);">
            <h3 style="margin: 0 0 8px; font-size: 15px;">Triage / vitals</h3>
            <form method="POST" action="<?= h($queuePath('/triage/' . (int) $nowServing['id'])) ?>">
              <div class="grid cols-2" style="gap: 10px;">
                <div>
                  <label>Blood pressure (systolic)</label>
                  <input type="number" name="blood_pressure_systolic" min="50" max="250" placeholder="e.g. 120" value="<?= h($triageRecord['blood_pressure_systolic'] ?? '') ?>" />
                </div>
                <div>
                  <label>Blood pressure (diastolic)</label>
                  <input type="number" name="blood_pressure_diastolic" min="30" max="150" placeholder="e.g. 80" value="<?= h($triageRecord['blood_pressure_diastolic'] ?? '') ?>" />
                </div>
                <div>
                  <label>Temperature (°C)</label>
                  <input type="number" name="temperature" min="34" max="43" step="0.1" placeholder="e.g. 36.5" value="<?= h($triageRecord['temperature'] ?? '') ?>" />
                </div>
                <div>
                  <label>Pulse (bpm)</label>
                  <input type="number" name="pulse_rate" min="30" max="200" placeholder="e.g. 72" value="<?= h($triageRecord['pulse_rate'] ?? '') ?>" />
                </div>
                <div>
                  <label>Weight (kg)</label>
                  <input type="number" name="weight_kg" min="1" max="300" step="0.1" placeholder="e.g. 65" value="<?= h($triageRecord['weight_kg'] ?? '') ?>" />
                </div>
                <div>
                  <label>Height (cm)</label>
                  <input type="number" name="height_cm" min="30" max="250" step="0.1" placeholder="e.g. 165" value="<?= h($triageRecord['height_cm'] ?? '') ?>" />
                </div>
                <div class="span-2">
                  <label>Notes (optional)</label>
                  <textarea name="triage_notes" rows="2" placeholder="Chief complaint, observations…"><?= h($triageRecord['notes'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="form-submit-actions">
                <button class="btn ok" type="submit" style="box-shadow: none;">Save triage record</button>
              </div>
            </form>
          </div>
        <?php endif; ?>

        <?php if ($isConsultation): ?>
          <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(15,23,42,.08);">
            <h3 style="margin: 0 0 8px; font-size: 15px;">Assign doctor</h3>
            <form method="POST" action="<?= h($queuePath('/assign-doctor/' . (int) $nowServing['id'])) ?>">
              <label for="assignDoctorSelect">Doctor</label>
              <select id="assignDoctorSelect" name="doctor_id">
                <option value="">— No doctor assigned —</option>
                <?php foreach ($doctors as $doc): ?>
                  <option value="<?= (int) $doc['id'] ?>" <?= (int) ($nowServing['assigned_doctor_id'] ?? 0) === (int) $doc['id'] ? 'selected' : '' ?>>
                    <?= h(User::doctorLabel($doc)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-submit-actions">
                <button class="btn" type="submit" style="box-shadow: none;">Save assignment</button>
              </div>
            </form>
            <?php if (!empty($nowServing['assigned_doctor_id'])): ?>
              <div class="muted" style="margin-top: 8px;">Assigned: <strong><?= h(User::doctorLabel(['display_name' => $nowServing['doctor_display_name'] ?? '', 'username' => $nowServing['doctor_username'] ?? ''])) ?></strong></div>
            <?php endif; ?>
          </div>
          <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(15,23,42,.08);">
            <h3 style="margin: 0 0 8px; font-size: 15px;">Consultation record</h3>
            <?php require __DIR__ . '/../partials/consultation_today_notice.php'; ?>
            <?php if ($servingTodayAppointment): ?>
              <?php
                $appointment = $servingTodayAppointment;
                $patient = $nowServing ?? null;
                $showPatient = false;
                $showActions = false;
                $footerNote = 'Saving this consultation will link and complete this appointment.';
                require __DIR__ . '/../partials/followup_appointment_today.php';
              ?>
            <?php endif; ?>
            <form method="POST" action="<?= h($queuePath('/consultation/' . (int) $nowServing['id'])) ?>">
              <?php if ($servingTodayAppointment): ?>
                <input type="hidden" name="appointment_id" value="<?= (int) $servingTodayAppointment['id'] ?>" />
              <?php endif; ?>
              <label>Diagnosis</label>
              <textarea name="diagnosis" rows="2" required placeholder="e.g., Acute upper respiratory infection"><?= h($consultationRecord['diagnosis'] ?? '') ?></textarea>
              <label style="margin-top: 8px;">Clinical notes (optional)</label>
              <textarea name="clinical_notes" rows="2" placeholder="Vitals, findings, advice…"><?= h($consultationRecord['clinical_notes'] ?? '') ?></textarea>
              <div class="muted" style="margin-top: 10px; margin-bottom: 6px; font-size: 13px;">Pharmacy may dispense prescribed medicines later.</div>
              <?php $showReceipt = false; $initialRows = 1; require __DIR__ . '/../partials/medicine_lines.php'; ?>
              <div class="form-submit-actions">
                <button class="btn ok" type="submit" style="box-shadow: none;"><?= !empty($todayConsultation) ? 'Update consultation record' : 'Save consultation record' ?></button>
              </div>
            </form>
          </div>
        <?php endif; ?>

        <?php if ($isPharmacy): ?>
          <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(15,23,42,.08);">
            <h3 style="margin: 0 0 8px; font-size: 15px;">Medicine dispensing</h3>
            <?php if (!empty($ticketMedicines)): ?>
              <div class="muted" style="margin-bottom: 8px;">Already recorded for this ticket: <?= count($ticketMedicines) ?> item(s).</div>
            <?php endif; ?>
            <form method="POST" action="<?= h($queuePath('/dispense/' . (int) $nowServing['id'])) ?>">
              <div class="muted" style="margin-bottom: 6px; font-size: 13px;">Check <strong>Issued</strong> when the patient received a printed receipt.</div>
              <?php $showReceipt = true; $initialRows = 2; require __DIR__ . '/../partials/medicine_lines.php'; ?>
              <div class="form-submit-actions">
                <button class="btn ok" type="submit" style="box-shadow: none;">Record medicines dispensed</button>
              </div>
            </form>
          </div>
        <?php endif; ?>

        <div class="row-actions row-actions-tight" style="margin-top: 10px;">
          <form method="POST" action="<?= h($queuePath('/complete/' . (int) $nowServing['id'])) ?>">
            <button class="btn ok has-tip" data-tip="Mark the current ticket as DONE for this station." type="submit">Complete</button>
          </form>
          <form method="POST" action="<?= h($queuePath('/skip/' . (int) $nowServing['id'])) ?>">
            <button class="btn danger has-tip" data-tip="Skip if patient is absent. Ticket will be marked SKIPPED." type="submit">Skip</button>
          </form>
        </div>
      <?php else: ?>
        <div class="muted">No active ticket.</div>
        <form method="POST" action="<?= h($queuePath('/call-next')) ?>" style="margin-top: 10px;">
          <button class="btn has-tip" data-tip="Call the oldest WAITING ticket and set it to NOW SERVING." type="submit">Call next</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($routingOnly): ?>
    <div class="card span-2">
      <h2>Patient Routing</h2>
      <div class="muted">Search the patient, record the reason for visit, then assign the service station.</div>

      <form class="patient-routing-form" method="POST" action="<?= h($queuePath('/enqueue')) ?>" style="margin-top: 10px;">
        <label>Search patient</label>
        <div class="auto-wrap">
          <input id="patientSearch" placeholder="Type name or BHC ID (e.g., Jerome / BHC-000001)" autocomplete="off" />
          <div id="patientAutoBackdrop" class="auto-backdrop"></div>
          <div id="patientAutoResults" class="auto-results" style="display:none;"></div>
        </div>
        <select id="patientSelect" name="patient_id" required style="display:none;" aria-hidden="true" tabindex="-1">
          <option value="" selected disabled>Select patient…</option>
          <?php
            $db = (new Database())->getConnection();
            $allPatients = Patient::paginate($db, 200, 0);
          ?>
          <?php foreach ($allPatients as $p): ?>
            <?php $label = $p['bhc_id'] . ' — ' . $p['full_name']; ?>
            <option value="<?= (int) $p['id'] ?>" data-label="<?= h($label) ?>" <?= $prefillPatientId === (int) $p['id'] ? 'selected' : '' ?>>
              <?= h(truncate_label($label)) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div id="appointmentTodayBanner" class="followup-appt-card followup-appt-routing" style="display: none; margin-top: 12px;">
          <div id="appointmentTodayBody"></div>
        </div>

        <div id="activeQueueWarning" class="notice" style="display: none; margin-top: 12px;" role="status"></div>
        <?php if (!empty($prefillActiveTickets)): ?>
          <div id="activeQueueWarningInitial" class="notice" style="margin-top: 12px;" role="status">
            This patient already has an active queue ticket:
            <?php foreach ($prefillActiveTickets as $t): ?>
              <strong><?= h($t['ticket_no']) ?></strong> at <?= h($t['station_name']) ?> (<?= h(strtoupper((string) $t['status'])) ?>).
            <?php endforeach; ?>
            Complete or skip it before creating another ticket.
            <?php $firstActive = $prefillActiveTickets[0]; ?>
            <a href="<?= h(app_url('/queue/' . (int) $firstActive['station_id'])) ?>" style="margin-left: 6px; font-weight: 700;">Open station queue</a>
          </div>
        <?php elseif (!empty($prefillConsultationTicket)): ?>
          <div id="activeQueueWarningInitial" class="notice" style="margin-top: 12px;" role="status">
            This patient already has a consultation ticket today:
            <strong><?= h($prefillConsultationTicket['ticket_no']) ?></strong>
            (<?= h(strtoupper((string) $prefillConsultationTicket['status'])) ?>).
            Only one consultation ticket per patient per day is allowed.
            <a href="<?= h(app_url('/queue/' . (int) QueueTicket::consultationStationId())) ?>" style="margin-left: 6px; font-weight: 700;">Open Consultation queue</a>
          </div>
        <?php endif; ?>

        <div style="margin-top: 10px;">
          <label>What are they here for? (Reason)</label>
          <div class="auto-wrap">
            <input id="reasonInput" name="reason" value="<?= h($prefillReason) ?>" placeholder="e.g., fever, prenatal checkup, immunization" autocomplete="off" />
            <div id="reasonBackdrop" class="auto-backdrop"></div>
            <div id="reasonResults" class="auto-results" style="display:none;"></div>
          </div>
          <div class="muted" style="margin-top: 6px;">Tip: type freely, or click a suggestion to autofill.</div>
        </div>
        <div style="margin-top: 10px;">
          <label>Assign / route to station</label>
          <select id="targetStationSelect" name="target_station_id" required>
            <option value="" disabled <?= $prefillStationId <= 0 ? 'selected' : '' ?>>Select station…</option>
            <?php foreach ($stations as $s): ?>
              <?php if ((int) $s['id'] === 1) continue; ?>
              <option value="<?= (int) $s['id'] ?>" <?= $prefillStationId === (int) $s['id'] ? 'selected' : '' ?>><?= h($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row form-actions" style="margin-top: 10px;">
          <button id="createTicketBtn" class="btn ok has-tip" data-tip="Creates a queue ticket for the selected station. The patient ticket screen opens after routing." type="submit" <?= (!empty($prefillActiveTickets) || !empty($prefillConsultationTicket)) ? 'disabled' : '' ?>>Create ticket</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <div class="card queue-col-right">
      <div class="row" style="justify-content: space-between; align-items: flex-start;">
        <div>
          <h2 style="margin-bottom: 6px;">Incoming Patients</h2>
          <div class="muted">Patients routed from Patient Routing, waiting to be called at this station.</div>
        </div>
        <form method="POST" action="<?= h($queuePath('/call-next')) ?>">
          <button class="btn has-tip" data-tip="Call the next waiting ticket for this station." type="submit" <?= $nowServing ? 'disabled' : '' ?>>Call next</button>
        </form>
      </div>
      <?php
        $db = (new Database())->getConnection();
        $incoming = QueueTicket::waitingList($db, (int) $station['id'], 50);
      ?>
      <div class="card" style="margin-top: 12px; background: var(--surface2);">
        <?php if (empty($incoming)): ?>
          <div class="muted">No incoming waiting patients yet.</div>
        <?php else: ?>
          <div class="row" style="justify-content: space-between; margin-bottom: 10px; gap: 10px;">
            <div class="muted" style="flex: 1 1 140px; min-width: 0;">Showing up to <strong><?= count($incoming) ?></strong> waiting patient(s).</div>
            <div style="flex: 1 1 180px; min-width: 0;">
              <input id="incomingFilter" placeholder="Filter (ticket / name / reason)..." autocomplete="off" style="width: 100%;" />
            </div>
          </div>
          <div class="table-wrap" style="max-height: 360px; overflow: auto; border-radius: 12px; border: 1px solid rgba(15, 23, 42, 0.08); background: var(--surface);">
          <table class="data-table">
            <thead>
              <tr>
                <th>Ticket</th>
                <th>Patient</th>
                <th>Reason</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="incomingTbody">
              <?php foreach ($incoming as $w): ?>
                <tr>
                  <td><strong><?= h($w['ticket_no']) ?></strong></td>
                  <td><?= h($w['full_name']) ?> <span class="muted">(<?= h($w['bhc_id']) ?>)</span></td>
                  <td class="muted"><?= h((string) ($w['reason'] ?? '')) ?></td>
                  <td>
                    <form method="POST" action="<?= h($queuePath('/call/' . (int) $w['id'])) ?>">
                      <button class="btn" type="submit" <?= $nowServing ? 'disabled' : '' ?>>Call</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
          <script>
            (function () {
              var input = document.getElementById('incomingFilter');
              var tbody = document.getElementById('incomingTbody');
              if (!input || !tbody) return;
              function norm(s) {
                s = (s || '').toLowerCase();
                s = s.replace(/\s+/g, ' ');
                return s.replace(/^\s+|\s+$/g, '');
              }
              function apply() {
                var q = norm(input.value);
                var rows = tbody.getElementsByTagName('tr');
                for (var i = 0; i < rows.length; i++) {
                  var text = norm(rows[i].innerText || rows[i].textContent || '');
                  rows[i].style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
                }
              }
              if (input.addEventListener) input.addEventListener('input', apply);
              else input.onkeyup = apply;
            })();
          </script>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  (function () {
    var appBase = <?= json_encode(app_base_path()) ?>;
    function appPath(path) {
      return appBase + (path.charAt(0) === '/' ? path : '/' + path);
    }

    var input = document.getElementById('patientSearch');
    var select = document.getElementById('patientSelect');
    var results = document.getElementById('patientAutoResults');
    var backdrop = document.getElementById('patientAutoBackdrop');
    if (!input || !select) return;

    var placeholder = null;
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].value === '') { placeholder = select.options[i]; break; }
    }

    var original = [];
    for (var j = 0; j < select.options.length; j++) {
      var o = select.options[j];
      if (!o.value) continue;
      original.push({
        value: o.value,
        text: o.text || o.textContent || '',
        label: o.getAttribute('data-label') || (o.text || o.textContent || '')
      });
    }

    function normalize(s) {
      s = (s || '').toLowerCase();
      s = s.replace(/\s+/g, ' ');
      return s.replace(/^\s+|\s+$/g, '');
    }

    function clearSelect() {
      while (select.firstChild) select.removeChild(select.firstChild);
      if (placeholder) select.appendChild(placeholder);
    }

    var apptBanner = document.getElementById('appointmentTodayBanner');
    var apptBody = document.getElementById('appointmentTodayBody');
    var stationSelect = document.getElementById('targetStationSelect');
    var todayApptIds = <?= json_encode(array_values($todayAppointmentPatientIds)) ?>;

    function hideAppointmentBanner() {
      if (apptBanner) apptBanner.style.display = 'none';
      if (apptBody) apptBody.innerHTML = '';
    }

    function stationIcon(name) {
      if (name === 'Registration') return '🧾';
      if (name === 'Triage / Vitals') return '🩺';
      if (name === 'Consultation') return '👩‍⚕️';
      if (name === 'Pharmacy') return '💊';
      return '🏥';
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function showAppointmentBanner(appt) {
      if (!apptBanner || !apptBody || !appt) {
        hideAppointmentBanner();
        return;
      }
      var time = appt.appointment_time ? String(appt.appointment_time).substring(0, 5) : 'Any time';
      var purpose = appt.purpose ? appt.purpose : 'Scheduled follow-up';
      var station = appt.station_name ? appt.station_name : '';
      var notes = appt.notes ? String(appt.notes) : '';
      var patientName = appt.full_name ? String(appt.full_name) : '';
      var bhcId = appt.bhc_id ? String(appt.bhc_id) : '';
      var contact = appt.contact_number ? String(appt.contact_number) : '';
      var apptId = appt.id ? String(appt.id) : '';
      var apptDate = appt.appointment_date ? String(appt.appointment_date).substring(0, 10) : '';
      var today = new Date();
      var todayIso = today.toISOString().slice(0, 10);
      var isToday = !apptDate || apptDate === todayIso;
      var dateLabel = isToday
        ? today.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
        : apptDate;

      var patientBlock = '';
      if (patientName || bhcId) {
        patientBlock =
          '<div class="followup-appt-patient">' +
            '<div class="followup-appt-label">Patient</div>' +
            '<div class="followup-appt-value">' +
              (patientName ? '<strong>' + escapeHtml(patientName) + '</strong>' : '') +
              (bhcId ? '<span class="muted">' + (patientName ? ' &middot; ' : '') + escapeHtml(bhcId) + '</span>' : '') +
              (contact ? '<div class="muted" style="font-size:12px;margin-top:4px;">Contact: ' + escapeHtml(contact) + '</div>' : '') +
            '</div>' +
          '</div>';
      }

      apptBody.innerHTML =
        '<div class="followup-appt-header">' +
          '<div class="followup-appt-heading">' +
            '<div class="followup-appt-icon" aria-hidden="true">📅</div>' +
            '<div>' +
              '<div class="followup-appt-title">' + (isToday ? 'Follow-up appointment today' : 'Scheduled appointment') + '</div>' +
              '<div class="followup-appt-subtitle">' + escapeHtml(dateLabel) + '</div>' +
            '</div>' +
          '</div>' +
          '<div class="followup-appt-badges">' +
            '<span class="pill waiting">Scheduled</span>' +
            (apptId ? '<span class="pill" style="font-size:11px;">#' + escapeHtml(apptId) + '</span>' : '') +
          '</div>' +
        '</div>' +
        patientBlock +
        '<div class="followup-appt-grid">' +
          '<div class="followup-appt-item">' +
            '<div class="followup-appt-label">Time</div>' +
            '<div class="followup-appt-value"><strong>' + escapeHtml(time) + '</strong>' +
              (time === 'Any time' ? '<div class="muted" style="font-size:12px;margin-top:2px;">Walk-in window — no fixed slot</div>' : '') +
            '</div>' +
          '</div>' +
          '<div class="followup-appt-item">' +
            '<div class="followup-appt-label">Purpose</div>' +
            '<div class="followup-appt-value">' + escapeHtml(purpose) + '</div>' +
          '</div>' +
          '<div class="followup-appt-item">' +
            '<div class="followup-appt-label">Preferred station</div>' +
            '<div class="followup-appt-value">' +
              (station
                ? '<span aria-hidden="true">' + stationIcon(station) + '</span> ' + escapeHtml(station)
                : '<span class="muted">Not specified</span>') +
            '</div>' +
          '</div>' +
          (notes
            ? '<div class="followup-appt-item span-2">' +
                '<div class="followup-appt-label">Appointment notes</div>' +
                '<div class="followup-appt-value">' + escapeHtml(notes).replace(/\n/g, '<br>') + '</div>' +
              '</div>'
            : '') +
        '</div>' +
        '<div class="followup-appt-footer">' +
          '<span aria-hidden="true">ℹ️</span> Route this patient to the preferred station when creating their ticket.' +
        '</div>';
      apptBanner.style.display = 'block';

      if (stationSelect && appt.station_id) {
        var stationValue = String(appt.station_id);
        var hasStationOption = false;
        for (var si = 0; si < stationSelect.options.length; si++) {
          if (String(stationSelect.options[si].value) === stationValue) {
            hasStationOption = true;
            break;
          }
        }
        if (hasStationOption) {
          stationSelect.value = stationValue;
        }
      }
      var reasonInput = document.getElementById('reasonInput');
      if (reasonInput && appt.purpose) {
        reasonInput.value = appt.purpose;
      }
      if (typeof refreshRoutingGuard === 'function') {
        refreshRoutingGuard();
      }
    }

    function loadAppointmentToday(patientId) {
      if (!patientId) {
        hideAppointmentBanner();
        return;
      }
      var xhr = new XMLHttpRequest();
      xhr.open('GET', appPath('/patients/' + encodeURIComponent(patientId) + '/appointment-today'), true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhr.status !== 200) {
          hideAppointmentBanner();
          return;
        }
        try {
          var data = JSON.parse(xhr.responseText);
          if (data) showAppointmentBanner(data);
          else hideAppointmentBanner();
        } catch (e) {
          hideAppointmentBanner();
        }
      };
      xhr.send();
    }

    var queueWarning = document.getElementById('activeQueueWarning');
    var queueWarningInitial = document.getElementById('activeQueueWarningInitial');
    var createTicketBtn = document.getElementById('createTicketBtn');
    var consultStationId = <?= (int) QueueTicket::consultationStationId() ?>;
    var lastQueueStatus = null;

    function setCreateTicketEnabled(enabled) {
      if (!createTicketBtn) return;
      createTicketBtn.disabled = !enabled;
    }

    function hideQueueWarning() {
      lastQueueStatus = null;
      if (queueWarning) {
        queueWarning.style.display = 'none';
        queueWarning.innerHTML = '';
      }
      if (queueWarningInitial) queueWarningInitial.style.display = 'none';
      setCreateTicketEnabled(true);
    }

    function refreshRoutingGuard() {
      if (!lastQueueStatus) {
        setCreateTicketEnabled(true);
        return;
      }

      var tickets = lastQueueStatus.tickets || [];
      var consultationTicketToday = lastQueueStatus.consultation_ticket_today || null;
      var targetStation = stationSelect ? parseInt(stationSelect.value || '0', 10) : 0;
      var block = tickets.length > 0;

      if (!block && targetStation === consultStationId && consultationTicketToday) {
        block = true;
      }

      if (!block) {
        if (queueWarning) {
          queueWarning.style.display = 'none';
          queueWarning.innerHTML = '';
        }
        setCreateTicketEnabled(true);
        return;
      }

      if (queueWarningInitial) queueWarningInitial.style.display = 'none';
      if (!queueWarning) {
        setCreateTicketEnabled(false);
        return;
      }

      var html = '';
      if (tickets.length > 0) {
        var parts = [];
        for (var i = 0; i < tickets.length; i++) {
          var t = tickets[i];
          parts.push(
            '<strong>' + escapeHtml(t.ticket_no) + '</strong> at ' + escapeHtml(t.station_name) +
            ' (' + escapeHtml(String(t.status || '').toUpperCase()) + ')'
          );
        }
        var first = tickets[0];
        html =
          'This patient already has an active queue ticket: ' + parts.join('; ') +
          '. Complete or skip it before creating another ticket. ' +
          '<a href="' + appPath('/queue/' + encodeURIComponent(first.station_id)) + '" style="margin-left:6px;font-weight:700;">Open station queue</a>';
      } else if (consultationTicketToday) {
        html =
          'This patient already has a consultation ticket today: ' +
          '<strong>' + escapeHtml(consultationTicketToday.ticket_no) + '</strong> (' +
          escapeHtml(String(consultationTicketToday.status || '').toUpperCase()) +
          '). Only one consultation ticket per patient per day is allowed. ' +
          '<a href="' + appPath('/queue/' + encodeURIComponent(consultStationId)) + '" style="margin-left:6px;font-weight:700;">Open Consultation queue</a>';
      }

      if (lastQueueStatus.consultation_today && lastQueueStatus.consultation_today.diagnosis) {
        html += ' <span class="muted">Consultation record already exists for today.</span>';
      }

      queueWarning.innerHTML = html;
      queueWarning.style.display = '';
      setCreateTicketEnabled(false);
    }

    function loadQueueStatus(patientId) {
      if (!patientId) {
        hideQueueWarning();
        return;
      }
      var xhr = new XMLHttpRequest();
      xhr.open('GET', appPath('/patients/' + encodeURIComponent(patientId) + '/queue-status'), true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhr.status !== 200) {
          hideQueueWarning();
          return;
        }
        try {
          lastQueueStatus = JSON.parse(xhr.responseText) || {};
          refreshRoutingGuard();
        } catch (e) {
          hideQueueWarning();
        }
      };
      xhr.send();
    }

    if (stationSelect) {
      stationSelect.addEventListener('change', refreshRoutingGuard);
    }

    function setSelected(patient) {
      // Set hidden select to satisfy form submit
      clearSelect();
      if (placeholder) {
        placeholder.selected = false;
        placeholder.disabled = true;
      }
      var opt = document.createElement('option');
      opt.value = patient.value;
      opt.text = patient.text;
      opt.selected = true;
      select.appendChild(opt);
      select.value = patient.value;

      input.value = patient.label;
      hideResults();
      loadAppointmentToday(patient.value);
      loadQueueStatus(patient.value);
    }

    function hideResults() {
      if (!results) return;
      results.style.display = 'none';
      while (results.firstChild) results.removeChild(results.firstChild);
      if (backdrop) backdrop.style.display = 'none';
    }

    function showResults(matches) {
      if (!results) return;
      while (results.firstChild) results.removeChild(results.firstChild);

      if (!matches || matches.length === 0) {
        hideResults();
        return;
      }

      var max = Math.min(matches.length, 8);
      for (var k = 0; k < max; k++) {
        (function (patient) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'auto-item';
          var apptBadge = '';
          for (var a = 0; a < todayApptIds.length; a++) {
            if (String(todayApptIds[a]) === String(patient.value)) {
              apptBadge = ' <span class=\"pill waiting\" style=\"font-size:11px;padding:2px 6px;\">Appt today</span>';
              break;
            }
          }
          btn.innerHTML = patient.text + apptBadge + '<span class=\"auto-sub\">' + patient.label + '</span>';
          btn.onclick = function () { setSelected(patient); };
          results.appendChild(btn);
        })(matches[k]);
      }
      results.style.display = 'block';
      if (backdrop) backdrop.style.display = 'block';
    }

    function applyFilter() {
      var q = normalize(input.value);
      if (q === '') {
        hideResults();
        hideAppointmentBanner();
        hideQueueWarning();
        return;
      }

      var matches = [];
      for (var k = 0; k < original.length; k++) {
        if (normalize(original[k].label).indexOf(q) !== -1) matches.push(original[k]);
      }
      showResults(matches);
    }

    function on(el, evt, handler) {
      if (el.addEventListener) el.addEventListener(evt, handler);
      else if (el.attachEvent) el.attachEvent('on' + evt, handler);
    }

    on(input, 'keyup', function (e) {
      e = e || window.event;
      var key = e.key || e.keyCode;
      if (key === 'Escape' || key === 27) {
        input.value = '';
        applyFilter();
        if (input.blur) input.blur();
        return;
      }
      applyFilter();
    });

    on(input, 'input', applyFilter);
    if (backdrop) {
      backdrop.onclick = hideResults;
    }
    // Preselect patient when coming from Add Patient suggestions
    try {
      var pre = <?= json_encode($prefillPatientId) ?>;
      var preAppt = <?= json_encode($prefillAppointment) ?>;
      var preApptId = <?= json_encode($prefillAppointmentId) ?>;
      if (pre && select.value) {
        for (var x = 0; x < select.options.length; x++) {
          if (parseInt(select.options[x].value, 10) === pre) {
            input.value = select.options[x].getAttribute('data-label') || select.options[x].text || '';
            break;
          }
        }
        if (preAppt) showAppointmentBanner(preAppt);
        else loadAppointmentToday(pre);
        loadQueueStatus(pre);
      }
    } catch (e) {}
    hideResults();
  })();
</script>

<?php if ($routingOnly): ?>
<script>
  (function () {
    var input = document.getElementById('reasonInput');
    var results = document.getElementById('reasonResults');
    var backdrop = document.getElementById('reasonBackdrop');
    if (!input || !results) return;

    var suggestions = [
      'Fever / lagnat',
      'Cough / ubo',
      'Colds / sipon',
      'Headache / sakit ng ulo',
      'Body pain / sakit ng katawan',
      'Diarrhea / LBM',
      'Stomach ache / pananakit ng tiyan',
      'High blood pressure monitoring',
      'Diabetes / blood sugar check',
      'Prenatal checkup',
      'Postnatal checkup',
      'Family planning consultation',
      'Child consultation',
      'Immunization / vaccination',
      'Wound care / dressing',
      'Medical certificate request',
      'TB-DOTS consultation',
      'Medication refill'
    ];

    function norm(s) {
      s = (s || '').toLowerCase();
      s = s.replace(/\s+/g, ' ');
      return s.replace(/^\s+|\s+$/g, '');
    }

    function hide() {
      results.style.display = 'none';
      while (results.firstChild) results.removeChild(results.firstChild);
      if (backdrop) backdrop.style.display = 'none';
    }

    function show(items) {
      while (results.firstChild) results.removeChild(results.firstChild);
      if (!items || items.length === 0) { hide(); return; }

      var max = Math.min(items.length, 8);
      for (var i = 0; i < max; i++) {
        (function (text) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'auto-item';
          btn.innerHTML = text + '<span class="auto-sub">Click to use this reason</span>';
          btn.onclick = function () { input.value = text; hide(); };
          results.appendChild(btn);
        })(items[i]);
      }
      results.style.display = 'block';
      if (backdrop) backdrop.style.display = 'block';
    }

    function apply() {
      var q = norm(input.value);
      if (q.length < 1) { hide(); return; }
      var matches = [];
      for (var i = 0; i < suggestions.length; i++) {
        if (norm(suggestions[i]).indexOf(q) !== -1) matches.push(suggestions[i]);
      }
      show(matches);
    }

    if (input.addEventListener) {
      input.addEventListener('input', apply);
      input.addEventListener('keyup', function (e) {
        e = e || window.event;
        var key = e.key || e.keyCode;
        if (key === 'Escape' || key === 27) hide();
      });
    } else {
      input.onkeyup = apply;
    }
    if (backdrop) backdrop.onclick = hide;
  })();
</script>
<?php endif; ?>

<?php if (!$routingOnly): ?>
  <div class="card" style="margin-top: 14px;">
    <div class="row" style="justify-content: space-between;">
      <h2 style="margin:0;">Today’s tickets</h2>
      <form method="POST" action="<?= h($queuePath('/call-next')) ?>">
        <button class="btn has-tip" data-tip="Tip: You can only have one NOW SERVING ticket at a time." type="submit" <?= $nowServing ? 'disabled' : '' ?>>Call next</button>
      </form>
    </div>

    <div class="table-wrap" style="margin-top: 10px;">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 9%;">Ticket</th>
          <th style="width: 22%;">Patient</th>
          <th style="width: 16%;">Reason</th>
          <th style="width: 10%;">Status</th>
          <th style="width: 14%;">Created</th>
          <th style="width: 12%;">Called</th>
          <th style="width: 12%;">Done</th>
          <th style="width: 12%;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="8" class="muted">No tickets today.</td></tr>
        <?php else: ?>
          <?php foreach ($tickets as $t): ?>
            <tr>
              <td><strong><?= h($t['ticket_no']) ?></strong></td>
              <td><?= h($t['full_name']) ?> <span class="muted">(<?= h($t['bhc_id']) ?>)</span></td>
              <td class="muted"><?= h((string) ($t['reason'] ?? '')) ?></td>
              <td>
                <span class="pill <?= h($t['status']) ?>"><?= strtoupper(h($t['status'])) ?></span>
              </td>
              <td class="muted"><?= h($t['created_at']) ?></td>
              <td class="muted"><?= h($t['called_at']) ?></td>
              <td class="muted"><?= h($t['completed_at']) ?></td>
              <td>
                <div class="row-actions row-actions-tight">
                  <a
                    class="btn has-tip"
                    href="<?= h(app_url('/patients/' . (int) $t['patient_id'] . '/history')) ?>"
                    style="padding: 8px 10px; font-size: 12px; box-shadow: none;"
                    data-tip="Open this patient&apos;s full record and visit history."
                  >History</a>
                  <?php if (($t['status'] ?? '') === 'skipped'): ?>
                    <form method="POST" action="<?= h($queuePath('/recall/' . (int) $t['id'])) ?>">
                      <button
                        class="btn has-tip"
                        type="submit"
                        style="box-shadow:none; padding: 8px 10px; font-size: 12px;"
                        data-tip="Patient returned — put them back in the waiting queue (same ticket number)."
                      >Recall</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
<?php endif; ?>

