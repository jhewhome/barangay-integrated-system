<?php

class GawadStaffSync
{
    /** @return array{ok: bool, error?: string, created: list<string>, skipped: list<string>, invalid: list<string>, dry_run: bool} */
    public static function sync(PDO $db, string $defaultPassword, bool $dryRun = false, ?int $actorUserId = null): array
    {
        $result = [
            'ok' => false,
            'created' => [],
            'skipped' => [],
            'invalid' => [],
            'dry_run' => $dryRun,
        ];

        if (!GawadIntegration::isEnabled()) {
            $result['error'] = 'Gawad integration is not configured on BHC.';

            return $result;
        }

        if (strlen($defaultPassword) < 8) {
            $result['error'] = 'Default password must be at least 8 characters.';

            return $result;
        }

        $staff = GawadIntegration::fetchStaffUsers();
        if ($staff === null) {
            $detail = GawadIntegration::getLastApiError();
            $result['error'] = $detail
                ?? 'Could not load staff users from Gawad BIS. Check that Gawad is running and integration keys match.';

            return $result;
        }

        foreach ($staff as $row) {
            if (!is_array($row)) {
                continue;
            }

            $username = trim((string) ($row['userName'] ?? $row['UserName'] ?? ''));
            if ($username === '') {
                continue;
            }

            if (!self::isValidUsername($username)) {
                $result['invalid'][] = $username . ' (invalid username format for BHC)';
                continue;
            }

            if (User::findByUsername($db, $username)) {
                $result['skipped'][] = $username . ' (already exists)';
                continue;
            }

            $role = self::mapBhcRole((string) ($row['gawadRoleType'] ?? $row['GawadRoleType'] ?? ''));
            $displayName = trim((string) ($row['fullName'] ?? $row['FullName'] ?? ''));
            if ($displayName === '') {
                $displayName = trim(implode(' ', array_filter([
                    trim((string) ($row['firstName'] ?? $row['FirstName'] ?? '')),
                    trim((string) ($row['lastName'] ?? $row['LastName'] ?? '')),
                ])));
            }

            if ($dryRun) {
                $result['created'][] = $username . ' → ' . $role . ($displayName !== '' ? ' (' . $displayName . ')' : '');
                continue;
            }

            $id = User::create(
                $db,
                $username,
                $defaultPassword,
                $role,
                $displayName !== '' ? $displayName : null
            );

            AuditLog::log($db, $actorUserId, 'user_create', 'user', $id, [
                'username' => $username,
                'role' => $role,
                'source' => 'gawad_staff_sync',
                'gawad_role' => (string) ($row['gawadRole'] ?? $row['GawadRole'] ?? ''),
            ]);

            $result['created'][] = $username . ' (' . $role . ')';
        }

        $result['ok'] = true;

        return $result;
    }

    public static function mapBhcRole(string $gawadRoleType): string
    {
        return strtolower(trim($gawadRoleType)) === 'administrator' ? 'admin' : 'staff';
    }

    public static function isValidUsername(string $username): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username);
    }

    /** @param array{ok: bool, error?: string, created: list<string>, skipped: list<string>, invalid: list<string>, dry_run: bool} $result */
    public static function summaryMessage(array $result): string
    {
        if (!empty($result['error'])) {
            return (string) $result['error'];
        }

        $parts = [];
        if (!empty($result['created'])) {
            $label = !empty($result['dry_run']) ? 'Would create' : 'Created';
            $parts[] = $label . ' ' . count($result['created']) . ' account(s): ' . implode(', ', $result['created']);
        } else {
            $parts[] = !empty($result['dry_run'])
                ? 'No new accounts would be created.'
                : 'No new accounts were created.';
        }
        if (!empty($result['skipped'])) {
            $parts[] = 'Skipped ' . count($result['skipped']) . ': ' . implode(', ', array_slice($result['skipped'], 0, 5))
                . (count($result['skipped']) > 5 ? '…' : '');
        }
        if (!empty($result['invalid'])) {
            $parts[] = 'Invalid ' . count($result['invalid']) . ': ' . implode(', ', $result['invalid']);
        }

        return implode(' ', $parts);
    }
}
