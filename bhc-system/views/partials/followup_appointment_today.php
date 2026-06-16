<?php
/** @var array<string,mixed>|null $appointment */
/** @var array<string,mixed>|null $patient */
/** @var bool $showPatient */
/** @var bool $showActions */
/** @var string|null $footerNote */
/** @var string $variant */
$appointment = $appointment ?? null;
if (!$appointment) {
    return;
}

$patient = $patient ?? null;
$showPatient = (bool) ($showPatient ?? false);
$showActions = (bool) ($showActions ?? true);
$footerNote = $footerNote ?? 'Saving a consultation for today will link and complete this appointment.';
$variant = (string) ($variant ?? 'default');

$apptId = (int) ($appointment['id'] ?? 0);
$patientId = (int) ($appointment['patient_id'] ?? ($patient['id'] ?? 0));
$purpose = trim((string) ($appointment['purpose'] ?? ''));
$stationName = trim((string) ($appointment['station_name'] ?? ''));
$notes = trim((string) ($appointment['notes'] ?? ''));
$timeLabel = format_appt_time($appointment['appointment_time'] ?? null);
$dateLabel = format_appt_date($appointment['appointment_date'] ?? null);
$isAnyTime = $timeLabel === 'Any time';

$patientName = trim((string) ($patient['full_name'] ?? $appointment['full_name'] ?? ''));
$bhcId = trim((string) ($patient['bhc_id'] ?? $appointment['bhc_id'] ?? ''));
$contact = trim((string) ($patient['contact_number'] ?? $appointment['contact_number'] ?? ''));
?>

<div class="followup-appt-card followup-appt-<?= h($variant) ?>">
  <div class="followup-appt-header">
    <div class="followup-appt-heading">
      <div class="followup-appt-icon" aria-hidden="true">📅</div>
      <div>
        <div class="followup-appt-title">Follow-up appointment today</div>
        <div class="followup-appt-subtitle"><?= h($dateLabel) ?></div>
      </div>
    </div>
    <div class="followup-appt-badges">
      <span class="pill waiting">Scheduled</span>
      <?php if ($apptId > 0): ?>
        <span class="pill" style="font-size: 11px;">#<?= $apptId ?></span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($showPatient && ($patientName !== '' || $bhcId !== '')): ?>
    <div class="followup-appt-patient">
      <div class="followup-appt-label">Patient</div>
      <div class="followup-appt-value">
        <?php if ($patientName !== ''): ?>
          <strong><?= h($patientName) ?></strong>
        <?php endif; ?>
        <?php if ($bhcId !== ''): ?>
          <span class="muted"><?= $patientName !== '' ? ' &middot; ' : '' ?><?= h($bhcId) ?></span>
        <?php endif; ?>
        <?php if ($contact !== ''): ?>
          <div class="muted" style="font-size: 12px; margin-top: 4px;">Contact: <?= h($contact) ?></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="followup-appt-grid">
    <div class="followup-appt-item">
      <div class="followup-appt-label">Time</div>
      <div class="followup-appt-value">
        <strong><?= h($timeLabel) ?></strong>
        <?php if ($isAnyTime): ?>
          <div class="muted" style="font-size: 12px; margin-top: 2px;">Walk-in window — no fixed slot</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="followup-appt-item">
      <div class="followup-appt-label">Purpose</div>
      <div class="followup-appt-value"><?= h($purpose !== '' ? $purpose : 'Scheduled follow-up') ?></div>
    </div>
    <div class="followup-appt-item">
      <div class="followup-appt-label">Preferred station</div>
      <div class="followup-appt-value">
        <?php if ($stationName !== ''): ?>
          <span aria-hidden="true"><?= station_icon($stationName) ?></span>
          <?= h($stationName) ?>
        <?php else: ?>
          <span class="muted">Not specified</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($notes !== ''): ?>
      <div class="followup-appt-item span-2">
        <div class="followup-appt-label">Appointment notes</div>
        <div class="followup-appt-value"><?= nl2br(h($notes)) ?></div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($footerNote !== ''): ?>
    <div class="followup-appt-footer">
      <span aria-hidden="true">ℹ️</span>
      <?= h($footerNote) ?>
    </div>
  <?php endif; ?>

  <?php if ($showActions && $patientId > 0): ?>
    <div class="row-actions row-actions-tight followup-appt-actions">
      <a class="btn" href="<?= h(app_url('/patients/' . (int) $patientId . '/history')) ?>" style="padding: 6px 10px; font-size: 12px; box-shadow: none;">Patient history</a>
      <a class="btn" href="<?= h(app_url('/appointments?from=' . date('Y-m-d') . '&to=' . date('Y-m-d'))) ?>" style="padding: 6px 10px; font-size: 12px; box-shadow: none;">Today&apos;s appointments</a>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/followup_appointment_styles.php'; ?>
