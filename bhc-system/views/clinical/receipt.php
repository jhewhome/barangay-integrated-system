<?php
$document = $document ?? null;
$consultation = $consultation ?? [];
$medicines = $medicines ?? [];
$liveSnapshot = $liveSnapshot ?? null;
$isPreview = empty($document);

if ($document) {
    $content = ClinicalDocument::decodeContent($document);
    if (is_array($liveSnapshot)) {
        $content = array_merge($content, $liveSnapshot);
    }
    $consultation = array_merge($consultation, [
        'patient_id' => (int) ($content['patient_id'] ?? $document['patient_id'] ?? 0),
        'bhc_id' => $content['bhc_id'] ?? '',
        'full_name' => $content['full_name'] ?? '',
        'consultation_date' => $content['consultation_date'] ?? '',
        'diagnosis' => $content['diagnosis'] ?? '',
        'clinical_notes' => $content['clinical_notes'] ?? '',
        'recorded_by_name' => $content['issued_by_name']
            ?? $content['recorded_by_name']
            ?? trim((string) ($document['issued_by_display_name'] ?? $document['issued_by_username'] ?? 'Staff')),
        'doctor_name' => $content['doctor_name'] ?? '',
    ]);
    $medicines = $content['medicines'] ?? [];
    $documentNo = (string) ($document['document_no'] ?? '');
    $issuedAt = (string) ($document['issued_at'] ?? '');
    $hasDispensed = !empty($content['has_dispensed']);
    $hasExternalProcurement = !empty($content['has_external_procurement']);
    $receiptTitle = ClinicalDocument::medicineReceiptTitle($content);
} else {
    $documentNo = '';
    $issuedAt = '';
    $receiptTitle = 'Medicine receipt preview';
    $hasDispensed = !empty(array_filter(
        $medicines,
        fn ($m) => ($m['dispense_status'] ?? '') === 'dispensed'
            && MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC)) === MedicineDispensing::SOURCE_CLINIC
    ));
    $hasExternalProcurement = !empty(array_filter(
        $medicines,
        fn ($m) => in_array(
            MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC)),
            [MedicineDispensing::SOURCE_LGU, MedicineDispensing::SOURCE_EXTERNAL],
            true
        )
    ));
    $consultation['doctor_name'] = $consultation['doctor_name'] ?? '';
}

$sectionTitle = match (true) {
    $hasDispensed && $hasExternalProcurement => 'Medicines prescribed and dispensed',
    $hasDispensed => 'Medicines dispensed from clinic stock',
    $hasExternalProcurement => 'Medicines to obtain from LGU or pharmacy',
    default => 'Medicines prescribed',
};

$showPatientSignature = $hasDispensed;
$signatureColumns = $showPatientSignature ? 3 : 2;

$doctorName = trim((string) ($consultation['doctor_name'] ?? ''));
if ($doctorName === '') {
    $dn = trim((string) ($consultation['doctor_display_name'] ?? $consultation['doctor_username'] ?? ''));
    if ($dn !== '') {
        $doctorName = str_starts_with(strtolower($dn), 'dr') ? $dn : 'Dr. ' . $dn;
    }
}

$visitDate = format_appt_date($consultation['consultation_date'] ?? date('Y-m-d'));
$issuedLabel = $issuedAt !== '' ? format_datetime($issuedAt) : '';
$clinicalNotes = trim((string) ($consultation['clinical_notes'] ?? ''));
$basePath = app_base_path();
$logoPath = '/assets/bhs_logo_v3.png';
$uLogo = ($basePath === '' ? '' : $basePath) . $logoPath;
$root = defined('BHC_ROOT') ? BHC_ROOT : dirname(__DIR__, 2);
$logoCandidates = [
    $root . '/public' . $logoPath,
    $root . $logoPath,
    dirname($root) . '/public' . $logoPath,
];
$hasLogo = false;
foreach ($logoCandidates as $candidate) {
    if (is_file($candidate)) {
        $hasLogo = true;
        break;
    }
}
?>

<article class="receipt-sheet">
  <header class="receipt-header">
    <?php if ($hasLogo): ?>
      <img class="receipt-logo" src="<?= h($uLogo) ?>" alt="BHC Logo" />
    <?php endif; ?>
    <div class="receipt-org">Barangay Health Center</div>
    <div class="receipt-subtitle">Brgy. Balong Bato, San Juan City</div>
    <div class="receipt-doc-title"><?= h($receiptTitle) ?></div>
    <?php if ($documentNo !== ''): ?>
      <div class="receipt-subtitle" style="margin-top: 8px;">
        Document no. <strong><?= h($documentNo) ?></strong>
      </div>
    <?php endif; ?>
  </header>

  <table class="receipt-meta">
    <colgroup>
      <col class="col-label" />
      <col class="col-value" />
    </colgroup>
    <tr>
      <td>Patient name</td>
      <td><strong><?= h($consultation['full_name'] ?? '') ?></strong></td>
    </tr>
    <tr>
      <td>BHC ID</td>
      <td><?= h($consultation['bhc_id'] ?? '') ?></td>
    </tr>
    <tr>
      <td>Visit date</td>
      <td><?= h($visitDate) ?></td>
    </tr>
    <?php if ($issuedLabel !== ''): ?>
      <tr>
        <td>Issued on</td>
        <td><?= h($issuedLabel) ?></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td>Diagnosis</td>
      <td><?= h($consultation['diagnosis'] ?? '—') ?></td>
    </tr>
    <?php if ($doctorName !== ''): ?>
      <tr>
        <td>Attending doctor</td>
        <td><?= h($doctorName) ?></td>
      </tr>
    <?php endif; ?>
    <?php if ($clinicalNotes !== ''): ?>
      <tr>
        <td>Clinical notes</td>
        <td><?= nl2br(h($clinicalNotes)) ?></td>
      </tr>
    <?php endif; ?>
  </table>

  <div class="receipt-section-title">
    <?= h($sectionTitle) ?>
  </div>

  <?php if (empty($medicines)): ?>
    <p class="receipt-subtitle">No medicines on record for this visit.</p>
  <?php else: ?>
    <table class="receipt-table">
      <colgroup>
        <col class="col-no" />
        <col class="col-medicine" />
        <col class="col-source" />
        <col class="col-qty" />
        <col class="col-unit" />
        <col class="col-status" />
      </colgroup>
      <thead>
        <tr>
          <th class="col-no">#</th>
          <th class="col-medicine">Medicine</th>
          <th class="col-source">Source</th>
          <th class="col-qty">Qty</th>
          <th class="col-unit">Unit</th>
          <th class="col-status">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($medicines as $i => $m): ?>
          <?php
            $source = MedicineDispensing::normalizeProcurementSource((string) ($m['procurement_source'] ?? MedicineDispensing::SOURCE_CLINIC));
            $sourceLabel = (string) ($m['procurement_label'] ?? MedicineDispensing::procurementLabel($source));
            $status = strtoupper((string) ($m['dispense_status'] ?? ''));
            if ($source !== MedicineDispensing::SOURCE_CLINIC && $status === 'PRESCRIBED') {
                $status = 'TO OBTAIN';
            }
          ?>
          <tr>
            <td class="col-no"><?= (int) $i + 1 ?></td>
            <td class="col-medicine"><?= h($m['medicine_name'] ?? '') ?></td>
            <td class="col-source"><?= h($sourceLabel) ?></td>
            <td class="col-qty"><?= h(format_medicine_qty($m['quantity'] ?? '')) ?></td>
            <td class="col-unit"><?= h($m['unit'] ?? '') ?></td>
            <td class="col-status"><?= h($status) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="receipt-signatures receipt-signatures--cols-<?= (int) $signatureColumns ?>">
    <div class="receipt-signature-block">
      <div class="receipt-subtitle">Attending physician signature</div>
      <div class="sig-line"></div>
      <?php if ($doctorName !== ''): ?>
        <div class="sig-name"><?= h($doctorName) ?></div>
      <?php else: ?>
        <div class="sig-name sig-name--placeholder">Physician name</div>
      <?php endif; ?>
    </div>
    <div class="receipt-signature-block">
      <div class="receipt-subtitle">Prepared by (BHC staff)</div>
      <div class="sig-line"></div>
      <div class="sig-name"><?= h('Barangay Health Center') ?></div>
    </div>
    <?php if ($showPatientSignature): ?>
      <div class="receipt-signature-block">
        <div class="receipt-subtitle">Patient signature</div>
        <div class="sig-line"></div>
        <div class="sig-name sig-name--placeholder">Received medicines from clinic stock</div>
      </div>
    <?php endif; ?>
  </div>

  <p class="receipt-footnote">
    This document is generated by the <?= h(app_name()) ?>.
    <?php if ($hasExternalProcurement): ?>
      Items marked for LGU request or external purchase are prescriptions only — they were not dispensed from clinic stock.
      Present this receipt when requesting medicines from the LGU or buying at a boutique/pharmacy.
    <?php endif; ?>
    <?php if ($documentNo !== ''): ?>
      Saved clinical document — reprint anytime from patient history.
    <?php endif; ?>
  </p>

  <?php if ($isPreview): ?>
    <div class="receipt-preview-note no-print">
      Preview only. Issue the receipt from patient history to save a permanent copy with a document number.
    </div>
  <?php endif; ?>
</article>
