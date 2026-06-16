<?php
$counts = $counts ?? [];
?>

<div class="card">
  <div class="row row-between">
    <div class="row-body">
      <div class="pill serving">Queue Stations</div>
      <h1 style="margin-top: 10px;">Service Stations</h1>
      <div class="muted">Select a station to manage its queue, or open the waiting-area display.</div>
    </div>
    <div class="row-actions">
      <?php $isAdmin = (Auth::user()['role'] ?? '') === 'admin'; require __DIR__ . '/../partials/patient_register_actions.php'; ?>
      <a class="btn" href="<?= h(app_url('/queue/1')) ?>">Patient Routing</a>
    </div>
  </div>
</div>

<div class="grid cols-3" style="margin-top: 14px;">
  <?php foreach ($stations as $s): ?>
    <?php
      $id = (int) $s['id'];
      $name = (string) $s['name'];
      $tip = match ($name) {
        'Registration' => 'Routing desk: search patient, ask reason, assign station, create ticket.',
        'Triage / Vitals' => 'Vitals and initial assessment before consultation.',
        'Consultation' => 'Consultation station: call next, serve, complete.',
        'Pharmacy' => 'Dispense medicines and complete the visit step.',
        default => 'Manage today’s queue for this station.',
      };
      $icon = match ($name) {
        'Registration' => '🧾',
        'Triage / Vitals' => '🩺',
        'Consultation' => '👩‍⚕️',
        'Pharmacy' => '💊',
        default => '🏥',
      };
      $c = $counts[$id] ?? ['waiting' => 0, 'serving' => 0];
    ?>
    <div class="card has-tip" data-tip="<?= h($tip) ?>" style="transition: transform 120ms ease, box-shadow 120ms ease;">
      <div>
          <div class="muted" style="font-size: 24px; line-height: 1;"><?= h($icon) ?></div>
          <h2 style="margin: 10px 0 6px;"><?= h($name) ?></h2>
          <div class="muted">Station ID: <?= $id ?></div>
        <div class="row" style="gap: 8px; margin-top: 10px;">
          <div class="pill waiting">Waiting: <strong><?= (int) $c['waiting'] ?></strong></div>
          <div class="pill serving">Serving: <strong><?= (int) $c['serving'] ?></strong></div>
        </div>
      </div>

      <div class="row-actions row-actions-tight" style="margin-top: 14px;">
        <a class="btn" href="<?= h(app_url('/queue/' . (int) $id)) ?>">Open</a>
        <a class="btn" href="<?= h(app_url('/display/' . (int) $id)) ?>" target="_blank" rel="noopener" style="box-shadow:none;">Patient display</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
  // Subtle hover lift for station cards
  (function () {
    var cards = document.querySelectorAll('.card.has-tip');
    for (var i = 0; i < cards.length; i++) {
      cards[i].onmouseenter = function () { this.style.transform = 'translateY(-2px)'; };
      cards[i].onmouseleave = function () { this.style.transform = 'translateY(0)'; };
    }
  })();
</script>

