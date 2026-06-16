<?php
$medicine = $medicine ?? null;
$errors = $errors ?? [];
if (!$medicine) {
    echo '<div class="muted">Medicine not found.</div>';
    return;
}
$old = $medicine;
$isEdit = true;
require __DIR__ . '/_form.php';
