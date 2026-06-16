<?php
/**
 * Gawad BIS ↔ BHC integration (Phase 2 resident sync).
 * Copy gawad_integration.local.example.php to gawad_integration.local.php and adjust.
 */
$defaults = [
    'enabled' => false,
    'api_base_url' => 'http://localhost:5003',
    'integration_api_key' => '',
    // Phase 3 — Gawad SSO (shared secret must match Gawad BhcIntegration:SsoSecret)
    'sso_enabled' => false,
    'sso_secret' => '',
    'sso_token_lifetime_seconds' => 300,
    // Optional override; defaults to {api_base_url}/Medicines
    'medicines_url' => '',
    // Phase 4 — read-only medicine catalog + stock from Gawad for BHC prescription picker
    'medicine_sync_enabled' => true,
];

$local = __DIR__ . '/gawad_integration.local.php';
if (is_file($local)) {
    $overrides = require $local;
    if (is_array($overrides)) {
        return array_merge($defaults, $overrides);
    }
}

return $defaults;
