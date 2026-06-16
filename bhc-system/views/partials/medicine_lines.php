<?php
/** @var bool $showReceipt */
/** @var int $initialRows */
/** @var array<int,array<string,mixed>> $medicineCatalog */
$showReceipt = $showReceipt ?? false;
$initialRows = max(1, (int) ($initialRows ?? 1));
$prefix = (string) ($prefix ?? 'med');
$medicineCatalog = $medicineCatalog ?? [];
$listId = 'medicine-catalog-' . preg_replace('/[^a-z0-9_-]/i', '', $prefix);
$pickerUsesGawadStock = false;
foreach ($medicineCatalog as $catalogMed) {
    if (($catalogMed['source'] ?? '') === 'gawad') {
        $pickerUsesGawadStock = true;
        break;
    }
}
?>
<div class="medicine-lines" data-prefix="<?= htmlspecialchars($prefix) ?>" data-show-receipt="<?= $showReceipt ? '1' : '0' ?>" data-gawad-stock="<?= $pickerUsesGawadStock ? '1' : '0' ?>">
  <div class="medicine-lines-title">
    Medicines
    <span class="muted" style="font-weight: 400;">(optional — pick from medicine list or type freely<?= $showReceipt ? ', check Issued when given from clinic' : '' ?>)</span>
  </div>
  <p class="medicine-lines-help muted">
    <?php if ($pickerUsesGawadStock): ?>
      Medicine names and stock levels come from <strong>Gawad BIS inventory</strong> (read-only).
      Choose <strong>Clinic stock</strong> only when dispensing from barangay inventory.
    <?php else: ?>
      If a medicine is not available at the clinic, choose <strong>Request from LGU</strong> or <strong>Buy externally</strong>.
    <?php endif; ?>
    A prescription receipt is issued automatically when you save — the patient can use it to obtain medicines elsewhere.
  </p>
  <?php if (!empty($medicineCatalog)): ?>
    <datalist id="<?= h($listId) ?>">
      <?php foreach ($medicineCatalog as $catalogMed): ?>
        <?php
          $stockQty = $catalogMed['stock_qty'] ?? null;
          $stockAttr = $stockQty === null ? '' : (string) $stockQty;
        ?>
        <option
          value="<?= h($catalogMed['name']) ?>"
          data-id="<?= h((string) ($catalogMed['id'] ?? '')) ?>"
          data-gawad-id="<?= h((string) ($catalogMed['gawad_id'] ?? '')) ?>"
          data-unit="<?= h($catalogMed['default_unit']) ?>"
          data-stock="<?= h($stockAttr) ?>"
          data-low-stock="<?= !empty($catalogMed['is_low_stock']) ? '1' : '0' ?>"
          data-out-of-stock="<?= !empty($catalogMed['is_out_of_stock']) ? '1' : '0' ?>"
        ></option>
      <?php endforeach; ?>
    </datalist>
  <?php endif; ?>
  <div class="medicine-lines-head">
    <div class="medicine-line-fields medicine-line-fields--head">
      <div>Medicine name</div>
      <div>Source</div>
      <div>Qty</div>
      <div>Unit</div>
      <?php if ($showReceipt): ?><div>Receipt</div><?php endif; ?>
    </div>
    <div class="medicine-line-action-spacer" aria-hidden="true"></div>
  </div>
  <div class="medicine-lines-body">
    <?php for ($i = 0; $i < $initialRows; $i++): ?>
      <div class="medicine-line">
        <div class="medicine-line-main">
          <div class="medicine-line-fields">
            <div class="medicine-field medicine-field--name">
              <input
                class="medicine-name-input"
                name="medicine_name[]"
                <?= !empty($medicineCatalog) ? 'list="' . h($listId) . '"' : '' ?>
                placeholder="<?= !empty($medicineCatalog) ? 'Search medicine list…' : 'e.g., Paracetamol 500mg' ?>"
                autocomplete="off"
              />
              <input type="hidden" class="medicine-id-input" name="medicine_id[]" value="" />
            </div>
            <div class="medicine-field medicine-field--source">
              <select class="medicine-source-select" name="medicine_source[]">
                <option value="clinic">Clinic stock</option>
                <option value="lgu">Request from LGU</option>
                <option value="external">Buy externally</option>
              </select>
            </div>
            <div class="medicine-field medicine-field--qty">
              <input type="number" class="medicine-qty-input" name="medicine_quantity[]" min="1" step="1" inputmode="numeric" value="1" />
            </div>
            <div class="medicine-field medicine-field--unit">
              <select class="medicine-unit-select" name="medicine_unit[]">
                <option value="tablet(s)">tablet(s)</option>
                <option value="capsule(s)">capsule(s)</option>
                <option value="bottle(s)">bottle(s)</option>
                <option value="sachet(s)">sachet(s)</option>
                <option value="ml">ml</option>
                <option value="tube(s)">tube(s)</option>
                <option value="pcs">pcs</option>
              </select>
            </div>
            <?php if ($showReceipt): ?>
              <div class="medicine-field medicine-field--receipt">
                <label class="medicine-receipt-label">
                  <input type="checkbox" class="medicine-receipt-cb" name="medicine_receipt[<?= $i ?>]" value="1" />
                  Issued
                </label>
              </div>
            <?php endif; ?>
          </div>
          <div class="medicine-line-hint">
            <div class="medicine-stock-hint muted"></div>
          </div>
        </div>
        <button type="button" class="medicine-remove-row" title="Remove this row">Remove</button>
      </div>
    <?php endfor; ?>
  </div>
  <div class="medicine-lines-toolbar">
    <button type="button" class="medicine-add-row">+ Add medicine row</button>
  </div>
</div>
<style>
  .medicine-lines {
    --med-cols: minmax(0, 1.5fr) minmax(132px, 1.1fr) 72px 104px;
    margin-top: 4px;
    padding: 12px 14px;
    border-radius: 12px;
    background: var(--surface2);
    border: 1px solid rgba(15, 23, 42, 0.08);
  }
  .medicine-lines[data-show-receipt="1"] {
    --med-cols: minmax(0, 1.5fr) minmax(132px, 1.1fr) 72px 104px auto;
  }
  .medicine-lines-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 6px;
  }
  .medicine-lines-help {
    font-size: 12px;
    margin: 0 0 10px;
    line-height: 1.45;
  }
  .medicine-line-fields {
    display: grid;
    grid-template-columns: var(--med-cols);
    gap: 8px;
    align-items: center;
  }
  .medicine-line-fields--head {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    align-items: end;
    padding-bottom: 2px;
  }
  .medicine-lines-head,
  .medicine-line {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 72px;
    gap: 8px;
    align-items: start;
  }
  .medicine-lines-head {
    margin-bottom: 6px;
  }
  .medicine-line {
    margin-bottom: 8px;
  }
  .medicine-line-main {
    min-width: 0;
  }
  .medicine-field {
    min-width: 0;
  }
  .medicine-field--qty input {
    -moz-appearance: textfield;
  }
  .medicine-field--qty input::-webkit-outer-spin-button,
  .medicine-field--qty input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  .medicine-field input,
  .medicine-field select {
    width: 100%;
    margin: 0;
  }
  .medicine-field--receipt {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-height: 42px;
  }
  .medicine-receipt-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    white-space: nowrap;
    margin: 0;
    color: var(--text);
    cursor: pointer;
  }
  .medicine-receipt-label input {
    width: auto;
    margin: 0;
  }
  .medicine-receipt-label.is-disabled {
    opacity: 0.45;
  }
  .medicine-line-hint {
    margin-top: 4px;
    min-height: 14px;
  }
  .medicine-stock-hint {
    font-size: 11px;
    line-height: 1.35;
  }
  .medicine-stock-hint.is-warning {
    color: #b45309;
    font-weight: 600;
  }
  .medicine-stock-hint.is-danger {
    color: #b91c1c;
    font-weight: 700;
  }
  .medicine-line-action-spacer {
    width: 72px;
  }
  .medicine-remove-row {
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    white-space: nowrap;
    color: #7a0f20;
    background: rgba(239, 62, 91, 0.08);
    border: 1px solid rgba(239, 62, 91, 0.25);
    border-radius: 10px;
    cursor: pointer;
    box-shadow: none;
    height: 42px;
    align-self: start;
  }
  .medicine-remove-row:hover {
    background: rgba(239, 62, 91, 0.14);
  }
  .medicine-remove-row:disabled {
    opacity: 0.35;
    cursor: default;
  }
  .medicine-lines-toolbar {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
  }
  .medicine-add-row {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    color: var(--pri);
    background: var(--surface);
    border: 1px dashed rgba(47, 107, 255, 0.45);
    border-radius: 10px;
    cursor: pointer;
  }
  .medicine-add-row:hover {
    background: rgba(47, 107, 255, 0.06);
    border-color: rgba(47, 107, 255, 0.55);
  }
  .medicine-add-row:focus {
    outline: 2px solid var(--ring);
    outline-offset: 2px;
  }
  .form-submit-actions {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(15, 23, 42, 0.08);
  }
  @media (max-width: 860px) {
    .medicine-lines {
      --med-cols: minmax(0, 1fr) minmax(0, 1fr);
    }
    .medicine-lines[data-show-receipt="1"] {
      --med-cols: minmax(0, 1fr) minmax(0, 1fr);
    }
    .medicine-line-fields--head {
      display: none;
    }
    .medicine-line-fields {
      grid-template-columns: 1fr 1fr;
    }
    .medicine-field--name,
    .medicine-field--source {
      grid-column: 1 / -1;
    }
    .medicine-lines-head,
    .medicine-line {
      grid-template-columns: 1fr;
    }
    .medicine-line-action-spacer {
      display: none;
    }
    .medicine-remove-row {
      width: 100%;
      height: auto;
    }
  }
</style>
<script>
  (function () {
    document.querySelectorAll('.medicine-lines').forEach(function (wrap) {
      var addBtn = wrap.querySelector('.medicine-add-row');
      var body = wrap.querySelector('.medicine-lines-body');
      var showReceipt = wrap.getAttribute('data-show-receipt') === '1';
      if (!addBtn || !body) return;

      function rowSource(row) {
        var sel = row.querySelector('.medicine-source-select');
        return sel ? sel.value : 'clinic';
      }

      var useGawadStock = wrap.getAttribute('data-gawad-stock') === '1';

      function formatStock(value) {
        var n = parseFloat(value);
        if (isNaN(n)) return value;
        if (Math.abs(n - Math.round(n)) < 0.001) return String(Math.round(n));
        return String(n);
      }

      function stockHintText(match, qty) {
        if (!match || !useGawadStock) return 'From clinic medicine list';
        var stockRaw = match.getAttribute('data-stock');
        if (stockRaw === '' || stockRaw === null) return 'From clinic medicine list';
        var stock = parseFloat(stockRaw);
        if (isNaN(stock)) return 'From clinic medicine list';
        var out = match.getAttribute('data-out-of-stock') === '1' || stock <= 0;
        var low = match.getAttribute('data-low-stock') === '1';
        var qtyNum = parseFloat(qty);
        if (out) {
          return 'Out of stock in Gawad inventory (' + formatStock(stock) + ' available)';
        }
        if (!isNaN(qtyNum) && qtyNum > stock) {
          return 'Requested qty exceeds Gawad stock (' + formatStock(stock) + ' available)';
        }
        if (low) {
          return 'Low stock in Gawad inventory (' + formatStock(stock) + ' available)';
        }
        return 'Gawad stock: ' + formatStock(stock) + ' available';
      }

      function applyStockHintStyle(hint, text) {
        if (!hint) return;
        hint.classList.remove('is-warning', 'is-danger');
        if (text.indexOf('Out of stock') === 0 || text.indexOf('Requested qty exceeds') === 0) {
          hint.classList.add(text.indexOf('Out of stock') === 0 ? 'is-danger' : 'is-warning');
        } else if (text.indexOf('Low stock') === 0) {
          hint.classList.add('is-warning');
        }
      }

      function reindexReceiptCheckboxes() {
        if (!showReceipt) return;
        var rows = body.querySelectorAll('.medicine-line');
        for (var i = 0; i < rows.length; i++) {
          var cb = rows[i].querySelector('.medicine-receipt-cb');
          if (cb) cb.name = 'medicine_receipt[' + i + ']';
        }
      }

      function updateRemoveButtons() {
        var rows = body.querySelectorAll('.medicine-line');
        var hideRemove = rows.length <= 1;
        for (var i = 0; i < rows.length; i++) {
          var btn = rows[i].querySelector('.medicine-remove-row');
          if (btn) {
            btn.style.visibility = hideRemove ? 'hidden' : 'visible';
            btn.disabled = hideRemove;
          }
        }
      }

      function catalogOptions() {
        var list = wrap.querySelector('datalist');
        if (!list) return [];
        return Array.prototype.slice.call(list.querySelectorAll('option'));
      }

      function syncRowSource(row) {
        var source = rowSource(row);
        var hint = row.querySelector('.medicine-stock-hint');
        var cb = row.querySelector('.medicine-receipt-cb');
        var label = row.querySelector('.medicine-receipt-label');
        var isClinic = source === 'clinic';

        if (cb) {
          if (!isClinic) {
            cb.checked = false;
            cb.disabled = true;
          } else {
            cb.disabled = false;
          }
        }
        if (label) {
          label.classList.toggle('is-disabled', !isClinic);
        }

        if (!isClinic && hint) {
          hint.textContent = source === 'lgu'
            ? 'Obtain via LGU request — prescription receipt recommended'
            : 'Buy at boutique/pharmacy — prescription receipt recommended';
        }
      }

      function syncRowFromCatalog(row) {
        var nameInput = row.querySelector('.medicine-name-input');
        var idInput = row.querySelector('.medicine-id-input');
        var unitSelect = row.querySelector('.medicine-unit-select');
        var qtyInput = row.querySelector('.medicine-qty-input');
        var hint = row.querySelector('.medicine-stock-hint');
        if (!nameInput || !idInput) return;

        var source = rowSource(row);
        if (source !== 'clinic') {
          syncRowSource(row);
          return;
        }

        var value = (nameInput.value || '').trim();
        var match = null;
        catalogOptions().forEach(function (opt) {
          if ((opt.value || '').trim() === value) match = opt;
        });

        if (match) {
          var gawadId = match.getAttribute('data-gawad-id') || '';
          var localId = match.getAttribute('data-id') || '';
          idInput.value = gawadId || localId;
          if (unitSelect) {
            var unit = match.getAttribute('data-unit') || '';
            if (unit) {
              var found = false;
              for (var u = 0; u < unitSelect.options.length; u++) {
                if (unitSelect.options[u].value === unit) {
                  unitSelect.selectedIndex = u;
                  found = true;
                  break;
                }
              }
              if (!found) unitSelect.value = unit;
            }
          }
          if (hint) {
            var hintText = stockHintText(match, qtyInput ? qtyInput.value : '1');
            hint.textContent = hintText;
            applyStockHintStyle(hint, hintText);
          }
        } else {
          idInput.value = '';
          if (hint) {
            hint.textContent = value ? 'Free text — not in medicine list' : '';
            hint.classList.remove('is-warning', 'is-danger');
          }
        }

        syncRowSource(row);
      }

      function validateStockBeforeSubmit() {
        if (!useGawadStock) return true;
        var issues = [];
        body.querySelectorAll('.medicine-line').forEach(function (row) {
          if (rowSource(row) !== 'clinic') return;
          var nameInput = row.querySelector('.medicine-name-input');
          var qtyInput = row.querySelector('.medicine-qty-input');
          if (!nameInput || !(nameInput.value || '').trim()) return;
          var match = null;
          catalogOptions().forEach(function (opt) {
            if ((opt.value || '').trim() === (nameInput.value || '').trim()) match = opt;
          });
          if (!match) return;
          var stock = parseFloat(match.getAttribute('data-stock') || '');
          var qty = parseFloat(qtyInput ? qtyInput.value : '1');
          if (isNaN(stock)) return;
          if (match.getAttribute('data-out-of-stock') === '1' || stock <= 0) {
            issues.push((nameInput.value || '').trim() + ' is out of stock in Gawad inventory.');
            return;
          }
          if (!isNaN(qty) && qty > stock) {
            issues.push((nameInput.value || '').trim() + ' quantity exceeds Gawad stock (' + formatStock(stock) + ' available).');
          }
        });
        if (issues.length === 0) return true;
        return confirm('Stock warning from Gawad inventory:\n\n- ' + issues.join('\n- ') + '\n\nSave anyway?');
      }

      function clearRow(row) {
        row.querySelectorAll('.medicine-name-input').forEach(function (el) { el.value = ''; });
        row.querySelectorAll('.medicine-id-input').forEach(function (el) { el.value = ''; });
        row.querySelectorAll('input[name="medicine_quantity[]"]').forEach(function (el) { el.value = '1'; });
        row.querySelectorAll('.medicine-unit-select').forEach(function (el) { el.selectedIndex = 0; });
        row.querySelectorAll('.medicine-source-select').forEach(function (el) { el.value = 'clinic'; });
        row.querySelectorAll('.medicine-receipt-cb').forEach(function (el) { el.checked = false; el.disabled = false; });
        row.querySelectorAll('.medicine-stock-hint').forEach(function (el) { el.textContent = ''; });
        row.querySelectorAll('.medicine-receipt-label').forEach(function (el) { el.classList.remove('is-disabled'); });
      }

      addBtn.addEventListener('click', function () {
        var first = body.querySelector('.medicine-line');
        if (!first) return;
        var clone = first.cloneNode(true);
        clearRow(clone);
        body.appendChild(clone);
        reindexReceiptCheckboxes();
        updateRemoveButtons();
      });

      body.addEventListener('click', function (e) {
        var target = e.target || e.srcElement;
        if (!target || !target.classList || !target.classList.contains('medicine-remove-row')) return;

        var rows = body.querySelectorAll('.medicine-line');
        if (rows.length <= 1) return;

        var row = target.closest ? target.closest('.medicine-line') : null;
        if (!row) return;
        row.parentNode.removeChild(row);
        reindexReceiptCheckboxes();
        updateRemoveButtons();
      });

      body.addEventListener('input', function (e) {
        var target = e.target || e.srcElement;
        if (!target || !target.classList) return;
        if (target.classList.contains('medicine-name-input') || target.classList.contains('medicine-qty-input')) {
          var row = target.closest ? target.closest('.medicine-line') : null;
          if (row) syncRowFromCatalog(row);
        }
      });

      body.addEventListener('change', function (e) {
        var target = e.target || e.srcElement;
        if (!target || !target.classList) return;
        var row = target.closest ? target.closest('.medicine-line') : null;
        if (!row) return;
        if (target.classList.contains('medicine-name-input') || target.classList.contains('medicine-source-select') || target.classList.contains('medicine-qty-input')) {
          syncRowFromCatalog(row);
        }
      });

      var form = wrap.closest('form');
      if (form) {
        form.addEventListener('submit', function (e) {
          if (!validateStockBeforeSubmit()) {
            e.preventDefault();
          }
        });
      }

      body.querySelectorAll('.medicine-line').forEach(syncRowFromCatalog);
      reindexReceiptCheckboxes();
      updateRemoveButtons();
    });
  })();
</script>
