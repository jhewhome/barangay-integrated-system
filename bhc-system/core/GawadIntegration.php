<?php

class GawadIntegration
{
    /** @var array<string,mixed>|null */
    private static ?array $config = null;

    private static ?string $lastApiError = null;

    public static function getLastApiError(): ?string
    {
        return self::$lastApiError;
    }

    /** @return array<string,mixed> */
    private static function config(): array
    {
        if (self::$config === null) {
            self::$config = require BHC_ROOT . '/config/gawad_integration.php';
        }

        return self::$config;
    }

    public static function isEnabled(): bool
    {
        $cfg = self::config();

        return !empty($cfg['enabled'])
            && trim((string) ($cfg['api_base_url'] ?? '')) !== ''
            && trim((string) ($cfg['integration_api_key'] ?? '')) !== '';
    }

    public static function isValidResidentId(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{24}$/i', trim($id));
    }

    /** @return array<string,mixed>|null */
    public static function fetchResident(string $residentId): ?array
    {
        if (!self::isEnabled() || !self::isValidResidentId($residentId)) {
            return null;
        }

        $data = self::apiGet('/api/integration/residents/' . rawurlencode(trim($residentId)));

        return is_array($data) ? $data : null;
    }

    /** @return list<array<string,mixed>>|null */
    public static function fetchStaffUsers(): ?array
    {
        if (!self::isEnabled()) {
            return null;
        }

        $data = self::apiGet('/api/integration/users');
        if (!is_array($data)) {
            return null;
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /** @return list<array<string,mixed>>|null */
    public static function fetchMedicines(): ?array
    {
        if (!self::isMedicineSyncEnabled()) {
            return null;
        }

        $data = self::apiGet('/api/integration/medicines');
        if (!is_array($data)) {
            return null;
        }

        $items = [];
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $mapped = self::mapMedicineToPicker($row);
            if ($mapped !== null) {
                $items[] = $mapped;
            }
        }

        usort($items, static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

        return $items;
    }

    public static function isMedicineSyncEnabled(): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $cfg = self::config();

        return !isset($cfg['medicine_sync_enabled']) || !empty($cfg['medicine_sync_enabled']);
    }

    /** @param array<string,mixed> $medicine */
    public static function mapMedicineToPicker(array $medicine): ?array
    {
        $name = trim((string) ($medicine['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $stock = (float) ($medicine['currentStock'] ?? $medicine['current_stock'] ?? 0);
        $minStock = (int) ($medicine['minimumStockLevel'] ?? $medicine['minimum_stock_level'] ?? 0);
        $isOut = !empty($medicine['isOutOfStock']) || !empty($medicine['is_out_of_stock']) || $stock <= 0;
        $isLow = !$isOut && (!empty($medicine['isLowStock']) || !empty($medicine['is_low_stock']) || ($minStock > 0 && $stock <= $minStock));
        $unitRaw = trim((string) ($medicine['unitOfMeasure'] ?? $medicine['unit_of_measure'] ?? ''));

        return [
            'id' => trim((string) ($medicine['id'] ?? '')),
            'gawad_id' => trim((string) ($medicine['id'] ?? '')),
            'name' => $name,
            'default_unit' => self::mapGawadUnit($unitRaw),
            'stock_qty' => $stock,
            'min_stock' => $minStock,
            'is_low_stock' => $isLow,
            'is_out_of_stock' => $isOut,
            'source' => 'gawad',
        ];
    }

    public static function mapGawadUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match ($unit) {
            'tablet' => 'tablet(s)',
            'capsule' => 'capsule(s)',
            'bottle' => 'bottle(s)',
            'tube' => 'tube(s)',
            'box', 'vial', 'ampoule', 'syringe', 'piece', 'other' => 'pcs',
            default => 'tablet(s)',
        };
    }

    /** @return array<string,mixed>|list<array<string,mixed>>|null */
    private static function apiGet(string $path): array|null
    {
        self::$lastApiError = null;
        $cfg = self::config();
        $base = rtrim((string) $cfg['api_base_url'], '/');
        $url = $base . $path;
        $key = (string) $cfg['integration_api_key'];
        $status = 0;
        $body = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'X-Integration-Key: ' . $key,
                ],
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 15,
                    'header' => "Accept: application/json\r\nX-Integration-Key: {$key}\r\n",
                    'ignore_errors' => true,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if (is_array($http_response_header ?? null)) {
                foreach ($http_response_header as $headerLine) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $headerLine, $m)) {
                        $status = (int) $m[1];
                        break;
                    }
                }
            }
        }

        if ($body === false) {
            self::$lastApiError = 'Could not reach Gawad BIS at ' . $url . '. Is Gawad running on that URL?';

            return null;
        }

        if ($status !== 200) {
            self::$lastApiError = self::describeApiFailure($status, $path);

            return null;
        }

        $data = json_decode((string) $body, true);
        if (!is_array($data)) {
            self::$lastApiError = 'Gawad BIS returned an invalid response for ' . $path . '.';

            return null;
        }

        return $data;
    }

    private static function describeApiFailure(int $status, string $path): string
    {
        return match ($status) {
            401 => 'Invalid integration key. Ensure BHC integration_api_key matches Gawad BhcIntegration:IntegrationApiKey in appsettings.json.',
            404 => $path === '/api/integration/users'
                ? 'Gawad staff API not found (HTTP 404). Rebuild and restart Gawad BIS, and set BhcIntegration:Enabled to true in appsettings.json.'
                : ($path === '/api/integration/medicines'
                    ? 'Gawad medicine API not found (HTTP 404). Rebuild and restart Gawad BIS, and set BhcIntegration:MedicineSyncEnabled to true in appsettings.json.'
                    : 'Gawad integration API not found (HTTP 404). Rebuild and restart Gawad BIS.'),
            default => 'Gawad BIS returned HTTP ' . $status . ' for ' . $path . '.',
        };
    }

    /** @param array<string,mixed> $resident */
    public static function mapToPatientPrefill(array $resident): array
    {
        $prefill = [
            'first_name' => trim((string) ($resident['firstName'] ?? $resident['first_name'] ?? '')),
            'middle_name' => trim((string) ($resident['middleName'] ?? $resident['middle_name'] ?? '')),
            'last_name' => trim((string) ($resident['lastName'] ?? $resident['last_name'] ?? '')),
            'suffix' => trim((string) ($resident['suffix'] ?? '')),
            'sex' => strtoupper(substr((string) ($resident['sex'] ?? ''), 0, 1)) === 'F' ? 'F' : 'M',
            'birthdate' => trim((string) ($resident['birthdate'] ?? '')),
            'contact_number' => trim((string) ($resident['contactNumber'] ?? $resident['contact_number'] ?? '')),
            'address' => trim((string) ($resident['address'] ?? '')),
            'barangay' => trim((string) ($resident['barangay'] ?? '')) ?: 'Balong Bato',
            'civil_status' => strtolower(trim((string) ($resident['civilStatus'] ?? $resident['civil_status'] ?? ''))),
            'notes' => 'Imported from Gawad BIS resident registry.',
        ];

        $isResident = !empty($resident['isBarangayResident']) || !empty($resident['is_barangay_resident']);
        if ($isResident) {
            $prefill['residency_status'] = Patient::RESIDENCY_VERIFIED;
            $prefill['residency_proof_type'] = 'barangay_id';
            $prefill['residency_proof_notes'] = 'Verified via Gawad BIS resident enrollment.';
        }

        return $prefill;
    }

    /** @param array<string,mixed> $resident */
    public static function displayName(array $resident): string
    {
        $name = trim((string) ($resident['fullName'] ?? $resident['full_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim(implode(' ', array_filter([
            $resident['firstName'] ?? $resident['first_name'] ?? '',
            $resident['middleName'] ?? $resident['middle_name'] ?? '',
            $resident['lastName'] ?? $resident['last_name'] ?? '',
            $resident['suffix'] ?? '',
        ])));
    }

    public static function medicinesInventoryUrl(): ?string
    {
        if (!self::isEnabled()) {
            return null;
        }

        $cfg = self::config();
        $url = trim((string) ($cfg['medicines_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }

        $base = rtrim((string) ($cfg['api_base_url'] ?? ''), '/');

        return $base !== '' ? $base . '/Medicines' : null;
    }

    public static function residentsIndexUrl(): ?string
    {
        if (!self::isEnabled()) {
            return null;
        }

        $cfg = self::config();
        $base = rtrim((string) ($cfg['api_base_url'] ?? ''), '/');

        return $base !== '' ? $base . '/Residents' : null;
    }
}
