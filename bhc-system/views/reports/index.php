<?php


?>



<div class="row row-between page-header">

  <div class="row-body">

    <h1 style="margin-bottom: 6px;">Reports</h1>

    <div class="muted">Summaries for queue operations, clinical care, and appointments — by day, week, month, or custom date range.</div>

  </div>

</div>



<h2 style="margin: 18px 0 10px;">Available reports</h2>

<p class="muted" style="margin: 0 0 12px; font-size: 14px;">Ready to view, print, and export as CSV.</p>



<div class="grid cols-3">

  <div class="card">

    <h2 style="margin: 0 0 6px;">Daily operations</h2>

    <div class="muted">Visits, queue tickets by station, triage counts, reasons for visit, and hourly arrivals.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <a class="btn ok" href="<?= h(app_url('/reports/daily')) ?>" style="box-shadow:none;">Open report</a>

    </div>

  </div>

  <div class="card">

    <h2 style="margin: 0 0 6px;">Queue report</h2>

    <div class="muted">Tickets per station, completed/skipped counts, average wait and service times.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <a class="btn ok" href="<?= h(app_url('/reports/monthly')) ?>" style="box-shadow:none;">Open report</a>

    </div>

  </div>

  <div class="card">

    <h2 style="margin: 0 0 6px;">Clinical summary</h2>

    <div class="muted">Consultations, top diagnoses, medicines prescribed and dispensed, receipts issued.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <a class="btn ok" href="<?= h(app_url('/reports/clinical')) ?>" style="box-shadow:none;">Open report</a>

    </div>

  </div>

  <div class="card">

    <h2 style="margin: 0 0 6px;">Appointments</h2>

    <div class="muted">Scheduled, completed, cancelled, and no-show follow-up visits for the selected period.</div>

    <div class="row-actions row-actions-tight" style="margin-top: 12px;">

      <a class="btn ok" href="<?= h(app_url('/reports/appointments')) ?>" style="box-shadow:none;">Open report</a>

    </div>

  </div>

</div>



<h2 style="margin: 24px 0 10px;">Coming soon</h2>

<p class="muted" style="margin: 0 0 12px; font-size: 14px;">Planned report modules — not available in this version yet.</p>



<div class="grid cols-3">

  <div class="card" style="background: var(--surface2); opacity: 0.92;">

    <strong style="display:block;">Reason for visit (extended)</strong>

    <div class="muted" style="margin-top: 6px; font-size: 13px;">Trend analysis of visit reasons across weeks or months.</div>

  </div>

  <div class="card" style="background: var(--surface2); opacity: 0.92;">

    <strong style="display:block;">Doctor activity</strong>

    <div class="muted" style="margin-top: 6px; font-size: 13px;">Patients assigned per doctor, consultations, and clinical comments.</div>

  </div>

  <div class="card" style="background: var(--surface2); opacity: 0.92;">

    <strong style="display:block;">Patient registry export</strong>

    <div class="muted" style="margin-top: 6px; font-size: 13px;">New registrations and filtered registry listing for LGU backup.</div>

  </div>

  <div class="card" style="background: var(--surface2); opacity: 0.92;">

    <strong style="display:block;">Repeat visitors</strong>

    <div class="muted" style="margin-top: 6px; font-size: 13px;">Patients with multiple visits in a selected period.</div>

  </div>

  <div class="card" style="background: var(--surface2); opacity: 0.92;">

    <strong style="display:block;">Activity log export</strong>

    <div class="muted" style="margin-top: 6px; font-size: 13px;">CSV export of audit trail by date, user, and action type.</div>

  </div>

</div>


