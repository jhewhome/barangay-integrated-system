<?php
/**
 * Two-column signature block for clinical letters (referral, recommendation, certificate).
 *
 * @var string $issuedBy
 * @var string $doctorName
 * @var string $patientSignatureLabel
 */
$issuedBy = trim((string) ($issuedBy ?? ''));
$doctorName = trim((string) ($doctorName ?? ''));
$patientSignatureLabel = trim((string) ($patientSignatureLabel ?? 'Patient / guardian signature'));
?>
<div class="receipt-signatures receipt-signatures--cols-2">
  <div class="receipt-signature-block">
    <div class="receipt-subtitle">Issued by</div>
    <div class="sig-line sig-line--tall"></div>
    <?php if ($issuedBy !== ''): ?>
      <div class="sig-name"><?= h($issuedBy) ?></div>
    <?php else: ?>
      <div class="sig-name sig-name--placeholder">Staff name</div>
    <?php endif; ?>
    <?php if ($doctorName !== ''): ?>
      <div class="sig-name sig-name--role"><?= h($doctorName) ?></div>
    <?php endif; ?>
  </div>
  <div class="receipt-signature-block">
    <div class="receipt-subtitle"><?= h($patientSignatureLabel) ?></div>
    <div class="sig-line sig-line--tall"></div>
  </div>
</div>
