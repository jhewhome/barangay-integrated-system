<?php
/**
 * Create BHC staff accounts for Gawad BIS users (matching usernames for SSO).
 *
 * Usage:
 *   php scripts/sync_gawad_staff_users.php --password=YourTempPass123 [--dry-run]
 *
 * Requires Gawad BIS running and gawad_integration.local.php configured.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

define('BHC_ROOT', dirname(__DIR__));

require_once BHC_ROOT . '/config/database.php';
require_once BHC_ROOT . '/core/GawadIntegration.php';
require_once BHC_ROOT . '/core/GawadStaffSync.php';
require_once BHC_ROOT . '/models/User.php';
require_once BHC_ROOT . '/models/AuditLog.php';

$password = '';
$dryRun = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
        continue;
    }
    if (str_starts_with($arg, '--password=')) {
        $password = substr($arg, strlen('--password='));
    }
}

if ($password === '') {
    fwrite(STDERR, "Usage: php scripts/sync_gawad_staff_users.php --password=YourTempPass123 [--dry-run]\n");
    exit(1);
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$result = GawadStaffSync::sync($db, $password, $dryRun, null);

echo GawadStaffSync::summaryMessage($result) . "\n";

if (!empty($result['error'])) {
    exit(1);
}

echo "Role mapping: Gawad Administrator → BHC admin; all other Gawad roles → BHC staff.\n";
if (!$dryRun && !empty($result['created'])) {
    echo "Tell each user to change their password after first login (Account → Change password).\n";
}

exit(0);
