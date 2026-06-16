<?php
$page = (int) ($page ?? 1);
$perPage = (int) ($perPage ?? 30);
$total = (int) ($total ?? 0);
$totalPages = (int) ($totalPages ?? 1);
$filters = $filters ?? ['action' => '', 'date_from' => '', 'date_to' => ''];
$actions = $actions ?? [];
$start = $total === 0 ? 0 : (($page - 1) * $perPage + 1);
$end = min($total, $page * $perPage);

function audit_page_url(int $page, array $filters): string
{
    $params = ['page' => $page];
    foreach ($filters as $k => $v) {
        if ((string) $v !== '') {
            $params[$k] = $v;
        }
    }
    return '/audit?' . http_build_query($params);
}
?>

<div class="row row-between page-header">
  <div class="row-body">
    <h1>Activity Log</h1>
    <div class="muted">Who used the system and what actions were performed.</div>
  </div>
  <div class="row-actions">
    <a class="btn" href="<?= h(app_url('/admin')) ?>">Back to admin</a>
  </div>
</div>

<div class="card" style="margin-top: 14px;">
  <form method="GET" action="<?= h(app_url('/audit')) ?>" class="grid cols-3" style="margin-bottom: 14px; align-items: end;">
    <div>
      <label>Action</label>
      <select name="action">
        <option value="">All actions</option>
        <?php foreach ($actions as $act): ?>
          <option value="<?= h($act) ?>" <?= ($filters['action'] ?? '') === $act ? 'selected' : '' ?>><?= h($act) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Date from</label>
      <input type="date" name="date_from" value="<?= h($filters['date_from'] ?? '') ?>" />
    </div>
    <div>
      <label>Date to</label>
      <input type="date" name="date_to" value="<?= h($filters['date_to'] ?? '') ?>" />
    </div>
    <div class="span-2 row-actions row-actions-tight">
      <button class="btn" type="submit">Apply filters</button>
      <a class="btn" href="<?= h(app_url('/audit')) ?>" style="box-shadow:none;">Clear</a>
    </div>
  </form>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Action</th>
          <th>Entity</th>
          <th>IP</th>
          <th>When</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="6" class="muted">No log entries match your filters.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td><?= (int) $l['id'] ?></td>
              <td><?= h($l['username'] ?? '—') ?></td>
              <td><strong><?= h($l['action']) ?></strong></td>
              <td class="muted"><?= h(($l['entity_type'] ?? '') . ($l['entity_id'] ? (' #' . $l['entity_id']) : '')) ?></td>
              <td class="muted"><?= h($l['ip_address'] ?? '') ?></td>
              <td class="muted"><?= h($l['created_at'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="row row-between patients-pagination" style="margin-top: 14px;">
    <div class="muted">Showing <strong><?= $start ?></strong>–<strong><?= $end ?></strong> of <strong><?= $total ?></strong></div>
    <div class="row-actions row-actions-tight">
      <a class="btn" href="<?= h(audit_page_url(max(1, $page - 1), $filters)) ?>" <?= $page <= 1 ? 'style="opacity:.5;pointer-events:none;"' : '' ?>>Prev</a>
      <span class="pill">Page <?= $page ?> / <?= $totalPages ?></span>
      <a class="btn" href="<?= h(audit_page_url(min($totalPages, $page + 1), $filters)) ?>" <?= $page >= $totalPages ? 'style="opacity:.5;pointer-events:none;"' : '' ?>>Next</a>
    </div>
  </div>
</div>
