<?php
$filter = $filter ?? ReportPeriod::resolve([]);
$formAction = app_url((string) ($formAction ?? ''));
$exportPath = app_url((string) ($exportPath ?? ''));
$period = (string) ($filter['period'] ?? 'month');
$exportQs = ReportPeriod::toQuery($filter);
?>
<form method="GET" action="<?= h($formAction) ?>" class="row-actions row-actions-tight reports-filter report-period-form">
  <div class="reports-filter-period">
    <label for="report-period-select">Period</label>
    <select name="period" id="report-period-select">
      <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Daily</option>
      <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Weekly</option>
      <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Monthly</option>
      <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom range</option>
    </select>
  </div>
  <div class="reports-filter-field" data-period="day"<?= $period !== 'day' ? ' style="display:none;"' : '' ?>>
    <label>Date</label>
    <input type="date" name="date" value="<?= h($filter['date'] ?? date('Y-m-d')) ?>" />
  </div>
  <div class="reports-filter-field" data-period="week"<?= $period !== 'week' ? ' style="display:none;"' : '' ?>>
    <label>Week</label>
    <input type="week" name="week" value="<?= h($filter['week'] ?? ReportPeriod::isoWeek()) ?>" />
  </div>
  <div class="reports-filter-field" data-period="month"<?= $period !== 'month' ? ' style="display:none;"' : '' ?>>
    <label>Month</label>
    <input type="month" name="month" value="<?= h($filter['month'] ?? date('Y-m')) ?>" />
  </div>
  <div class="reports-filter-field reports-filter-custom" data-period="custom"<?= $period !== 'custom' ? ' style="display:none;"' : '' ?>>
    <label>From</label>
    <input type="date" name="from" value="<?= h($filter['from'] ?? date('Y-m-01')) ?>" />
  </div>
  <div class="reports-filter-field reports-filter-custom" data-period="custom"<?= $period !== 'custom' ? ' style="display:none;"' : '' ?>>
    <label>To</label>
    <input type="date" name="to" value="<?= h($filter['to'] ?? date('Y-m-d')) ?>" />
  </div>
  <div class="reports-filter-submit">
    <button class="btn" type="submit">View</button>
    <?php if ($exportPath !== ''): ?>
      <a class="btn ok" href="<?= h($exportPath) ?>?<?= h($exportQs) ?>" style="box-shadow:none;">Export CSV</a>
    <?php endif; ?>
    <button class="btn" type="button" onclick="window.print()" style="box-shadow:none;">Print</button>
  </div>
</form>
<script>
(function () {
  document.querySelectorAll('.report-period-form').forEach(function (form) {
    var sel = form.querySelector('#report-period-select');
    if (!sel) return;
    function sync() {
      var p = sel.value;
      form.querySelectorAll('[data-period]').forEach(function (el) {
        el.style.display = el.getAttribute('data-period') === p ? '' : 'none';
      });
    }
    sel.addEventListener('change', sync);
  });
})();
</script>
