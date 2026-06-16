<?php
$patient = $patient ?? null;
$activeTicket = $activeTicket ?? null;
$consultations = $consultations ?? [];
$medicines = $medicines ?? [];
$comments = $comments ?? [];
$clinicalDocuments = $clinicalDocuments ?? [];
$todayAppointment = $todayAppointment ?? null;
$tickets = $tickets ?? [];
$appointments = $appointments ?? [];
$nextAppointment = $nextAppointment ?? null;
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
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Patient clinical record</h1>
    <div class="muted">
      <strong><?= h($displayName) ?></strong> &middot; BHC ID <?= h($patient['bhc_id']) ?>
      <?php if ($activeTicket): ?>
        &middot; Ticket <strong><?= h($activeTicket['ticket_no'] ?? '') ?></strong>
      <?php endif; ?>
    </div>
  </div>
  <div class="row-actions row-actions-tight">
  <?php if ($activeTicket && ($activeTicket['status'] ?? '') === 'serving'): ?>
      <form method="POST" action="<?= h(app_route('/doctor/tickets/' . (int) $activeTicket['id'] . '/complete')) ?>">
        <button class="btn ok" type="submit" style="box-shadow: none;">Complete consultation</button>
      </form>
      <form method="POST" action="<?= h(app_route('/doctor/tickets/' . (int) $activeTicket['id'] . '/skip')) ?>">
        <button class="btn danger" type="submit" style="box-shadow: none;">Skip patient</button>
      </form>
    <?php elseif ($activeTicket && ($activeTicket['status'] ?? '') === 'waiting'): ?>
      <form method="POST" action="<?= h(app_route('/doctor/tickets/' . (int) $activeTicket['id'] . '/call')) ?>">
        <button class="btn ok" type="submit" style="box-shadow: none;">Call this patient</button>
      </form>
    <?php endif; ?>
    <a class="btn" href="<?= h(app_url('/doctor')) ?>">My patients</a>
  </div>
</div>

<?php if ($activeTicket): ?>
  <div class="card" style="margin-top: 14px; padding: 12px 14px;">
    <span class="pill <?= h($activeTicket['status'] ?? 'waiting') ?>"><?= h(strtoupper((string) ($activeTicket['status'] ?? ''))) ?></span>
    <span class="muted" style="margin-left: 8px;">
      Ticket <strong><?= h($activeTicket['ticket_no'] ?? '') ?></strong>
      <?php if (($activeTicket['status'] ?? '') === 'serving'): ?>
        — save the consultation record below, then complete when finished.
      <?php elseif (($activeTicket['status'] ?? '') === 'waiting'): ?>
        — call this patient when the consultation room is free.
      <?php endif; ?>
    </span>
  </div>
<?php endif; ?>

<div class="grid cols-2" style="margin-top: 14px; gap: 14px;">
  <div class="card">
    <h2 style="margin: 0 0 10px;">Demographics</h2>
    <dl class="detail-list">
      <div><dt>Sex</dt><dd><?= h($patient['sex'] ?? '') ?></dd></div>
      <div><dt>Date of birth</dt><dd><?= h($patient['birthdate'] ?? '') ?></dd></div>
      <div><dt>Contact</dt><dd><?= h($patient['contact_number'] ?? '—') ?></dd></div>
      <div><dt>Address</dt><dd><?= h($patient['address'] ?? '—') ?></dd></div>
      <div><dt>PhilHealth</dt><dd><?= h($patient['philhealth_no'] ?? '—') ?></dd></div>
      <?php if (!empty($patient['notes'])): ?>
        <div><dt>Registry notes</dt><dd><?= nl2br(h($patient['notes'])) ?></dd></div>
      <?php endif; ?>
    </dl>
  </div>
  <div class="card">
    <h2 style="margin: 0 0 10px;">Next appointment</h2>
    <?php if ($nextAppointment): ?>
      <p style="margin: 0;"><strong><?= h($nextAppointment['appointment_date']) ?></strong>
        <?php if (!empty($nextAppointment['appointment_time'])): ?>
          at <?= h(substr((string) $nextAppointment['appointment_time'], 0, 5)) ?>
        <?php endif; ?>
      </p>
      <p class="muted" style="margin: 8px 0 0;"><?= h($nextAppointment['purpose'] ?? '') ?></p>
    <?php else: ?>
      <p class="muted" style="margin: 0;">No upcoming appointment on file.</p>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Doctor comment</h2>
  <form method="POST" action="<?= h(app_route('/doctor/patients/' . (int) $patient['id'] . '/comments')) ?>">
    <?php if ($activeTicket): ?>
      <input type="hidden" name="queue_ticket_id" value="<?= (int) $activeTicket['id'] ?>" />
    <?php endif; ?>
    <textarea name="comment" rows="3" required placeholder="Clinical impression, follow-up advice, or notes for the health center team…"></textarea>
    <button class="btn ok" type="submit" style="margin-top: 10px;">Save comment</button>
  </form>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Record consultation</h2>
  <?php require __DIR__ . '/../partials/consultation_today_notice.php'; ?>
  <?php if ($todayAppointment): ?>
    <?php
      $appointment = $todayAppointment;
      $showPatient = false;
      $showActions = false;
      $footerNote = 'Save the consultation below to link and complete this follow-up appointment.';
      require __DIR__ . '/../partials/followup_appointment_today.php';
    ?>
  <?php endif; ?>
  <form method="POST" action="<?= h(app_route('/doctor/patients/' . (int) $patient['id'] . '/consultation')) ?>">
    <?php if ($todayAppointment): ?>
      <input type="hidden" name="appointment_id" value="<?= (int) $todayAppointment['id'] ?>" />
    <?php endif; ?>
    <div class="grid cols-2">
      <div class="span-2">
        <label>Diagnosis</label>
        <textarea name="diagnosis" rows="2" required placeholder="Primary diagnosis or clinical impression"><?= h($todayConsultation['diagnosis'] ?? '') ?></textarea>
      </div>
      <div class="span-2">
        <label>Clinical notes (optional)</label>
        <textarea name="clinical_notes" rows="2"><?= h($todayConsultation['clinical_notes'] ?? '') ?></textarea>
      </div>
      <div class="span-2">
        <?php $showReceipt = false; $initialRows = 1; require __DIR__ . '/../partials/medicine_lines.php'; ?>
      </div>
      <div class="span-2 form-submit-actions">
        <button class="btn ok" type="submit" style="box-shadow: none;"><?= !empty($todayConsultation) ? 'Update diagnosis &amp; prescription' : 'Save diagnosis &amp; prescription' ?></button>
      </div>
    </div>
  </form>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Issue clinical documents</h2>
  <p class="muted" style="margin: 0 0 12px;">Medical certificate, referral, or clinical recommendation for today&apos;s consultation visit. <a href="<?= h(app_url('/account/document-name')) ?>">Change your name on documents</a></p>
  <?php if (empty($todayConsultation)): ?>
    <p class="muted" style="margin: 0;">Save today&apos;s consultation record above before issuing documents.</p>
  <?php else: ?>
    <div class="grid cols-2" style="gap: 14px;">
      <div>
        <h3 style="margin: 0 0 10px; font-size: 15px;">Medical certificate</h3>
        <?php if (!empty($todayCertificate)): ?>
          <a class="btn ok" href="<?= h(app_url('/clinical/documents/' . (int) $todayCertificate['id'])) ?>" target="_blank" rel="noopener" style="box-shadow:none;">View certificate</a>
        <?php else: ?>
          <form method="POST" action="<?= h(app_route('/doctor/patients/' . (int) $patient['id'] . '/medical-certificate')) ?>">
            <label>Purpose</label>
            <input name="purpose" required placeholder="e.g. Fit to work, School excuse" />
            <label>Recommended rest (days, optional)</label>
            <input type="number" name="rest_days" min="0" max="365" placeholder="e.g. 3" />
            <label>Remarks (optional)</label>
            <textarea name="remarks" rows="2" placeholder="Additional notes for the certificate"></textarea>
            <button class="btn ok" type="submit" style="margin-top: 10px; box-shadow: none;">Issue certificate</button>
          </form>
        <?php endif; ?>
      </div>
      <div>
        <h3 style="margin: 0 0 10px; font-size: 15px;">Referral letter</h3>
        <?php if (!empty($todayReferral)): ?>
          <a class="btn ok" href="<?= h(app_url('/clinical/documents/' . (int) $todayReferral['id'])) ?>" target="_blank" rel="noopener" style="box-shadow:none;">View referral</a>
        <?php else: ?>
          <form method="POST" action="<?= h(app_route('/doctor/patients/' . (int) $patient['id'] . '/referral')) ?>">
            <label>Referred to</label>
            <input name="referred_to" required placeholder="Hospital, clinic, or specialist" />
            <label>Reason for referral</label>
            <textarea name="reason" rows="2" required placeholder="Why the patient needs referral"></textarea>
            <label>Clinical summary (optional)</label>
            <textarea name="clinical_summary" rows="2" placeholder="Defaults to today&apos;s diagnosis and notes"><?= h(trim(($todayConsultation['diagnosis'] ?? '') . (($todayConsultation['clinical_notes'] ?? '') !== '' ? ' — ' . ($todayConsultation['clinical_notes'] ?? '') : ''))) ?></textarea>
            <button class="btn ok" type="submit" style="margin-top: 10px; box-shadow: none;">Issue referral</button>
          </form>
        <?php endif; ?>
      </div>
      <div class="span-2" style="grid-column: 1 / -1; padding-top: 4px; border-top: 1px solid rgba(15,23,42,.08);">
        <h3 style="margin: 0 0 10px; font-size: 15px;">Clinical recommendation</h3>
        <?php if (!empty($todayRecommendation)): ?>
          <a class="btn ok" href="<?= h(app_url('/clinical/documents/' . (int) $todayRecommendation['id'])) ?>" target="_blank" rel="noopener" style="box-shadow:none;">View recommendation</a>
        <?php else: ?>
          <form method="POST" action="<?= h(app_route('/doctor/patients/' . (int) $patient['id'] . '/recommendation')) ?>">
            <label>Recommendation title</label>
            <input name="recommendation_title" value="Clinical recommendation" placeholder="e.g. Lifestyle advice, Follow-up care" />
            <label>Recommendation details</label>
            <textarea name="recommendation_text" rows="3" required placeholder="Advice, care instructions, or recommended actions for the patient"></textarea>
            <label>Follow-up notes (optional)</label>
            <textarea name="follow_up_notes" rows="2" placeholder="When to return, warning signs, etc."></textarea>
            <button class="btn ok" type="submit" style="margin-top: 10px; box-shadow: none;">Issue recommendation</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Doctor comments history</h2>
  <?php if (empty($comments)): ?>
    <p class="muted" style="margin: 0;">No comments yet.</p>
  <?php else: ?>
    <?php foreach ($comments as $c): ?>
      <div style="padding: 12px 0; border-bottom: 1px solid rgba(15,23,42,.06);">
        <div class="muted" style="font-size: 13px;">
          <strong><?= h(DoctorComment::doctorLabel($c)) ?></strong>
          &middot; <?= h($c['created_at']) ?>
        </div>
        <div style="margin-top: 6px;"><?= nl2br(h($c['comment'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Diagnosis history</h2>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Visit</th><th>Diagnosis</th><th>Doctor</th><th>Notes</th></tr>
      </thead>
      <tbody>
        <?php if (empty($consultations)): ?>
          <tr><td colspan="5" class="muted">No consultation records yet.</td></tr>
        <?php else: ?>
          <?php foreach ($consultations as $c): ?>
            <tr>
              <td><?= h($c['consultation_date']) ?></td>
              <td>
                <?php if (!empty($c['appointment_id'])): ?>
                  <span class="pill done" style="font-size:11px;">Follow-up</span>
                <?php else: ?>
                  <span class="muted">Walk-in</span>
                <?php endif; ?>
              </td>
              <td><strong><?= h($c['diagnosis']) ?></strong></td>
              <td class="muted"><?= h(trim((string) ($c['doctor_display_name'] ?? $c['doctor_username'] ?? '—'))) ?></td>
              <td class="muted"><?= h($c['clinical_notes'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Issued clinical documents</h2>
  <?php if (empty($clinicalDocuments)): ?>
    <p class="muted" style="margin: 0;">No saved clinical documents for this patient yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Document</th><th>Type</th><th>Issued</th><th>Visit</th><th>Summary</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($clinicalDocuments as $doc): ?>
            <?php
              $content = ClinicalDocument::decodeContent($doc);
              $docType = (string) ($doc['document_type'] ?? '');
              $summary = match ($docType) {
                  'medicine_receipt' => ((int) ($content['medicine_count'] ?? count($content['medicines'] ?? []))) . ' medicine item(s)',
                  'medical_certificate' => (string) ($content['purpose'] ?? 'Medical certificate'),
                  'referral' => 'To ' . (string) ($content['referred_to'] ?? 'facility'),
                  'recommendation' => (string) ($content['recommendation_title'] ?? 'Clinical recommendation'),
                  default => (string) ($doc['title'] ?? ''),
              };
            ?>
            <tr>
              <td><strong><?= h($doc['document_no']) ?></strong></td>
              <td><?= h(ClinicalDocument::typeLabel($docType)) ?></td>
              <td class="muted"><?= h(substr((string) ($doc['issued_at'] ?? ''), 0, 10)) ?></td>
              <td><?= h($content['consultation_date'] ?? '') ?></td>
              <td class="muted"><?= h($summary) ?></td>
              <td><a class="btn" href="<?= h(app_url('/clinical/documents/' . (int) $doc['id'])) ?>" target="_blank" rel="noopener" style="padding:6px 10px;font-size:12px;box-shadow:none;">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Medicine history</h2>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Medicine</th><th>Qty</th><th>Source</th><th>Status</th><th>Receipt</th></tr>
      </thead>
      <tbody>
        <?php if (empty($medicines)): ?>
          <tr><td colspan="6" class="muted">No medicines recorded.</td></tr>
        <?php else: ?>
          <?php foreach ($medicines as $m): ?>
            <tr>
              <td class="muted"><?= h(substr((string) ($m['created_at'] ?? ''), 0, 10)) ?></td>
              <td><?= h($m['medicine_name']) ?></td>
              <td><?= h((string) $m['quantity']) ?> <?= h($m['unit']) ?></td>
              <td class="muted"><?= h(MedicineDispensing::procurementShortLabel((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC))) ?></td>
              <td><span class="pill <?= ($m['dispense_status'] ?? '') === 'dispensed' ? 'done' : 'waiting' ?>"><?= h(strtoupper((string) ($m['dispense_status'] ?? ''))) ?></span></td>
              <td><?= !empty($m['receipt_issued']) ? 'Yes' : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <h2 style="margin: 0 0 8px;">Visit / queue history</h2>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th>Ticket</th><th>Station</th><th>Status</th><th>Reason</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="5" class="muted">No queue visits yet.</td></tr>
        <?php else: ?>
          <?php foreach ($tickets as $t): ?>
            <tr>
              <td><strong><?= h($t['ticket_no']) ?></strong></td>
              <td><?= h($t['station_name']) ?></td>
              <td><span class="pill <?= h($t['status']) ?>"><?= h(strtoupper((string) $t['status'])) ?></span></td>
              <td class="muted"><?= h($t['reason'] ?? '') ?></td>
              <td class="muted"><?= h($t['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .detail-list { margin: 0; }
  .detail-list > div { display: grid; grid-template-columns: 140px 1fr; gap: 8px; padding: 6px 0; border-bottom: 1px solid rgba(15,23,42,.06); }
  .detail-list > div:last-child { border-bottom: 0; }
  .detail-list dt { margin: 0; font-weight: 600; color: var(--muted); font-size: 13px; }
  .detail-list dd { margin: 0; }
</style>
