<?php
$isEdit = !empty($isEdit);
$action = $isEdit ? app_route('/medicines/' . (int) ($old['id'] ?? 0)) : app_route('/medicines');
$title = $isEdit ? 'Edit medicine' : 'Add medicine';
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1><?= h($title) ?></h1>
    <div class="muted">Appears in consultation, pharmacy, and patient history pickers. Stock is not tracked here.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(app_url('/medicines')) ?>">Back to list</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="error" style="margin-top: 14px;">
    <ul style="margin: 0; padding-left: 20px;">
      <?php foreach ($errors as $e): ?>
        <li><?= h($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="<?= h($action) ?>" class="card" style="margin-top: 14px;">
  <label>Medicine name</label>
  <input name="name" required value="<?= h($old['name'] ?? '') ?>" placeholder="e.g. Paracetamol 500mg" />

  <label>Default unit</label>
  <select name="default_unit">
    <?php
      $units = ['tablet(s)', 'capsule(s)', 'bottle(s)', 'sachet(s)', 'ml', 'tube(s)', 'pcs'];
      $selectedUnit = (string) ($old['default_unit'] ?? 'tablet(s)');
      foreach ($units as $unit):
    ?>
      <option value="<?= h($unit) ?>" <?= $selectedUnit === $unit ? 'selected' : '' ?>><?= h($unit) ?></option>
    <?php endforeach; ?>
  </select>

  <label style="display:flex; align-items:center; gap:8px; margin-top: 12px;">
    <input type="checkbox" name="is_active" value="1" <?= !isset($old['is_active']) || $old['is_active'] === '1' || (int) ($old['is_active'] ?? 1) === 1 ? 'checked' : '' ?> />
    Active in medicine picker
  </label>

  <div class="form-submit-actions">
    <button class="btn ok" type="submit" style="box-shadow:none;"><?= $isEdit ? 'Save changes' : 'Add medicine' ?></button>
  </div>
</form>
