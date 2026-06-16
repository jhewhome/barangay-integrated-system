<?php
$p = $old ?? [];
$gawadImport = $gawadImport ?? null;
$gawadError = $gawadError ?? null;
$identityMatch = $identityMatch ?? null;
$patientsIndexUrl = app_url('/patients');
$patientsStoreUrl = app_route('/patients');
$patientsSearchUrl = app_url('/patients/search');
$queueRegistrationUrl = app_url('/queue/1');
$canLinkGawad = !empty($gawadImport['id'])
    && !empty($identityMatch)
    && empty($identityMatch['gawad_resident_id'])
    && empty($gawadError);
$identityHistoryUrl = !empty($identityMatch['id'])
    ? app_url('/patients/' . (int) $identityMatch['id'] . '/history')
    : null;
$identityQueueUrl = !empty($identityMatch['id'])
    ? app_url('/queue/1?patient_id=' . (int) $identityMatch['id'])
    : null;
$linkGawadUrl = ($canLinkGawad && !empty($identityMatch['id']))
    ? app_route('/patients/' . (int) $identityMatch['id'] . '/link-gawad')
    : null;
$isAdmin = !empty($isAdmin);
$emergencyWalkIn = !empty($emergencyWalkIn);
$emergencyReason = trim((string) ($emergencyReason ?? ''));
$pageTitle = $emergencyWalkIn ? 'Emergency walk-in registration' : 'Register New Patient';
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1><?= h($pageTitle) ?></h1>
    <div class="muted">
      Assigned BHC ID: <strong><?= htmlspecialchars($bhcId) ?></strong>.
      <?php if ($emergencyWalkIn): ?>
        Document why this patient could not be registered in Gawad BIS first. Add them to BIS when possible.
      <?php elseif (!empty($gawadImport)): ?>
        Linked from Gawad BIS. Confirm residency, then save.
      <?php elseif ($isAdmin): ?>
        Admin registration — prefer Gawad BIS when the resident already exists there.
      <?php else: ?>
        New patients must verify Balong Bato residency from a supporting document before routing.
      <?php endif; ?>
    </div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h($patientsIndexUrl) ?>">Back</a>
  </div>
</div>

<?php if ($emergencyWalkIn): ?>
  <div class="card notice warn" style="margin-top: 14px; border-left: 4px solid #d97706;" role="status">
    <strong>Emergency walk-in only</strong>
    <div class="muted" style="margin-top: 6px;">Use this path when the patient needs immediate Health Center care and cannot be registered in Gawad BIS first. The reason is recorded on the patient file.</div>
  </div>
<?php elseif (!$isAdmin && empty($gawadImport) && !empty($gawadResidentsUrl)): ?>
  <div class="card" style="margin-top: 14px; border-left: 4px solid var(--brand, #2563eb);">
    <strong>Standard registration is via Gawad BIS</strong>
    <div class="muted" style="margin-top: 6px;">Open a resident in Gawad BIS and use <strong>Register at Health Center</strong> to prefill this form with a resident link.</div>
    <div class="row-actions" style="margin-top: 10px;">
      <a class="btn" href="<?= h($gawadResidentsUrl) ?>" target="_blank" rel="noopener noreferrer">Open Gawad BIS Residents</a>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($gawadImport)): ?>
  <div class="card" style="margin-top: 14px; border-left: 4px solid var(--ok, #16a34a);">
    <strong>Importing from Gawad BIS</strong>
    <div class="muted" style="margin-top: 6px;">
      Resident: <strong><?= h($gawadImport['name'] ?? 'Resident') ?></strong>.
      Review the prefilled fields, confirm residency, then save to link this resident to BHC.
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($gawadError)): ?>
  <div class="error" style="margin-top: 14px;"><?= htmlspecialchars($gawadError) ?></div>
<?php endif; ?>

<?php if (!empty($identityMatch)): ?>
  <div class="card notice warn" style="margin-top: 14px; border-left: 4px solid #d97706;" role="alert">
    <strong>Existing BHC patient matches this person</strong>
    <div class="muted" style="margin-top: 6px;">
      <strong><?= h($identityMatch['bhc_id'] ?? '') ?></strong>
      — <?= h($identityMatch['full_name'] ?? '') ?>
      · <?= h($identityMatch['sex'] ?? '') ?>
      · <?= h($identityMatch['birthdate'] ?? '') ?>
      <?php if (!empty($identityMatch['contact_number'])): ?>
        · <?= h($identityMatch['contact_number']) ?>
      <?php endif; ?>
    </div>
    <div class="row form-actions" style="margin-top: 12px; flex-wrap: wrap; gap: 8px;">
      <?php if ($canLinkGawad && $linkGawadUrl): ?>
        <form method="POST" action="<?= h($linkGawadUrl) ?>" style="margin:0;">
          <input type="hidden" name="gawad_resident_id" value="<?= h($gawadImport['id']) ?>" />
          <button class="btn ok" type="submit">Link Gawad resident to this record</button>
        </form>
      <?php endif; ?>
      <?php if ($identityHistoryUrl): ?>
        <a class="btn" href="<?= h($identityHistoryUrl) ?>">Open existing record</a>
      <?php endif; ?>
      <?php if ($identityQueueUrl): ?>
        <a class="btn" href="<?= h($identityQueueUrl) ?>">Route at Registration</a>
      <?php endif; ?>
    </div>
    <div class="muted" style="margin-top: 10px;">
      <?php if ($canLinkGawad): ?>
        This walk-in record is not linked to Gawad BIS yet. Link instead of creating a duplicate.
      <?php else: ?>
        Do not create a new record unless you confirmed this is a different person.
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="error" style="margin-top: 14px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= h($patientsStoreUrl) ?>" style="margin-top: 14px;">
  <?php if ($emergencyWalkIn): ?>
    <input type="hidden" name="emergency_walk_in" value="1" />
  <?php endif; ?>
  <?php if (!empty($gawadImport['id'])): ?>
    <input type="hidden" name="gawad_resident_id" value="<?= h($gawadImport['id']) ?>" />
    <input type="hidden" name="gawad_resident_name" value="<?= h($gawadImport['name'] ?? '') ?>" />
  <?php endif; ?>
  <div class="card">
    <?php if ($emergencyWalkIn): ?>
      <div style="margin-bottom: 14px;">
        <label for="emergencyReasonInput">Emergency reason <span class="required-mark">(required)</span></label>
        <textarea id="emergencyReasonInput" name="emergency_reason" rows="3" required minlength="10" placeholder="Example: Unconscious patient brought in by barangay tanod; no valid ID or time to register in BIS."><?= h($emergencyReason) ?></textarea>
      </div>
    <?php endif; ?>
    <?php
      $requireResidencyDecision = true;
      $enableDuplicateSuggest = true;
      require __DIR__ . '/../partials/patient_form.php';
    ?>

    <div class="muted" style="margin-top: 12px;">
      If the patient already exists, pick them from the suggestions under the name fields instead of creating a duplicate.
    </div>
    <div class="row form-actions" style="margin-top: 14px;">
      <button class="btn ok" type="submit">Save patient record</button>
    </div>
  </div>
</form>

<script>
  (function () {
    var first = document.getElementById('patientFirstName');
    var middle = document.getElementById('patientMiddleName');
    var last = document.getElementById('patientLastName');
    var dobInput = document.getElementById('patientDobInput');
    var contactInput = document.getElementById('patientContactInput');
    var results = document.getElementById('patientDuplicateResults');
    var backdrop = document.getElementById('patientDuplicateBackdrop');
    if (!first || !last || !results) return;

    var nameFields = [first, middle, last].filter(Boolean);
    var timer = null;
    var xhrActive = null;
    var requestToken = 0;
    var isOpen = false;

    function clearResults() {
      isOpen = false;
      results.style.display = 'none';
      while (results.firstChild) results.removeChild(results.firstChild);
      if (backdrop) backdrop.style.display = 'none';
    }

    function buildQuery() {
      var parts = [];
      if (first.value.trim()) parts.push(first.value.trim());
      if (middle && middle.value.trim()) parts.push(middle.value.trim());
      if (last.value.trim()) parts.push(last.value.trim());
      return parts.join(' ');
    }

    function shouldSearch() {
      var fn = first.value.trim();
      var ln = last.value.trim();
      var q = buildQuery();
      var contact = contactInput ? contactInput.value.trim() : '';
      var dob = dobInput ? dobInput.value : '';
      if (contact.length >= 4) return true;
      if (dob && (fn.length >= 1 || ln.length >= 1)) return true;
      if (fn.length >= 2 && ln.length >= 2) return true;
      if (q.length >= 3) return true;
      return false;
    }

    function anchorField() {
      var active = document.activeElement;
      for (var i = 0; i < nameFields.length; i++) {
        if (nameFields[i] === active) return nameFields[i];
      }
      if (last.value.trim()) return last;
      if (first.value.trim()) return first;
      return last;
    }

    function positionResults() {
      if (!isOpen) return;
      var anchor = anchorField();
      if (!anchor) return;
      var rect = anchor.getBoundingClientRect();
      var vw = window.innerWidth || document.documentElement.clientWidth || 0;
      var width = Math.max(rect.width, 280);
      width = Math.min(width, vw - 32);
      var left = Math.max(16, Math.min(rect.left, vw - width - 16));
      results.style.position = 'fixed';
      results.style.left = left + 'px';
      results.style.width = width + 'px';
      results.style.right = 'auto';
      results.style.top = (rect.bottom + 6) + 'px';
      results.style.zIndex = '40';
    }

    function render(list) {
      while (results.firstChild) results.removeChild(results.firstChild);
      if (!list || list.length === 0) { clearResults(); return; }

      for (var i = 0; i < list.length; i++) {
        (function (p) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'auto-item';
          btn.innerHTML =
            (p.full_name || '') +
            '<span class="auto-sub">' +
            (p.bhc_id || '') + ' • ' + (p.sex || '') + ' • ' + (p.birthdate || '') +
            ' — Click to use existing & route' +
            '</span>';
          btn.onmousedown = function (e) {
            if (e && e.preventDefault) e.preventDefault();
          };
          btn.onclick = function () {
            clearResults();
            window.location.href = <?= json_encode($queueRegistrationUrl, JSON_UNESCAPED_SLASHES) ?> + '?patient_id=' + encodeURIComponent(p.id);
          };
          results.appendChild(btn);
        })(list[i]);
      }
      isOpen = true;
      results.style.display = 'block';
      if (backdrop) backdrop.style.display = 'block';
      positionResults();
    }

    function fetchMatches() {
      if (!shouldSearch()) { clearResults(); return; }

      var q = buildQuery();
      var dob = dobInput ? (dobInput.value || '') : '';
      var contact = contactInput ? (contactInput.value || '').trim() : '';
      var token = ++requestToken;

      if (xhrActive) {
        try { xhrActive.abort(); } catch (e) {}
        xhrActive = null;
      }

      var url = <?= json_encode($patientsSearchUrl, JSON_UNESCAPED_SLASHES) ?> + '?q=' + encodeURIComponent(q);
      if (dob) url += '&birthdate=' + encodeURIComponent(dob);
      if (contact) url += '&contact=' + encodeURIComponent(contact);

      var xhr = new XMLHttpRequest();
      xhrActive = xhr;
      xhr.open('GET', url, true);
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhrActive === xhr) xhrActive = null;
        if (token !== requestToken) return;
        if (xhr.status !== 200) { clearResults(); return; }
        try { render(JSON.parse(xhr.responseText)); } catch (e) { clearResults(); }
      };
      xhr.send();
    }

    function schedule() {
      if (!shouldSearch()) {
        if (timer) clearTimeout(timer);
        clearResults();
        return;
      }
      if (timer) clearTimeout(timer);
      timer = setTimeout(fetchMatches, 250);
    }

    function onKeyDown(e) {
      e = e || window.event;
      var key = e.key || e.keyCode;
      if (key === 'Escape' || key === 27) {
        clearResults();
        if (e.preventDefault) e.preventDefault();
      }
    }

    nameFields.forEach(function (el) {
      el.addEventListener('input', schedule);
      el.addEventListener('change', schedule);
      el.addEventListener('keydown', onKeyDown);
      el.addEventListener('focus', function () {
        if (isOpen) positionResults();
      });
    });
    [dobInput, contactInput].forEach(function (el) {
      if (!el) return;
      el.addEventListener('input', schedule);
      el.addEventListener('change', schedule);
      el.addEventListener('keydown', onKeyDown);
    });

    if (backdrop) {
      backdrop.onmousedown = function (e) {
        if (e && e.preventDefault) e.preventDefault();
        clearResults();
      };
    }

    window.addEventListener('resize', positionResults);
    window.addEventListener('scroll', positionResults, true);
  })();
</script>
