<?php
$page = (int) ($page ?? 1);
$perPage = (int) ($perPage ?? 20);
$total = (int) ($total ?? 0);
$totalActivePatients = (int) ($totalActivePatients ?? 0);
$totalPages = (int) ($totalPages ?? 1);
$q = trim((string) ($q ?? ''));
$residency = trim((string) ($residency ?? ''));
$registry = trim((string) ($registry ?? 'active'));
$isAdmin = !empty($isAdmin);
$start = (int) ($start ?? ($total === 0 ? 0 : (($page - 1) * $perPage + 1)));
$end = (int) ($end ?? min($total, $page * $perPage));
$patientsSearchUrl = url_path($basePath ?? '', '/patients');
?>

<div class="card page-header-card" style="padding: 16px 18px;">
  <div class="row row-between page-header">
    <div class="row-body">
      <div class="row row-pills" style="margin-bottom: 8px;">
        <div class="pill serving">Total patients: <strong><?= number_format($totalActivePatients) ?></strong></div>
        <?php if ($q !== '' || $residency !== '' || ($isAdmin && $registry !== 'active')): ?>
          <div class="pill">Matching filters: <strong><?= number_format($total) ?></strong></div>
        <?php endif; ?>
      </div>
      <h1 style="margin-bottom: 4px;">Patient Registry</h1>
      <div class="muted">BHC ID is auto-generated.<?php if ($isAdmin): ?> Admins may add patients directly or via Gawad BIS.<?php else: ?> New patients must be registered from <strong>Gawad BIS</strong> (Residents → Register at Health Center). Use <strong>Emergency walk-in</strong> only when BIS registration is not possible.<?php endif; ?> Balong Bato residency verification is required before routing.<?php if ($isAdmin): ?> Admins can archive patients to hide them from this list while keeping visit history.<?php endif; ?></div>
    </div>
    <div class="row-actions">
      <?php require __DIR__ . '/../partials/patient_register_actions.php'; ?>
    </div>
  </div>
</div>

<div class="card list-page-card">
  <form id="patientsSearchForm" method="GET" action="<?= h($patientsSearchUrl) ?>" class="row row-between list-search-bar patients-search-bar">
    <div style="flex: 1 1 220px; min-width: 0;">
      <label for="patientsSearchInput">Search patients</label>
      <input id="patientsSearchInput" type="search" name="q" value="<?= h($q) ?>" placeholder="Name, BHC ID, or contact…" autocomplete="off" />
    </div>
    <div style="flex: 0 1 200px; min-width: 0;">
      <label for="patientsResidencyFilter">Residency</label>
      <select id="patientsResidencyFilter" name="residency">
        <option value="">All statuses</option>
        <option value="verified" <?= $residency === 'verified' ? 'selected' : '' ?>>Verified resident</option>
        <option value="pending" <?= $residency === 'pending' ? 'selected' : '' ?>>Pending verification</option>
        <option value="non_resident" <?= $residency === 'non_resident' ? 'selected' : '' ?>>Not verified</option>
      </select>
    </div>
    <?php if ($isAdmin): ?>
    <div style="flex: 0 1 180px; min-width: 0;">
      <label for="patientsRegistryFilter">Registry</label>
      <select id="patientsRegistryFilter" name="registry">
        <option value="active" <?= $registry === 'active' ? 'selected' : '' ?>>Active patients</option>
        <option value="archived" <?= $registry === 'archived' ? 'selected' : '' ?>>Archived patients</option>
        <option value="all" <?= $registry === 'all' ? 'selected' : '' ?>>All patients</option>
      </select>
    </div>
    <?php endif; ?>
    <div class="row-actions row-actions-tight">
      <span id="patientsSearchStatus" class="patients-search-status muted" aria-live="polite" hidden></span>
      <button id="patientsClearBtn" class="btn" type="button" style="box-shadow:none;" <?= $q === '' && $residency === '' && $registry === 'active' ? 'hidden' : '' ?>>Clear</button>
    </div>
  </form>

  <div id="patientsRegistryResults">
    <?php require __DIR__ . '/partials/registry_results.php'; ?>
  </div>
</div>

<style>
  .patients-search-status {
    font-size: 13px;
    white-space: nowrap;
  }
  .patients-table-wrap.is-loading {
    opacity: 0.55;
    pointer-events: none;
    transition: opacity 0.15s ease;
  }
  .patients-table .col-sex,
  .patients-table .col-dob,
  .patients-table thead .col-sex,
  .patients-table thead .col-dob {
    width: 1%;
    white-space: nowrap;
    text-align: center;
  }
  .patients-table .col-name {
    width: 28%;
    min-width: 140px;
  }
  .patients-table .col-name a {
    font-weight: 600;
  }
  .patients-table .col-barangay,
  .patients-table .col-contact {
    word-break: break-word;
  }
  @media (max-width: 900px) {
    .patients-table {
      min-width: 680px;
    }
  }
</style>

<script>
(function () {
  var form = document.getElementById('patientsSearchForm');
  var input = document.getElementById('patientsSearchInput');
  var residencyFilter = document.getElementById('patientsResidencyFilter');
  var registryFilter = document.getElementById('patientsRegistryFilter');
  var clearBtn = document.getElementById('patientsClearBtn');
  var statusEl = document.getElementById('patientsSearchStatus');
  var resultsEl = document.getElementById('patientsRegistryResults');
  var searchUrl = <?= json_encode($patientsSearchUrl, JSON_UNESCAPED_SLASHES) ?>;
  if (!form || !input || !resultsEl) return;

  var timer = null;
  var requestId = 0;
  var currentPage = <?= (int) $page ?>;

  function bindRowMenus(root) {
    var menus = root.querySelectorAll('.appt-menu');
    menus.forEach(function (menu) {
      menu.addEventListener('toggle', function () {
        if (!menu.open) return;
        menus.forEach(function (other) {
          if (other !== menu) other.open = false;
        });
      });
    });
  }

  function setStatus(text) {
    if (!statusEl) return;
    if (!text) {
      statusEl.hidden = true;
      statusEl.textContent = '';
      return;
    }
    statusEl.hidden = false;
    statusEl.textContent = text;
  }

  function currentResidency() {
    return residencyFilter ? (residencyFilter.value || '') : '';
  }

  function currentRegistry() {
    return registryFilter ? (registryFilter.value || 'active') : 'active';
  }

  function updateClearButton() {
    if (!clearBtn) return;
    clearBtn.hidden = input.value.trim() === '' && currentResidency() === '' && currentRegistry() === 'active';
  }

  function buildUrl(q, page, residency, registry) {
    var params = new URLSearchParams();
    if (q) params.set('q', q);
    if (residency) params.set('residency', residency);
    if (registry && registry !== 'active') params.set('registry', registry);
    if (page > 1) params.set('page', String(page));
    params.set('partial', '1');
    var query = params.toString();
    return searchUrl + (query ? '?' + query : '');
  }

  function updateBrowserUrl(q, page, residency, registry) {
    var params = new URLSearchParams();
    if (q) params.set('q', q);
    if (residency) params.set('residency', residency);
    if (registry && registry !== 'active') params.set('registry', registry);
    if (page > 1) params.set('page', String(page));
    var query = params.toString();
    var nextUrl = searchUrl + (query ? '?' + query : '');
    if (window.location.pathname + window.location.search !== nextUrl) {
      window.history.replaceState({ q: q, page: page }, '', nextUrl);
    }
  }

  function runSearch(page) {
    page = page || 1;
    currentPage = page;
    var q = input.value.trim();
    var residency = currentResidency();
    var registry = currentRegistry();
    var localRequest = ++requestId;

    setStatus('Searching…');
    resultsEl.querySelector('.patients-table-wrap')?.classList.add('is-loading');

    fetch(buildUrl(q, page, residency, registry), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Search failed');
        return res.text();
      })
      .then(function (html) {
        if (localRequest !== requestId) return;
        resultsEl.innerHTML = html;
        bindRowMenus(resultsEl);
        updateBrowserUrl(q, page, residency, registry);
        updateClearButton();
        setStatus('');
      })
      .catch(function () {
        if (localRequest !== requestId) return;
        setStatus('Could not load results.');
        resultsEl.querySelector('.patients-table-wrap')?.classList.remove('is-loading');
      });
  }

  function scheduleSearch(resetPage) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(function () {
      runSearch(resetPage ? 1 : currentPage);
    }, 300);
  }

  if (residencyFilter) {
    residencyFilter.addEventListener('change', function () {
      updateClearButton();
      runSearch(1);
    });
  }

  if (registryFilter) {
    registryFilter.addEventListener('change', function () {
      updateClearButton();
      runSearch(1);
    });
  }

  input.addEventListener('input', function () {
    updateClearButton();
    scheduleSearch(true);
  });

  input.addEventListener('search', function () {
    if (input.value === '') {
      updateClearButton();
      scheduleSearch(true);
    }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    runSearch(1);
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      input.value = '';
      if (residencyFilter) residencyFilter.value = '';
      if (registryFilter) registryFilter.value = 'active';
      updateClearButton();
      input.focus();
      runSearch(1);
    });
  }

  resultsEl.addEventListener('click', function (e) {
    var link = e.target.closest('.patients-page-link');
    if (!link || link.style.pointerEvents === 'none') return;
    e.preventDefault();
    var page = parseInt(link.getAttribute('data-page') || '1', 10);
    if (!page || page < 1) return;
    runSearch(page);
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.appt-menu')) return;
    document.querySelectorAll('#patientsRegistryResults .appt-menu').forEach(function (menu) {
      menu.open = false;
    });
  });

  bindRowMenus(resultsEl);
})();
</script>
