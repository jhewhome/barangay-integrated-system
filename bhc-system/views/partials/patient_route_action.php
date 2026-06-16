<?php

/** @var int $patientId */

/** @var array<int,array<int,array<string,mixed>>> $activeQueueByPatient */

/** @var int|null $routeAppointmentId */

/** @var bool|null $patientCanRoute */

/** @var bool|null $patientIsArchived */

$patientId = (int) ($patientId ?? 0);

$activeQueueByPatient = $activeQueueByPatient ?? [];

$activeQueue = $activeQueueByPatient[$patientId] ?? [];

$patientCanRoute = $patientCanRoute ?? true;

$patientIsArchived = $patientIsArchived ?? false;

$routeAppointmentId = (int) ($routeAppointmentId ?? 0);

$consultStationId = QueueTicket::consultationStationId();

$inConsult = false;

foreach ($activeQueue as $ticket) {

    if ((int) ($ticket['station_id'] ?? 0) === $consultStationId) {

        $inConsult = true;

        break;

    }

}

?>

<?php if (!empty($activeQueue)): ?>

  <?php $queueTicket = $activeQueue[0]; ?>

  <a

    class="appt-action appt-action-queue"

    href="<?= h(app_url('/queue/' . (int) $queueTicket['station_id'])) ?>"

    title="<?= h(($queueTicket['ticket_no'] ?? 'Ticket') . ' at ' . ($queueTicket['station_name'] ?? 'station') . ' (' . strtoupper((string) ($queueTicket['status'] ?? 'active')) . ')') ?>"

  ><?= $inConsult ? 'In consult' : 'In queue' ?></a>

<?php elseif ($patientIsArchived): ?>

  <a class="appt-action" href="<?= h(app_url('/patients/' . $patientId . '/history')) ?>" title="Archived — view history or restore">Archived</a>

<?php elseif (!$patientCanRoute): ?>

  <a class="appt-action" href="<?= h(app_url('/patients/' . $patientId . '/edit')) ?>" title="Verify Balong Bato residency before routing">Verify residency</a>

<?php else: ?>

  <?php
    $routeHref = app_url('/queue/1?patient_id=' . $patientId);
    if ($routeAppointmentId > 0) {
        $routeHref .= '&appointment_id=' . $routeAppointmentId;
    }
  ?>
  <a class="appt-action appt-action-route" href="<?= h($routeHref) ?>">Route</a>

<?php endif; ?>


