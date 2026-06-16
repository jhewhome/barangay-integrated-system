<?php
/** @var array<int,array<string,mixed>> $patients */
/** @var string $q */
/** @var string $residency */
/** @var string $registry */
/** @var bool $isAdmin */
/** @var int $page */
/** @var int $perPage */
/** @var int $total */
/** @var int $totalPages */
/** @var int $start */
/** @var int $end */
/** @var array<int,array<int,array<string,mixed>>> $activeQueueByPatient */

if (!function_exists('patients_registry_page_url')) {
    function patients_registry_page_url(int $page, string $q, string $residency = '', string $registry = 'active'): string
    {
        $params = ['page' => $page];
        if ($q !== '') {
            $params['q'] = $q;
        }
        if ($residency !== '') {
            $params['residency'] = $residency;
        }
        if ($registry !== '' && $registry !== 'active') {
            $params['registry'] = $registry;
        }

        return app_url('/patients?' . http_build_query($params));
    }
}
?>

<div class="table-wrap list-table-wrap patients-table-wrap<?= !empty($loading) ? ' is-loading' : '' ?>">
  <table class="data-table list-table patients-table">
    <thead>
      <tr>
        <th class="col-id">BHC ID</th>
        <th class="col-name">Name</th>
        <th class="col-sex">Sex</th>
        <th class="col-dob">DOB</th>
        <th class="col-barangay">Barangay</th>
        <th class="col-contact">Contact</th>
        <th class="col-actions">Manage</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($patients)): ?>
        <tr class="table-empty-row list-table-empty-row">
          <td colspan="7" class="muted"><?php
            if (($registry ?? 'active') === 'archived') {
                echo 'No archived patients match your search.';
            } elseif ($q !== '' || ($residency ?? '') !== '') {
                echo 'No patients match your search.';
            } else {
                echo 'No patients yet.';
            }
          ?></td>
        </tr>
      <?php else: ?>
        <?php foreach ($patients as $p): ?>
          <?php
            $sex = (string) ($p['sex'] ?? '');
            $sexLabel = match ($sex) {
                'M' => 'Male',
                'F' => 'Female',
                default => $sex,
            };
            $patientId = (int) $p['id'];
            $isPatientArchived = Patient::isArchived($p);
            $canRoute = Patient::canReceiveServices($p);
          ?>
          <tr<?= $isPatientArchived ? ' class="patient-row-archived"' : '' ?>>
            <td class="col-id" data-label="BHC ID"><strong><?= h($p['bhc_id']) ?></strong></td>
            <td class="col-name" data-label="Name">
              <a href="<?= h(app_url('/patients/' . $patientId . '/history')) ?>"><?= h($p['full_name']) ?></a>
              <?php if ($isPatientArchived): ?>
                <span class="pill skipped" style="margin-left: 6px;">Archived</span>
              <?php endif; ?>
            </td>
            <td class="col-sex muted" data-label="Sex" title="<?= h($sexLabel) ?>"><?= h($sex !== '' ? $sex : '—') ?></td>
            <td class="col-dob muted" data-label="DOB"><?= h((string) ($p['birthdate'] ?? '')) ?></td>
            <td class="col-barangay muted" data-label="Barangay"><?= h((string) ($p['barangay'] ?? '')) ?></td>
            <td class="col-contact muted" data-label="Contact"><?= h((string) ($p['contact_number'] ?? '')) ?></td>
            <td class="col-actions" data-label="Manage">
              <div class="appt-row-actions">
                <?php $patientCanRoute = $canRoute; $patientIsArchived = $isPatientArchived; require __DIR__ . '/../../partials/patient_route_action.php'; ?>
                <details class="appt-menu">
                  <summary class="appt-action appt-action-menu">More</summary>
                  <div class="appt-menu-panel">
                    <a class="appt-menu-item" href="<?= h(app_url('/patients/' . $patientId . '/history')) ?>">Patient history</a>
                    <a class="appt-menu-item" href="<?= h(app_url('/patients/' . $patientId . '/edit')) ?>">Edit patient</a>
                    <?php if (!empty($isAdmin)): ?>
                      <?php if ($isPatientArchived): ?>
                        <form method="POST" action="<?= h(app_route('/patients/' . $patientId . '/restore')) ?>" class="appt-menu-form" onsubmit="return confirm('Restore this patient to the active registry?');">
                          <input type="hidden" name="return_to" value="<?= h(app_url('/patients?' . http_build_query(array_filter(['q' => $q ?? '', 'residency' => $residency ?? '', 'registry' => ($registry ?? '') !== 'active' ? ($registry ?? '') : ''])))) ?>" />
                          <button class="appt-menu-item" type="submit">Restore patient</button>
                        </form>
                      <?php else: ?>
                        <form method="POST" action="<?= h(app_route('/patients/' . $patientId . '/archive')) ?>" class="appt-menu-form" onsubmit="return confirm('Archive this patient? They will be hidden from the registry but all visit history is kept.');">
                          <button class="appt-menu-item appt-menu-item-danger" type="submit">Archive patient</button>
                        </form>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </details>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="row row-between patients-pagination" style="margin-top: 14px;">
  <div class="muted patients-result-count">
    <?php if ($total === 0): ?>
      No results
    <?php else: ?>
      Showing <strong><?= $start ?></strong>–<strong><?= $end ?></strong> of <strong><?= $total ?></strong>
    <?php endif; ?>
  </div>
  <div class="row-actions row-actions-tight">
    <a class="btn patients-page-link" href="<?= h(patients_registry_page_url(max(1, $page - 1), $q, (string) ($residency ?? ''), (string) ($registry ?? 'active'))) ?>" data-page="<?= max(1, $page - 1) ?>" <?= $page <= 1 ? 'style="opacity:.5;pointer-events:none;"' : '' ?>>Prev</a>
    <span class="pill">Page <?= $page ?> / <?= $totalPages ?></span>
    <a class="btn patients-page-link" href="<?= h(patients_registry_page_url(min($totalPages, $page + 1), $q, (string) ($residency ?? ''), (string) ($registry ?? 'active'))) ?>" data-page="<?= min($totalPages, $page + 1) ?>" <?= $page >= $totalPages ? 'style="opacity:.5;pointer-events:none;"' : '' ?>>Next</a>
  </div>
</div>
