<?php
/** @var array<string,mixed> $p */
$residencyStatus = Patient::normalizeResidencyStatus((string) ($p['residency_status'] ?? Patient::RESIDENCY_PENDING));
$proofType = (string) ($p['residency_proof_type'] ?? '');
$requireDecision = !empty($requireResidencyDecision);
$isExistingRecord = !$requireDecision && !Patient::requiresResidencyVerification($p);
?>
<div class="patient-residency-block span-2">
  <div class="patient-residency-title">Balong Bato residency</div>
  <p class="patient-residency-intro muted">
    <?php if ($requireDecision): ?>
      Brgy. staff require proof that the patient resides in <strong>Balong Bato</strong> before accepting them for BHC services.
      Check the document presented, then record the verification below.
    <?php elseif ($isExistingRecord): ?>
      This patient was registered before residency verification was required. You may optionally record proof below, but routing is not blocked.
    <?php else: ?>
      Check the document presented and update residency verification if needed.
    <?php endif; ?>
  </p>

  <div class="residency-choice-group" role="radiogroup" aria-label="Balong Bato residency verification">
    <label class="residency-choice<?= $residencyStatus === Patient::RESIDENCY_VERIFIED ? ' is-selected' : '' ?>">
      <input
        type="radio"
        class="residency-choice-input"
        name="residency_status"
        value="verified"
        <?= $residencyStatus === Patient::RESIDENCY_VERIFIED ? 'checked' : '' ?>
        <?= $requireDecision ? 'required' : '' ?>
      />
      <span class="residency-choice-body">
        <span class="residency-choice-title">Verified Balong Bato resident</span>
        <span class="residency-choice-desc muted">Patient presented acceptable proof of residence.</span>
      </span>
    </label>
    <label class="residency-choice<?= $residencyStatus === Patient::RESIDENCY_NON_RESIDENT ? ' is-selected' : '' ?>">
      <input
        type="radio"
        class="residency-choice-input"
        name="residency_status"
        value="non_resident"
        <?= $residencyStatus === Patient::RESIDENCY_NON_RESIDENT ? 'checked' : '' ?>
      />
      <span class="residency-choice-body">
        <span class="residency-choice-title">Not verified / non-resident</span>
        <span class="residency-choice-desc muted">No acceptable proof — do not route for regular BHC services.</span>
      </span>
    </label>
  </div>

  <?php if (!$requireDecision): ?>
    <label class="residency-choice residency-choice-pending<?= $residencyStatus === Patient::RESIDENCY_PENDING ? ' is-selected' : '' ?>">
      <input
        type="radio"
        class="residency-choice-input"
        name="residency_status"
        value="pending"
        <?= $residencyStatus === Patient::RESIDENCY_PENDING ? 'checked' : '' ?>
      />
      <span class="residency-choice-body">
        <span class="residency-choice-title">Pending verification</span>
        <span class="residency-choice-desc muted"><?= $isExistingRecord ? 'Existing record — verification optional.' : 'Legacy record or follow-up — confirm proof before routing.' ?></span>
      </span>
    </label>
  <?php endif; ?>

  <div class="residency-proof-fields grid cols-2">
    <div>
      <label for="residencyProofType">Residency document presented</label>
      <select name="residency_proof_type" id="residencyProofType">
        <option value="">Select document…</option>
        <?php foreach (Patient::residencyProofTypes() as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $proofType === $value ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="residencyProofNotes">Document details (optional)</label>
      <input id="residencyProofNotes" name="residency_proof_notes" value="<?= h((string) ($p['residency_proof_notes'] ?? '')) ?>" placeholder="e.g. ID no., date issued, issuing office" />
    </div>
  </div>
</div>

<style>
  .patient-residency-block {
    margin-top: 8px;
    padding-top: 14px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  .patient-residency-title {
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text);
  }
  .patient-residency-intro {
    font-size: 13px;
    margin: 0 0 12px;
    line-height: 1.45;
  }
  .residency-choice-group {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .patient-residency-block label.residency-choice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 0;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid rgba(15, 23, 42, 0.12);
    background: var(--surface);
    color: var(--text);
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
  }
  .patient-residency-block label.residency-choice:hover {
    border-color: rgba(47, 107, 255, 0.22);
    background: rgba(47, 107, 255, 0.03);
  }
  .patient-residency-block label.residency-choice.is-selected,
  .patient-residency-block label.residency-choice:has(.residency-choice-input:checked) {
    border-color: rgba(47, 107, 255, 0.38);
    background: rgba(47, 107, 255, 0.07);
    box-shadow: 0 0 0 1px rgba(47, 107, 255, 0.08);
  }
  .patient-residency-block label.residency-choice-pending {
    margin-top: 10px;
  }
  .residency-choice-input {
    width: 18px;
    height: 18px;
    min-width: 18px;
    margin-top: 2px;
  }
  .residency-choice-body {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
  }
  .residency-choice-title {
    display: block;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
    color: var(--text);
  }
  .residency-choice-desc {
    display: block;
    font-size: 12px;
    line-height: 1.4;
  }
  .residency-proof-fields {
    margin-top: 12px;
    gap: 12px;
  }
  .residency-proof-fields > div > label {
    margin-bottom: 6px;
  }
  @media (max-width: 768px) {
    .residency-choice-group {
      grid-template-columns: 1fr;
    }
  }
</style>

<script>
(function () {
  var block = document.querySelector('.patient-residency-block');
  if (!block) return;

  function syncSelectedState() {
    block.querySelectorAll('.residency-choice').forEach(function (choice) {
      var input = choice.querySelector('.residency-choice-input');
      choice.classList.toggle('is-selected', !!(input && input.checked));
    });
  }

  block.querySelectorAll('.residency-choice-input').forEach(function (input) {
    input.addEventListener('change', syncSelectedState);
  });
  syncSelectedState();
})();
</script>
