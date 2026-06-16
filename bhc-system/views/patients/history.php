<?php
$patient = $patient ?? null;
$tickets = $tickets ?? [];
$appointments = $appointments ?? [];
$consultations = $consultations ?? [];
$medicines = $medicines ?? [];
$doctorComments = $doctorComments ?? [];
$clinicalDocuments = $clinicalDocuments ?? [];
$receiptMap = $receiptMap ?? [];
$pendingReceiptConsultations = $pendingReceiptConsultations ?? [];
$consultationHasMeds = $consultationHasMeds ?? [];
$nextAppointment = $nextAppointment ?? null;
$stations = $stations ?? [];
$errors = $errors ?? [];
$apptOld = $apptOld ?? [];
if (!$patient) {
    echo '<div class="muted">Patient not found.</div>';
    return;
}

$displayName = trim((string) ($patient['full_name'] ?? ''));
if ($displayName === '') {
    $displayName = Patient::buildFullName(
        (string) ($patient['first_name'] ?? ''),
        (string) ($patient['middle_name'] ?? ''),
        (string) ($patient['last_name'] ?? ''),
        (string) ($patient['suffix'] ?? '')
    );
}

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

$visits = $visits ?? [];
$visitDetails = $visitDetails ?? [];
$activeQueueTickets = $activeQueueTickets ?? [];
$todayConsultation = $todayConsultation ?? null;
$hasApptErrors = !empty($errors);
$todayScheduledAppointment = null;
if (
    $nextAppointment
    && ($nextAppointment['status'] ?? '') === 'scheduled'
    && ($nextAppointment['appointment_date'] ?? '') === date('Y-m-d')
) {
    $todayScheduledAppointment = $nextAppointment;
}

$historySectionCounts = [
    'visits' => count($visits),
    'receipts' => count($clinicalDocuments),
    'consultations' => count($consultations),
    'comments' => count($doctorComments),
    'medicines' => count($medicines),
    'appointments' => count($appointments),
    'tickets' => count($tickets),
];

$patientAge = null;
if (!empty($patient['birthdate'])) {
    try {
        $birthdate = new DateTime((string) $patient['birthdate']);
        $patientAge = (new DateTime('today'))->diff($birthdate)->y;
    } catch (Throwable) {
        $patientAge = null;
    }
}

$activeQueueByPatient = [(int) $patient['id'] => $activeQueueTickets ?? []];
$patientId = (int) $patient['id'];
$patientIsArchived = Patient::isArchived($patient);
$residencyStatus = Patient::normalizeResidencyStatus((string) ($patient['residency_status'] ?? Patient::RESIDENCY_PENDING));
?>

<div class="card patient-history-hero">
  <div class="patient-history-hero-main">
    <div class="patient-history-identity">
      <p class="patient-history-eyebrow">Patient history</p>
      <h1 class="patient-history-name"><?= h($displayName) ?></h1>
      <div class="patient-history-meta">
        <span class="pill patient-history-id-pill">BHC ID <?= h($patient['bhc_id']) ?></span>
        <?php if (!empty($patient['sex'])): ?>
          <span class="pill patient-history-meta-pill"><?= h($patient['sex']) ?></span>
        <?php endif; ?>
        <?php if ($patientAge !== null): ?>
          <span class="pill patient-history-meta-pill"><?= (int) $patientAge ?> yrs</span>
        <?php endif; ?>
        <?php if (!empty($patient['contact_number'])): ?>
          <span class="pill patient-history-meta-pill"><?= h($patient['contact_number']) ?></span>
        <?php endif; ?>
        <?php if (!empty($patient['barangay'])): ?>
          <span class="pill patient-history-meta-pill"><?= h($patient['barangay']) ?></span>
        <?php endif; ?>
        <?php
          $residencyPill = match (true) {
              $residencyStatus === Patient::RESIDENCY_VERIFIED => 'done',
              $residencyStatus === Patient::RESIDENCY_NON_RESIDENT => 'skipped',
              !Patient::requiresResidencyVerification($patient) => 'done',
              default => 'waiting',
          };
        ?>
        <span class="pill <?= h($residencyPill) ?> patient-history-meta-pill"><?= h(Patient::residencyDisplayLabel($patient)) ?></span>
        <?php if ($patientIsArchived): ?>
          <span class="pill skipped patient-history-meta-pill">Archived</span>
        <?php endif; ?>
        <?php if (!empty($activeQueueTickets)): ?>
          <span class="pill serving patient-history-meta-pill">In queue today</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="row-actions patient-history-hero-actions">
      <a class="btn" href="<?= h(app_url('/patients/' . (int) $patient['id'] . '/edit')) ?>">Edit record</a>
      <span class="patient-history-route-action">
        <?php $patientCanRoute = Patient::canReceiveServices($patient); $patientIsArchived = $patientIsArchived; require __DIR__ . '/../partials/patient_route_action.php'; ?>
      </span>
    </div>
  </div>
</div>

<?php if (!empty($activeQueueTickets)): ?>
  <?php
    $activeQueueLabels = array_map(
        static fn (array $t): string => ($t['ticket_no'] ?? 'Ticket')
            . ' at '
            . ($t['station_name'] ?? 'station')
            . ' ('
            . strtoupper((string) ($t['status'] ?? 'active'))
            . ')',
        $activeQueueTickets
    );
  ?>
  <div class="notice" style="margin-top: 14px;" role="status">
    This patient is already in today&apos;s queue: <?= h(implode('; ', $activeQueueLabels)) ?>.
    Use <strong>Open <?= h($activeQueueTickets[0]['station_name']) ?> queue</strong> instead of routing again.
  </div>
<?php endif; ?>

<?php if ($patientIsArchived): ?>
  <div class="notice warn" style="margin-top: 14px;" role="status">
    This patient is <strong>archived</strong> and hidden from the active registry. All visit history below is retained for records.<?php if (!empty($isAdmin)): ?> Use <strong>Restore patient</strong> to return them to the registry.<?php endif; ?>
  </div>
  <?php if (!empty($isAdmin)): ?>
    <div class="row-actions" style="margin-top: 10px;">
      <?php $returnTo = '/patients/' . (int) $patient['id'] . '/history'; require __DIR__ . '/../partials/patient_archive_actions.php'; ?>
    </div>
  <?php endif; ?>
<?php elseif (!Patient::canReceiveServices($patient) && Patient::requiresResidencyVerification($patient)): ?>
  <div class="notice warn" style="margin-top: 14px;" role="status">
    <?php if ($residencyStatus === Patient::RESIDENCY_PENDING): ?>
      Balong Bato residency is <strong>pending verification</strong>. Edit the patient record and confirm the supporting document before routing to the queue.
    <?php else: ?>
      This patient is <strong>not verified</strong> as a Balong Bato resident. BHC services require acceptable proof of residence before routing.
    <?php endif; ?>
  </div>
<?php elseif (!Patient::canReceiveServices($patient)): ?>
  <div class="notice warn" style="margin-top: 14px;" role="status">
    This patient is marked as <strong>not verified</strong> for Balong Bato residency and cannot be routed for regular BHC services.
  </div>
<?php endif; ?>

<nav class="history-jump-nav card" aria-label="Jump to patient history section">
  <span class="history-jump-label muted">Jump to</span>
  <a href="#section-schedule-appointment">Schedule</a>
  <a href="#section-add-clinical" data-history-section="section-add-clinical">Clinical record</a>
  <a href="#section-visit-history">Visits</a>
  <a href="#section-receipts">Documents</a>
  <a href="#section-consultations">Consultations</a>
  <a href="#section-doctor-comments">Comments</a>
  <a href="#section-medicine-history">Medicines</a>
  <a href="#section-appointment-history">Appointments</a>
  <a href="#section-queue-history">Queue</a>
</nav>

<div class="grid cols-2 patient-history-info-grid">
  <div class="card patient-info-card patient-info-demographics">
    <div class="patient-info-card-head">
      <h2>Demographics</h2>
      <span class="patient-info-card-tag">Patient profile</span>
    </div>
    <dl class="detail-list patient-detail-list">
      <div><dt>Sex</dt><dd><?= h($patient['sex'] ?? '') ?></dd></div>
      <div><dt>Date of birth</dt><dd><?= h($patient['birthdate'] ?? '') ?></dd></div>
      <div><dt>Contact</dt><dd><?= h($patient['contact_number'] ?? '—') ?></dd></div>
      <div><dt>PhilHealth</dt><dd><?= h($patient['philhealth_no'] ?? '—') ?></dd></div>
      <div><dt>Address</dt><dd><?= h($patient['address'] ?? '—') ?></dd></div>
      <div><dt>Barangay</dt><dd><?= h($patient['barangay'] ?? '—') ?></dd></div>
      <div><dt>Residency status</dt><dd><?= h(Patient::residencyDisplayLabel($patient)) ?></dd></div>
      <div><dt>Residency proof</dt><dd><?= h(Patient::residencyProofLabel((string) ($patient['residency_proof_type'] ?? ''))) ?></dd></div>
      <?php if (!empty($patient['residency_proof_notes'])): ?>
        <div class="detail-row-multiline"><dt>Proof details</dt><dd><?= h($patient['residency_proof_notes']) ?></dd></div>
      <?php endif; ?>
      <div><dt>Civil status</dt><dd><?= h($patient['civil_status'] ?? '—') ?></dd></div>
      <div><dt>Emergency contact</dt><dd><?= h(trim(($patient['emergency_contact_name'] ?? '') . ' ' . ($patient['emergency_contact_phone'] ?? '')) ?: '—') ?></dd></div>
      <?php if (!empty($patient['notes'])): ?>
        <div class="detail-row-multiline"><dt>Notes</dt><dd><?= nl2br(h($patient['notes'])) ?></dd></div>
      <?php endif; ?>
    </dl>
  </div>

  <div class="card patient-info-card patient-info-appointment<?= $nextAppointment ? ' has-appointment' : '' ?>">
    <div class="patient-info-card-head">
      <h2>Next appointment</h2>
      <?php if ($nextAppointment): ?>
        <span class="pill waiting patient-info-card-tag">Scheduled</span>
      <?php else: ?>
        <span class="patient-info-card-tag muted-tag">No visit booked</span>
      <?php endif; ?>
    </div>
    <?php if ($nextAppointment): ?>
      <p style="margin: 0 0 6px;"><strong><?= h($nextAppointment['appointment_date']) ?></strong>
        <?php if (!empty($nextAppointment['appointment_time'])): ?>
          at <?= h(substr((string) $nextAppointment['appointment_time'], 0, 5)) ?>
        <?php endif; ?>
      </p>
      <?php if (!empty($nextAppointment['purpose'])): ?>
        <p class="muted" style="margin: 0;"><?= h($nextAppointment['purpose']) ?></p>
      <?php endif; ?>
      <?php if (!empty($nextAppointment['station_name'])): ?>
        <p class="muted" style="margin: 8px 0 0;">Station: <?= h($nextAppointment['station_name']) ?></p>
      <?php endif; ?>
    <?php else: ?>
      <p class="muted" style="margin: 0;">No upcoming appointment on file.</p>
    <?php endif; ?>
  </div>
</div>

<?php
  $sectionId = 'section-schedule-appointment';
  $sectionTitle = 'Schedule next appointment';
  $sectionOpen = $hasApptErrors;
  $sectionHint = 'Record a follow-up or return visit';
  $sectionBadge = $nextAppointment ? 'Scheduled' : null;
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="muted" style="margin-bottom: 12px;">Record a follow-up or return visit for this patient.</div>

  <?php if (!empty($errors)): ?>
    <ul style="margin: 0 0 12px; padding-left: 20px; color: var(--danger);">
      <?php foreach ($errors as $e): ?>
        <li><?= h($e) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST" action="<?= h(app_route('/patients/' . (int) $patient['id'] . '/appointments')) ?>">
    <div class="grid cols-2">
      <div>
        <label>Date</label>
        <input type="date" name="appointment_date" min="<?= h(date('Y-m-d')) ?>" value="<?= h($apptOld['appointment_date'] ?? '') ?>" required />
      </div>
      <div>
        <label>Time (optional)</label>
        <input type="time" name="appointment_time" value="<?= h($apptOld['appointment_time'] ?? '') ?>" />
      </div>
      <div>
        <label>Purpose (optional)</label>
        <input name="purpose" placeholder="e.g., Follow-up consultation" value="<?= h($apptOld['purpose'] ?? '') ?>" />
      </div>
      <div>
        <label>Preferred station (optional)</label>
        <select name="station_id">
          <option value="">— Any / not set —</option>
          <?php foreach ($stations as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= (int) ($apptOld['station_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
              <?= h($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="span-2">
        <label>Notes (optional)</label>
        <textarea name="appointment_notes" rows="2"><?= h($apptOld['appointment_notes'] ?? '') ?></textarea>
      </div>
      <div class="span-2">
        <button class="btn ok" type="submit">Save appointment</button>
      </div>
    </div>
  </form>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-add-clinical';
  $sectionTitle = 'Add clinical record';
  $sectionOpen = $hasApptErrors || $todayScheduledAppointment !== null;
  $sectionHint = 'Diagnosis and medicines';
  $sectionBadge = !empty($todayConsultation) ? 'Today' : null;
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="muted" style="margin-bottom: 12px;">Record diagnosis and medicines (for walk-ins or backdated entries).</div>
  <?php require __DIR__ . '/../partials/consultation_today_notice.php'; ?>
  <?php if ($todayScheduledAppointment): ?>
    <?php
      $appointment = $todayScheduledAppointment;
      $patient = $patient ?? null;
      $showPatient = false;
      $showActions = true;
      require __DIR__ . '/../partials/followup_appointment_today.php';
    ?>
  <?php endif; ?>
  <form method="POST" action="<?= h(app_route('/patients/' . (int) $patient['id'] . '/consultations')) ?>">
    <?php if ($todayScheduledAppointment): ?>
      <input type="hidden" name="appointment_id" value="<?= (int) $todayScheduledAppointment['id'] ?>" />
    <?php endif; ?>
    <div class="grid cols-2">
      <div>
        <label>Consultation date</label>
        <input type="date" name="consultation_date" value="<?= h(date('Y-m-d')) ?>" />
      </div>
      <div>
        <label>Record type</label>
        <select name="record_type">
          <option value="prescribed">Prescribed at consultation</option>
          <option value="dispensed">Dispensed (given to patient)</option>
        </select>
      </div>
      <div class="span-2">
        <label>Diagnosis</label>
        <textarea name="diagnosis" rows="2" required placeholder="Primary diagnosis or impression"><?= h($todayConsultation['diagnosis'] ?? '') ?></textarea>
      </div>
      <div class="span-2">
        <label>Clinical notes (optional)</label>
        <textarea name="clinical_notes" rows="2"><?= h($todayConsultation['clinical_notes'] ?? '') ?></textarea>
      </div>
      <div class="span-2">
        <?php $showReceipt = true; $initialRows = 1; require __DIR__ . '/../partials/medicine_lines.php'; ?>
      </div>
      <div class="span-2 form-submit-actions">
        <button class="btn ok" type="submit" id="clinicalRecordSubmitBtn" style="box-shadow: none;"><?= !empty($todayConsultation) ? 'Update clinical record' : 'Save clinical record' ?></button>
      </div>
    </div>
  </form>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-visit-history';
  $sectionTitle = 'Visit history';
  $sectionOpen = false;
  $sectionHint = 'Queue tickets and triage by day';
  $sectionBadge = $historySectionCounts['visits'] > 0 ? (string) $historySectionCounts['visits'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'visit' : 'visits';
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="muted" style="margin-bottom: 12px;">Each visit groups the patient&apos;s queue tickets and triage for that day.</div>
  <?php if (empty($visits)): ?>
    <p class="muted" style="margin: 0;">No visit episodes recorded yet. A visit is created when the patient is queued.</p>
  <?php else: ?>
    <?php foreach ($visits as $visit): ?>
      <?php
        $vid = (int) $visit['id'];
        $detail = $visitDetails[$vid] ?? ['tickets' => [], 'triage' => null];
        $triage = $detail['triage'] ?? null;
      ?>
      <div style="padding: 14px 0; border-bottom: 1px solid rgba(15,23,42,.06);">
        <div class="row" style="justify-content: space-between; align-items: flex-start; gap: 10px;">
          <div>
            <strong><?= h($visit['visit_date']) ?></strong>
            <span class="muted">· <?= h($visit['visit_no']) ?></span>
            <span class="pill <?= ($visit['status'] ?? '') === 'completed' ? 'done' : 'waiting' ?>" style="margin-left: 6px; font-size: 11px;">
              <?= h(ucfirst((string) ($visit['status'] ?? 'open'))) ?>
            </span>
            <?php if (!empty($visit['primary_reason'])): ?>
              <div class="muted" style="font-size: 13px; margin-top: 4px;">Reason: <?= h($visit['primary_reason']) ?></div>
            <?php endif; ?>
          </div>
          <div class="muted" style="font-size: 13px;">
            <?= (int) ($visit['ticket_count'] ?? 0) ?> ticket(s)
            <?php if ((int) ($visit['triage_count'] ?? 0) > 0): ?> · Triage recorded<?php endif; ?>
          </div>
        </div>
        <?php if ($triage): ?>
          <div class="muted" style="margin-top: 8px; font-size: 13px;">
            Vitals:
            BP <?= h(TriageRecord::formatBloodPressure($triage)) ?>,
            Temp <?= h($triage['temperature'] ?? '—') ?>°C,
            Pulse <?= h($triage['pulse_rate'] ?? '—') ?> bpm,
            Weight <?= h($triage['weight_kg'] ?? '—') ?> kg
          </div>
        <?php endif; ?>
        <?php if (!empty($detail['tickets'])): ?>
          <div style="margin-top: 8px; font-size: 13px;">
            <?php foreach ($detail['tickets'] as $tk): ?>
              <span class="pill <?= h($tk['status']) ?>" style="margin: 0 6px 6px 0; font-size: 11px;">
                <?= h($tk['station_name']) ?> <?= h($tk['ticket_no']) ?> (<?= h(strtoupper((string) $tk['status'])) ?>)
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-receipts';
  $sectionTitle = 'Issued clinical documents';
  $sectionOpen = false;
  $sectionHint = 'Receipts, certificates, referrals';
  $sectionBadge = $historySectionCounts['receipts'] > 0 ? (string) $historySectionCounts['receipts'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'receipt' : 'receipts';
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="muted" style="margin-bottom: 12px;">Saved medicine receipts, medical certificates, and referral letters from patient visits.</div>
  <?php if (!empty($pendingReceiptConsultations)): ?>
    <div class="card" style="margin-bottom: 12px; padding: 12px 14px; background: rgba(47, 107, 255, 0.06); border: 1px solid rgba(47, 107, 255, 0.2);">
      <div style="font-weight: 600; margin-bottom: 6px;">Prescription receipt needed</div>
      <div class="muted" style="font-size: 13px; margin-bottom: 10px;">
        This patient has LGU or external medicine requests without a saved receipt yet. Issue the receipt to show it here and give the patient a printable copy.
      </div>
      <div class="row-actions row-actions-tight">
        <?php foreach ($pendingReceiptConsultations as $pendingConsultationId): ?>
          <form method="POST" action="<?= h(app_route('/clinical/consultations/' . (int) $pendingConsultationId . '/issue-receipt')) ?>" style="margin:0;">
            <button class="btn ok" type="submit" style="box-shadow:none;">Issue receipt for visit #<?= (int) $pendingConsultationId ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Document no.</th>
          <th>Type</th>
          <th>Date issued</th>
          <th>Visit date</th>
          <th>Title</th>
          <th>Summary</th>
          <th>Issued by</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clinicalDocuments)): ?>
          <tr><td colspan="8" class="muted">No saved clinical documents yet. Medicine receipts are issued when medicines are prescribed or dispensed; certificates and referrals are issued by doctors.</td></tr>
        <?php else: ?>
          <?php foreach ($clinicalDocuments as $doc): ?>
            <?php
              $content = ClinicalDocument::decodeContent($doc);
              $docType = (string) ($doc['document_type'] ?? '');
              $summary = match ($docType) {
                  'medicine_receipt' => ((int) ($content['medicine_count'] ?? count($content['medicines'] ?? []))) . ' item(s)',
                  'medical_certificate' => (string) ($content['purpose'] ?? '—'),
                  'referral' => 'To ' . (string) ($content['referred_to'] ?? '—'),
                  'recommendation' => (string) ($content['recommendation_title'] ?? 'Clinical recommendation'),
                  default => '—',
              };
              $issuedBy = trim((string) ($doc['issued_by_display_name'] ?? $doc['issued_by_username'] ?? '—'));
            ?>
            <tr>
              <td><strong><?= h($doc['document_no']) ?></strong></td>
              <td><?= h(ClinicalDocument::typeLabel($docType)) ?></td>
              <td class="muted"><?= h(substr((string) ($doc['issued_at'] ?? ''), 0, 16)) ?></td>
              <td><?= h($content['consultation_date'] ?? '') ?></td>
              <td><?= h($doc['title'] ?? ClinicalDocument::typeLabel($docType)) ?></td>
              <td class="muted"><?= h($summary) ?></td>
              <td class="muted"><?= h($issuedBy) ?></td>
              <td>
                <a class="btn ok" href="<?= h(app_url('/clinical/documents/' . (int) $doc['id'])) ?>" target="_blank" rel="noopener" style="padding:6px 10px;font-size:12px;box-shadow:none;">View / Print</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-consultations';
  $sectionTitle = 'Consultation & diagnosis history';
  $sectionOpen = true;
  $sectionHint = 'Past diagnoses and visit notes';
  $sectionBadge = $historySectionCounts['consultations'] > 0 ? (string) $historySectionCounts['consultations'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'record' : 'records';
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Visit</th>
          <th>Diagnosis</th>
          <th>Notes</th>
          <th>Ticket</th>
          <th>Recorded by</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($consultations)): ?>
          <tr><td colspan="6" class="muted">No consultation records yet.</td></tr>
        <?php else: ?>
          <?php foreach ($consultations as $c): ?>
            <tr id="consult-<?= (int) $c['id'] ?>">
              <td><?= h($c['consultation_date']) ?></td>
              <td>
                <?php if (!empty($c['appointment_id'])): ?>
                  <span class="pill done" style="font-size: 11px;">Follow-up</span>
                  <?php if (!empty($c['linked_appointment_purpose'])): ?>
                    <div class="muted" style="font-size: 12px; margin-top: 4px;"><?= h($c['linked_appointment_purpose']) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="muted">Walk-in</span>
                <?php endif; ?>
              </td>
              <td><strong><?= h($c['diagnosis']) ?></strong></td>
              <td class="muted"><?= h($c['clinical_notes'] ?? '') ?></td>
              <td class="muted"><?= h($c['ticket_no'] ?? '—') ?></td>
              <td class="muted"><?= h($c['recorded_by_name'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-doctor-comments';
  $sectionTitle = 'Doctor comments';
  $sectionOpen = false;
  $sectionHint = 'Notes from doctors';
  $sectionBadge = $historySectionCounts['comments'] > 0 ? (string) $historySectionCounts['comments'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'comment' : 'comments';
  require __DIR__ . '/../partials/history_section.php';
?>
  <?php if (empty($doctorComments)): ?>
    <p class="muted" style="margin: 0;">No doctor comments yet.</p>
  <?php else: ?>
    <?php foreach ($doctorComments as $dc): ?>
      <div style="padding: 12px 0; border-bottom: 1px solid rgba(15,23,42,.06);">
        <div class="muted" style="font-size: 13px;">
          <strong><?= h(DoctorComment::doctorLabel($dc)) ?></strong> &middot; <?= h($dc['created_at']) ?>
        </div>
        <div style="margin-top: 6px;"><?= nl2br(h($dc['comment'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-medicine-history';
  $sectionTitle = 'Medicine history';
  $sectionOpen = false;
  $sectionHint = 'Clinic stock, LGU requests, and external purchases';
  $sectionBadge = $historySectionCounts['medicines'] > 0 ? (string) $historySectionCounts['medicines'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'item' : 'items';
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="muted" style="margin-bottom: 12px;">Medicines from clinic stock, LGU requests, or external purchase — with quantities and receipt links.</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Medicine</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Source</th>
          <th>Status</th>
          <th>Receipt doc.</th>
          <th>Diagnosis</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($medicines)): ?>
          <tr><td colspan="8" class="muted">No medicines recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($medicines as $m): ?>
            <?php
              $consultationId = (int) ($m['consultation_id'] ?? 0);
              $medDocId = $consultationId > 0 ? (int) ($receiptMap[$consultationId] ?? 0) : 0;
              $source = MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC));
              $needsPrescriptionReceipt = in_array($source, [MedicineDispensing::SOURCE_LGU, MedicineDispensing::SOURCE_EXTERNAL], true);
            ?>
            <tr>
              <td class="muted"><?= h(substr((string) ($m['created_at'] ?? ''), 0, 10)) ?></td>
              <td><strong><?= h($m['medicine_name']) ?></strong></td>
              <td><?= h(format_medicine_qty($m['quantity'])) ?></td>
              <td class="muted"><?= h($m['unit']) ?></td>
              <td class="muted"><?= h(MedicineDispensing::procurementShortLabel($source)) ?></td>
              <td><span class="pill <?= ($m['dispense_status'] ?? '') === 'dispensed' ? 'done' : 'waiting' ?>"><?= h(strtoupper((string) ($m['dispense_status'] ?? ''))) ?></span></td>
              <td>
                <?php if ($medDocId > 0): ?>
                  <a class="btn" href="<?= h(app_url('/clinical/documents/' . (int) $medDocId)) ?>" target="_blank" rel="noopener" style="padding:4px 8px;font-size:11px;box-shadow:none;">View</a>
                <?php elseif ($consultationId > 0 && ($needsPrescriptionReceipt || !empty($m['receipt_issued']))): ?>
                  <form method="POST" action="<?= h(app_route('/clinical/consultations/' . (int) $consultationId . '/issue-receipt')) ?>" style="margin:0;">
                    <button class="btn ok" type="submit" style="padding:4px 8px;font-size:11px;box-shadow:none;">Issue receipt</button>
                  </form>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td class="muted"><?= h($m['diagnosis'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-appointment-history';
  $sectionTitle = 'Appointment history';
  $sectionOpen = false;
  $sectionHint = 'Scheduled follow-up visits';
  $sectionBadge = $historySectionCounts['appointments'] > 0 ? (string) $historySectionCounts['appointments'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'appointment' : 'appointments';
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Purpose</th>
          <th>Station</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($appointments)): ?>
          <tr><td colspan="6" class="muted">No appointments recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($appointments as $a): ?>
            <tr>
              <td><?= h($a['appointment_date']) ?></td>
              <td class="muted"><?= !empty($a['appointment_time']) ? h(substr((string) $a['appointment_time'], 0, 5)) : '—' ?></td>
              <td><?= h($a['purpose'] ?? '') ?></td>
              <td class="muted"><?= h($a['station_name'] ?? '—') ?></td>
              <td><span class="pill <?= h(appt_status_pill((string) $a['status'])) ?>"><?= h(strtoupper(str_replace('_', ' ', (string) $a['status']))) ?></span></td>
              <td>
                <?php if (!empty($a['linked_consultation_id'])): ?>
                  <a class="btn ok" href="#consult-<?= (int) $a['linked_consultation_id'] ?>" style="padding:6px 10px;font-size:12px;box-shadow:none;">View consultation</a>
                <?php elseif (($a['status'] ?? '') === 'scheduled'): ?>
                  <form method="POST" action="<?= h(app_route('/appointments/' . (int) $a['id'] . '/status')) ?>" style="display:inline;">
                    <input type="hidden" name="status" value="completed" />
                    <button class="btn" type="submit" style="padding:6px 10px;font-size:12px;">Done</button>
                  </form>
                  <form method="POST" action="<?= h(app_route('/appointments/' . (int) $a['id'] . '/status')) ?>" style="display:inline;">
                    <input type="hidden" name="status" value="no_show" />
                    <button class="btn" type="submit" style="padding:6px 10px;font-size:12px;">No-show</button>
                  </form>
                  <form method="POST" action="<?= h(app_route('/appointments/' . (int) $a['id'] . '/status')) ?>" style="display:inline;">
                    <input type="hidden" name="status" value="cancelled" />
                    <button class="btn" type="submit" style="padding:6px 10px;font-size:12px;">Cancel</button>
                  </form>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<?php
  $sectionId = 'section-queue-history';
  $sectionTitle = 'Queue / visit history';
  $sectionOpen = false;
  $sectionHint = 'Recent queue tickets';
  $sectionBadge = $historySectionCounts['tickets'] > 0 ? (string) $historySectionCounts['tickets'] : null;
  $sectionBadgeLabel = ((int) ($sectionBadge ?? 0)) === 1 ? 'ticket' : 'tickets';
  require __DIR__ . '/../partials/history_section.php';
?>
  <div class="muted" style="margin-bottom: 12px;">Recent queue tickets for this patient (newest first).</div>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ticket</th>
          <th>Station</th>
          <th>Status</th>
          <th>Reason</th>
          <th>Created</th>
          <th>Completed</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="6" class="muted">No queue tickets yet.</td></tr>
        <?php else: ?>
          <?php foreach ($tickets as $t): ?>
            <tr>
              <td><strong><?= h($t['ticket_no']) ?></strong></td>
              <td><?= h($t['station_name']) ?></td>
              <td><span class="pill <?= h($t['status']) ?>"><?= h(strtoupper((string) $t['status'])) ?></span></td>
              <td class="muted"><?= h($t['reason'] ?? '') ?></td>
              <td class="muted"><?= h($t['created_at']) ?></td>
              <td class="muted"><?= h($t['completed_at'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php require __DIR__ . '/../partials/history_section_end.php'; ?>

<style>
  .patient-history-hero {
    margin-bottom: 14px;
    padding: 0;
    overflow: hidden;
    border-color: rgba(47, 107, 255, 0.22);
    box-shadow: 0 14px 30px rgba(47, 107, 255, 0.1);
  }
  .patient-history-hero-main {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px 18px;
    padding: 18px 20px;
    background: linear-gradient(135deg, rgba(47, 107, 255, 0.14) 0%, rgba(255, 255, 255, 0.96) 52%, rgba(20, 184, 122, 0.08) 100%);
    border-bottom: 1px solid rgba(47, 107, 255, 0.12);
  }
  .patient-history-identity {
    flex: 1 1 260px;
    min-width: 0;
  }
  .patient-history-eyebrow {
    margin: 0 0 4px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(29, 78, 216, 0.85);
  }
  .patient-history-name {
    margin: 0 0 10px;
    font-size: clamp(24px, 3vw, 32px);
    font-weight: 900;
    line-height: 1.15;
    color: var(--text);
    letter-spacing: -0.02em;
  }
  .patient-history-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .patient-history-id-pill {
    border-color: rgba(47, 107, 255, 0.35);
    background: rgba(47, 107, 255, 0.12);
    color: #1e3a8a;
    font-weight: 800;
  }
  .patient-history-meta-pill {
    background: rgba(255, 255, 255, 0.88);
    border-color: rgba(15, 23, 42, 0.1);
    color: var(--text);
    font-weight: 700;
  }
  .patient-history-hero-actions {
    flex: 1 1 220px;
    justify-content: flex-end;
  }
  .patient-history-info-grid {
    margin-top: 14px;
    gap: 14px;
    align-items: stretch;
  }
  .patient-info-card {
    border-color: rgba(47, 107, 255, 0.14);
    background: linear-gradient(180deg, rgba(47, 107, 255, 0.04) 0%, rgba(255, 255, 255, 1) 42%);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
  }
  .patient-info-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid rgba(47, 107, 255, 0.1);
  }
  .patient-info-card-head h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: #1e3a8a;
  }
  .patient-info-card-tag {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: rgba(29, 78, 216, 0.8);
  }
  .patient-info-card-tag.muted-tag {
    color: var(--muted);
    text-transform: none;
    letter-spacing: 0;
    font-weight: 700;
  }
  .patient-info-appointment.has-appointment {
    border-color: rgba(47, 107, 255, 0.24);
    background: linear-gradient(180deg, rgba(47, 107, 255, 0.08) 0%, rgba(255, 255, 255, 1) 48%);
  }
  .detail-list.patient-detail-list > div:nth-child(odd) {
    background: rgba(47, 107, 255, 0.03);
    border-radius: 8px;
  }
  @media (max-width: 760px) {
    .patient-history-hero-main {
      align-items: flex-start;
    }
    .patient-history-hero-actions {
      width: 100%;
      justify-content: flex-start;
    }
  }
  .history-jump-nav {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 10px;
    margin-top: 14px;
    padding: 12px 14px;
  }
  .history-jump-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-right: 4px;
  }
  .history-jump-nav a {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid rgba(15, 23, 42, 0.1);
    background: rgba(255, 255, 255, 0.9);
    font-size: 12px;
    font-weight: 700;
    color: var(--text);
    text-decoration: none;
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.12s ease;
  }
  .history-jump-nav a:hover {
    background: rgba(47, 107, 255, 0.08);
    border-color: rgba(47, 107, 255, 0.2);
    color: var(--pri);
    cursor: pointer;
  }
  .history-jump-nav a:active {
    transform: scale(0.98);
    cursor: pointer;
  }
  .history-jump-nav a.history-jump-focus {
    background: rgba(20, 184, 122, 0.14);
    border-color: rgba(20, 184, 122, 0.42);
    color: #065f46;
    box-shadow: 0 0 0 3px rgba(20, 184, 122, 0.18);
  }
  .history-section.history-section-focus > summary {
    border-color: rgba(20, 184, 122, 0.38);
    box-shadow: 0 0 0 3px rgba(20, 184, 122, 0.16), inset 0 0 0 1px rgba(20, 184, 122, 0.08);
  }
  .history-section-badge-emphasis {
    animation: history-badge-emphasis 1.1s ease-in-out 3;
    box-shadow: 0 0 0 4px rgba(20, 184, 122, 0.2);
  }
  @keyframes history-badge-emphasis {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 4px rgba(20, 184, 122, 0.2); }
    50% { transform: scale(1.04); box-shadow: 0 0 0 6px rgba(20, 184, 122, 0.28); }
  }
  @media (prefers-reduced-motion: reduce) {
    .history-section-badge-emphasis {
      animation: none;
    }
  }
  .history-section {
    margin-top: 14px;
    padding: 0;
    overflow: hidden;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }
  .history-section > summary {
    list-style: none;
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    margin: 0;
    border-radius: 14px;
    background: linear-gradient(180deg, rgba(47, 107, 255, 0.05) 0%, rgba(255, 255, 255, 0.65) 100%);
    border: 1px solid transparent;
    transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, transform 0.12s ease;
  }
  .history-section > summary::-webkit-details-marker {
    display: none;
  }
  .history-section > summary:hover {
    cursor: pointer;
    background: linear-gradient(180deg, rgba(47, 107, 255, 0.1) 0%, rgba(255, 255, 255, 0.92) 100%);
    border-color: rgba(47, 107, 255, 0.18);
    box-shadow: inset 0 0 0 1px rgba(47, 107, 255, 0.08);
  }
  .history-section > summary:active {
    cursor: pointer;
    transform: scale(0.995);
    background: linear-gradient(180deg, rgba(47, 107, 255, 0.14) 0%, rgba(255, 255, 255, 0.98) 100%);
  }
  .history-section > summary:focus {
    outline: none;
  }
  .history-section > summary:focus-visible {
    outline: 2px solid rgba(47, 107, 255, 0.45);
    outline-offset: 2px;
  }
  .history-section[open] > summary {
    border-radius: 14px 14px 0 0;
    border-color: rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(47, 107, 255, 0.08) 0%, rgba(255, 255, 255, 0.82) 100%);
  }
  .history-section-leading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 10px;
    min-width: 0;
    flex: 1 1 auto;
  }
  .history-section-title {
    font-size: 18px;
    font-weight: 800;
    line-height: 1.25;
  }
  .history-section-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 5px 11px 5px 8px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    line-height: 1;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    pointer-events: none;
    flex-shrink: 0;
  }
  .history-section-badge-icon {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .history-section-badge-icon svg {
    width: 12px;
    height: 12px;
    display: block;
  }
  .history-section-badge-value {
    min-width: 1.1em;
    text-align: center;
    font-size: 13px;
    font-weight: 900;
    line-height: 1;
  }
  .history-section-badge-label,
  .history-section-badge-text {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .history-section-badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
    box-shadow: 0 0 0 3px rgba(20, 184, 122, 0.18);
    animation: history-badge-pulse 1.8s ease-in-out infinite;
    flex-shrink: 0;
  }
  @keyframes history-badge-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.12); opacity: 0.82; }
  }
  .history-section-badge-count {
    color: #1e3a8a;
    background: linear-gradient(135deg, rgba(47, 107, 255, 0.16) 0%, rgba(255, 255, 255, 0.95) 100%);
    border-color: rgba(47, 107, 255, 0.28);
  }
  .history-section-badge-count .history-section-badge-icon {
    color: #1d4ed8;
    background: rgba(47, 107, 255, 0.14);
    border: 1px solid rgba(47, 107, 255, 0.18);
  }
  .history-section-badge-scheduled {
    color: #1e40af;
    background: linear-gradient(135deg, rgba(47, 107, 255, 0.14) 0%, rgba(255, 255, 255, 0.96) 100%);
    border-color: rgba(47, 107, 255, 0.3);
  }
  .history-section-badge-scheduled .history-section-badge-icon {
    color: #1d4ed8;
    background: rgba(47, 107, 255, 0.12);
    border: 1px solid rgba(47, 107, 255, 0.2);
  }
  .history-section-badge-today {
    color: #065f46;
    background: linear-gradient(135deg, rgba(20, 184, 122, 0.18) 0%, rgba(255, 255, 255, 0.96) 100%);
    border-color: rgba(20, 184, 122, 0.32);
    box-shadow: 0 4px 14px rgba(20, 184, 122, 0.14);
  }
  .history-section-badge-status {
    color: #334155;
    background: rgba(255, 255, 255, 0.92);
    border-color: rgba(15, 23, 42, 0.12);
  }
  .history-section > summary:hover .history-section-badge-count,
  .history-section > summary:hover .history-section-badge-scheduled {
    border-color: rgba(47, 107, 255, 0.42);
    box-shadow: 0 6px 16px rgba(47, 107, 255, 0.14);
  }
  .history-section > summary:hover .history-section-badge-today {
    border-color: rgba(20, 184, 122, 0.42);
    box-shadow: 0 6px 16px rgba(20, 184, 122, 0.16);
  }
  @media (prefers-reduced-motion: reduce) {
    .history-section-badge-dot {
      animation: none;
    }
  }
  .history-section-hint {
    font-size: 13px;
    flex: 1 1 180px;
  }
  .history-section-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
    padding: 6px 10px 6px 8px;
    border-radius: 999px;
    border: 1px solid rgba(47, 107, 255, 0.2);
    background: rgba(255, 255, 255, 0.92);
    color: var(--pri, #2f6bff);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    pointer-events: none;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
  }
  .history-section > summary:hover .history-section-toggle {
    background: #fff;
    border-color: rgba(47, 107, 255, 0.35);
    box-shadow: 0 4px 10px rgba(47, 107, 255, 0.12);
  }
  .history-section-toggle-icon {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    background: rgba(47, 107, 255, 0.1);
    position: relative;
    transition: transform 0.2s ease, background 0.18s ease;
  }
  .history-section-toggle-icon::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 7px;
    height: 7px;
    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: translate(-50%, -65%) rotate(45deg);
    transition: transform 0.2s ease;
  }
  .history-section-toggle-label::before {
    content: "Show";
  }
  .history-section[open] .history-section-toggle-label::before {
    content: "Hide";
  }
  .history-section[open] .history-section-toggle-icon {
    transform: rotate(180deg);
    background: rgba(47, 107, 255, 0.16);
  }
  .history-section-body {
    padding: 12px 16px 16px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .patient-history-route-action .appt-action {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    box-shadow: none;
  }
  .patient-history-route-action .appt-action-route {
    color: #fff;
    background: var(--ok, #14b87a);
    border: 1px solid rgba(20, 184, 122, 0.35);
  }
  .patient-history-route-action .appt-action-queue {
    color: #9a3412;
    border: 1px solid rgba(234, 88, 12, 0.28);
    background: rgba(251, 146, 60, 0.12);
  }
  .detail-list { margin: 0; }
  .detail-list > div { display: grid; grid-template-columns: 140px 1fr; gap: 8px; padding: 6px 0; border-bottom: 1px solid rgba(15,23,42,.06); }
  .detail-list > div:last-child { border-bottom: 0; }
  .detail-list dt { margin: 0; font-weight: 600; color: var(--muted); font-size: 13px; }
  .detail-list dd { margin: 0; }
  .detail-list.patient-detail-list > div {
    grid-template-columns: minmax(150px, 38%) 1fr;
    gap: 10px 14px;
    padding: 9px 10px;
    align-items: center;
  }
  .detail-list.patient-detail-list > .detail-row-multiline {
    align-items: start;
  }
  .detail-list.patient-detail-list dt {
    color: #334155;
    line-height: 1.35;
  }
  .detail-list.patient-detail-list dd {
    font-weight: 600;
    color: var(--text);
    line-height: 1.45;
    word-break: break-word;
  }
</style>

<script>
(function () {
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function openSection(section) {
    if (section && section.classList.contains('history-section')) {
      section.open = true;
    }
  }

  function emphasizeHistorySection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    openSection(section);

    var jumpLink = document.querySelector('.history-jump-nav a[href="#' + sectionId + '"]');
    if (jumpLink) jumpLink.classList.add('history-jump-focus');

    section.classList.add('history-section-focus');

    var badge = section.querySelector('.history-section-badge');
    if (badge) badge.classList.add('history-section-badge-emphasis');

    var summary = section.querySelector('.history-section-summary');
    if (summary) {
      summary.setAttribute('tabindex', '-1');
      summary.focus({ preventScroll: true });
    }

    window.setTimeout(function () {
      section.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }, 80);

    window.setTimeout(function () {
      if (jumpLink) jumpLink.classList.remove('history-jump-focus');
      section.classList.remove('history-section-focus');
      if (badge) badge.classList.remove('history-section-badge-emphasis');
    }, 3200);
  }

  function openSectionForHash() {
    var hash = window.location.hash;
    if (!hash) return;

    var sectionId = hash.replace('#', '');
    var target = document.getElementById(sectionId);
    if (!target) return;

    if (target.classList.contains('history-section')) {
      openSection(target);
      return;
    }

    var nested = target.closest('.history-section');
    if (nested) openSection(nested);
  }

  function handleSectionFocusFromHash() {
    var hash = window.location.hash.replace('#', '');
    if (!hash) return;

    openSectionForHash();

    if (hash === 'section-add-clinical') {
      emphasizeHistorySection('section-add-clinical');
    }
  }

  document.querySelectorAll('.history-jump-nav a').forEach(function (link) {
    link.addEventListener('click', function () {
      var id = (link.getAttribute('href') || '').replace('#', '');
      if (!id) return;
      var section = document.getElementById(id);
      if (section) openSection(section);
    });
  });

  handleSectionFocusFromHash();
  window.addEventListener('hashchange', handleSectionFocusFromHash);
})();
</script>
