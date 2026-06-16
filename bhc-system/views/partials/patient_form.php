<?php
/** @var array<string,mixed> $p Merged patient + old input */
if (!function_exists('pf')) {
    function pf(string $key, array $p): string
    {
        return htmlspecialchars((string) ($p[$key] ?? ''));
    }
}
$civil = (string) ($p['civil_status'] ?? '');
$sex = (string) ($p['sex'] ?? '');
?>
<div class="grid cols-2">
  <div>
    <label>First name</label>
    <input id="patientFirstName" name="first_name" value="<?= pf('first_name', $p) ?>" required autocomplete="given-name" />
  </div>
  <div>
    <label>Middle name (optional)</label>
    <input id="patientMiddleName" name="middle_name" value="<?= pf('middle_name', $p) ?>" autocomplete="additional-name" />
  </div>
  <div>
    <label>Last name</label>
    <input id="patientLastName" name="last_name" value="<?= pf('last_name', $p) ?>" required autocomplete="family-name" />
  </div>
  <div>
    <label>Suffix (optional)</label>
    <input name="suffix" placeholder="Jr., Sr., III" value="<?= pf('suffix', $p) ?>" />
  </div>
  <?php if (!empty($enableDuplicateSuggest)): ?>
  <div class="span-2 auto-wrap" id="patientDuplicateWrap">
    <div class="muted small" id="patientDuplicateHint" style="margin: 0;">Possible duplicates appear as you type name, date of birth, or contact.</div>
    <div id="patientDuplicateBackdrop" class="auto-backdrop"></div>
    <div id="patientDuplicateResults" class="auto-results patient-duplicate-results" style="display:none;"></div>
  </div>
  <?php endif; ?>
  <div>
    <label>Sex</label>
    <select name="sex" required>
      <option value="" disabled <?= $sex === '' ? 'selected' : '' ?>>Select sex…</option>
      <option value="M" <?= $sex === 'M' ? 'selected' : '' ?>>Male</option>
      <option value="F" <?= $sex === 'F' ? 'selected' : '' ?>>Female</option>
    </select>
  </div>
  <div>
    <label>Date of birth</label>
    <input id="patientDobInput" type="date" name="birthdate" value="<?= pf('birthdate', $p) ?>" required />
  </div>
  <div>
    <label>Contact number (optional)</label>
    <input id="patientContactInput" name="contact_number" placeholder="e.g., 09xxxxxxxxx" value="<?= pf('contact_number', $p) ?>" />
  </div>
  <div>
    <label>PhilHealth no. (optional)</label>
    <input name="philhealth_no" placeholder="e.g., 12-345678901-2" value="<?= pf('philhealth_no', $p) ?>" />
  </div>
  <div class="span-2">
    <label>Address (optional)</label>
    <input name="address" placeholder="House no., street, subdivision" value="<?= pf('address', $p) ?>" />
  </div>
  <div>
    <label>Barangay</label>
    <input name="barangay" value="<?= pf('barangay', $p) !== '' ? pf('barangay', $p) : 'Balong Bato' ?>" />
  </div>
  <div>
    <label>Civil status (optional)</label>
    <select name="civil_status">
      <option value="" <?= $civil === '' ? 'selected' : '' ?>>—</option>
      <option value="single" <?= $civil === 'single' ? 'selected' : '' ?>>Single</option>
      <option value="married" <?= $civil === 'married' ? 'selected' : '' ?>>Married</option>
      <option value="widowed" <?= $civil === 'widowed' ? 'selected' : '' ?>>Widowed</option>
      <option value="separated" <?= $civil === 'separated' ? 'selected' : '' ?>>Separated</option>
    </select>
  </div>
  <div>
    <label>Emergency contact name (optional)</label>
    <input name="emergency_contact_name" value="<?= pf('emergency_contact_name', $p) ?>" />
  </div>
  <div>
    <label>Emergency contact phone (optional)</label>
    <input name="emergency_contact_phone" value="<?= pf('emergency_contact_phone', $p) ?>" />
  </div>
  <div class="span-2">
    <label>Notes (optional)</label>
    <textarea name="notes" rows="3" placeholder="Allergies, chronic conditions, or other registry notes"><?= pf('notes', $p) ?></textarea>
  </div>
  <?php
    $requireResidencyDecision = $requireResidencyDecision ?? false;
    require __DIR__ . '/patient_residency_fields.php';
  ?>
</div>
