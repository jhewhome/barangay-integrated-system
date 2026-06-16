<?php
$medicines = $medicines ?? [];
$pickerItems = $pickerItems ?? [];
$pickerSource = (string) ($pickerSource ?? 'local');
$pickerCount = (int) ($pickerCount ?? 0);
$gawadPickerError = $gawadPickerError ?? null;
$gawadMedicinesUrl = $gawadMedicinesUrl ?? GawadIntegration::medicinesInventoryUrl();
$usingGawadPicker = $pickerSource === 'gawad';
?>

<div class="row row-between page-header">
  <div class="row-body">
    <div class="pill serving">Admin</div>
    <h1 style="margin-top: 10px;">Medicine list</h1>
    <div class="muted">
      <?php if ($usingGawadPicker): ?>
        Prescription and pharmacy pickers use the live catalog from <strong>Gawad BIS</strong>
        (<?= (int) $pickerCount ?> active medicine<?= $pickerCount === 1 ? '' : 's' ?> with stock levels).
        Stock is read-only here — manage inventory in Gawad.
      <?php else: ?>
        Common medicines for faster prescribing and dispensing.
        <?php if (GawadIntegration::isMedicineSyncEnabled() && $gawadPickerError): ?>
          Gawad sync is enabled but unavailable right now — pickers fall back to this local list.
        <?php elseif (GawadIntegration::isEnabled()): ?>
          Enable medicine sync in integration config to load the Gawad inventory into BHC pickers.
        <?php else: ?>
          Stock levels and dispensing inventory are managed in Gawad BIS when integration is enabled.
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="row-actions">
    <?php if ($gawadMedicinesUrl): ?>
      <a class="btn" href="<?= h($gawadMedicinesUrl) ?>" target="_blank" rel="noopener noreferrer">Open inventory (Gawad)</a>
    <?php endif; ?>
    <?php if (!$usingGawadPicker): ?>
      <a class="btn ok" href="<?= h(app_url('/medicines/create')) ?>">Add medicine</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($usingGawadPicker): ?>
  <div class="notice ok-banner" style="margin-top: 14px;" role="status">
    <strong>Gawad medicine sync active.</strong>
    Consultation and pharmacy forms show Gawad stock levels and warn when quantity exceeds available stock.
    Dispensing in BHC does not reduce Gawad inventory yet (read-only integration).
  </div>
<?php elseif (GawadIntegration::isMedicineSyncEnabled() && $gawadPickerError): ?>
  <div class="error flash-banner" style="margin-top: 14px;" role="status">
    Could not load medicines from Gawad BIS: <?= h((string) $gawadPickerError) ?>
  </div>
<?php endif; ?>

<?php if ($usingGawadPicker): ?>
  <div class="card" style="margin-top: 14px;">
    <h2 style="margin: 0 0 10px;">Live picker catalog (from Gawad)</h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Medicine</th>
            <th>Unit</th>
            <th>Stock</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pickerItems as $m): ?>
            <tr>
              <td><strong><?= h($m['name']) ?></strong></td>
              <td class="muted"><?= h($m['default_unit']) ?></td>
              <td><?= h(format_medicine_qty($m['stock_qty'])) ?></td>
              <td>
                <?php if (!empty($m['is_out_of_stock'])): ?>
                  <span class="pill skipped">Out of stock</span>
                <?php elseif (!empty($m['is_low_stock'])): ?>
                  <span class="pill waiting">Low stock</span>
                <?php else: ?>
                  <span class="pill done">Available</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <div class="card" style="margin-top: 14px;">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Medicine</th>
            <th>Default unit</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($medicines)): ?>
            <tr><td colspan="4" class="muted">No medicines in the list yet.</td></tr>
          <?php else: ?>
            <?php foreach ($medicines as $m): ?>
              <tr>
                <td><strong><?= h($m['name']) ?></strong></td>
                <td class="muted"><?= h($m['default_unit']) ?></td>
                <td>
                  <?php if ((int) ($m['is_active'] ?? 0) === 1): ?>
                    <span class="pill done">Active</span>
                  <?php else: ?>
                    <span class="pill skipped">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a class="btn" href="<?= h(app_url('/medicines/' . (int) $m['id'] . '/edit')) ?>" style="box-shadow:none; padding:6px 10px; font-size:12px;">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
