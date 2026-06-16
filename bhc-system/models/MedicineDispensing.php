<?php

class MedicineDispensing
{
    public const SOURCE_CLINIC = 'clinic';
    public const SOURCE_LGU = 'lgu';
    public const SOURCE_EXTERNAL = 'external';

    public static function normalizeProcurementSource(string $source): string
    {
        return match ($source) {
            self::SOURCE_LGU, self::SOURCE_EXTERNAL => $source,
            default => self::SOURCE_CLINIC,
        };
    }

    public static function procurementLabel(string $source): string
    {
        return match (self::normalizeProcurementSource($source)) {
            self::SOURCE_LGU => 'Request from LGU',
            self::SOURCE_EXTERNAL => 'Buy externally (boutique/pharmacy)',
            default => 'Clinic stock',
        };
    }

    public static function procurementShortLabel(string $source): string
    {
        return match (self::normalizeProcurementSource($source)) {
            self::SOURCE_LGU => 'LGU request',
            self::SOURCE_EXTERNAL => 'Buy externally',
            default => 'Clinic',
        };
    }

    /** @param array<int,array{medicine_name:string,quantity:float,unit:string,receipt_issued?:bool,medicine_id?:int,procurement_source?:string}> $lines */
    public static function createBatch(PDO $db, int $patientId, array $lines, array $meta): int
    {
        $consultationId = !empty($meta['consultation_id']) ? (int) $meta['consultation_id'] : null;
        $ticketId = !empty($meta['queue_ticket_id']) ? (int) $meta['queue_ticket_id'] : null;
        $createdBy = !empty($meta['created_by']) ? (int) $meta['created_by'] : null;
        $status = ($meta['dispense_status'] ?? 'dispensed') === 'prescribed' ? 'prescribed' : 'dispensed';
        $replace = (string) ($meta['replace'] ?? '');

        if ($replace === 'prescribed' && $consultationId) {
            self::replacePrescribedForConsultation($db, $consultationId);
        } elseif ($replace === 'dispensed') {
            if ($consultationId) {
                self::replaceDispensedForConsultation($db, $consultationId);
            } elseif ($ticketId) {
                self::replaceDispensedForTicket($db, $ticketId);
            }
        }

        $stmt = $db->prepare(
            "INSERT INTO medicine_dispensings
             (patient_id, consultation_id, queue_ticket_id, medicine_id, medicine_name, quantity, unit,
              procurement_source, dispense_status, receipt_issued, notes, created_by)
             VALUES
             (:patient_id, :consultation_id, :queue_ticket_id, :medicine_id, :medicine_name, :quantity, :unit,
              :procurement_source, :dispense_status, :receipt_issued, :notes, :created_by)"
        );

        $count = 0;
        $db->beginTransaction();
        try {
            foreach ($lines as $line) {
                $name = trim((string) ($line['medicine_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $qty = max(1, (int) round((float) ($line['quantity'] ?? 1)));
                $unit = trim((string) ($line['unit'] ?? 'tablet(s)')) ?: 'tablet(s)';
                $source = self::normalizeProcurementSource((string) ($line['procurement_source'] ?? self::SOURCE_CLINIC));
                $lineStatus = $source === self::SOURCE_CLINIC ? $status : 'prescribed';
                $receipt = $source === self::SOURCE_CLINIC && !empty($line['receipt_issued']) ? 1 : 0;
                $medicineId = (int) ($line['medicine_id'] ?? 0);
                if ($medicineId <= 0 && $source === self::SOURCE_CLINIC) {
                    $match = MedicineCatalog::findByName($db, $name);
                    $medicineId = $match ? (int) $match['id'] : 0;
                }
                if ($medicineId <= 0 || $source !== self::SOURCE_CLINIC) {
                    $medicineId = null;
                }

                $stmt->execute([
                    ':patient_id' => $patientId,
                    ':consultation_id' => $consultationId,
                    ':queue_ticket_id' => $ticketId,
                    ':medicine_id' => $medicineId,
                    ':medicine_name' => $name,
                    ':quantity' => $qty,
                    ':unit' => $unit,
                    ':procurement_source' => $source,
                    ':dispense_status' => $lineStatus,
                    ':receipt_issued' => $receipt,
                    ':notes' => $meta['notes'] ?? null,
                    ':created_by' => $createdBy,
                ]);
                $count++;
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        return $count;
    }

    public static function replacePrescribedForConsultation(PDO $db, int $consultationId): void
    {
        if ($consultationId <= 0) {
            return;
        }
        $stmt = $db->prepare(
            "DELETE FROM medicine_dispensings
             WHERE consultation_id = :consultation_id
               AND dispense_status = 'prescribed'"
        );
        $stmt->execute([':consultation_id' => $consultationId]);
    }

    public static function replaceDispensedForConsultation(PDO $db, int $consultationId): void
    {
        if ($consultationId <= 0) {
            return;
        }
        $stmt = $db->prepare(
            "DELETE FROM medicine_dispensings
             WHERE consultation_id = :consultation_id
               AND dispense_status = 'dispensed'"
        );
        $stmt->execute([':consultation_id' => $consultationId]);
    }

    public static function replaceDispensedForTicket(PDO $db, int $ticketId): void
    {
        if ($ticketId <= 0) {
            return;
        }
        $stmt = $db->prepare(
            "DELETE FROM medicine_dispensings
             WHERE queue_ticket_id = :ticket_id
               AND dispense_status = 'dispensed'
               AND consultation_id IS NULL"
        );
        $stmt->execute([':ticket_id' => $ticketId]);
    }

    /** @param array<int,array<string,mixed>> $medicines */
    public static function consolidateLines(array $medicines): array
    {
        $byKey = [];
        foreach ($medicines as $m) {
            $name = strtolower(trim((string) ($m['medicine_name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $source = self::normalizeProcurementSource(
                (string) ($m['procurement_source'] ?? self::SOURCE_CLINIC)
            );
            $key = $name . '|' . $source;

            if (!isset($byKey[$key])) {
                $byKey[$key] = $m;
                continue;
            }

            $existing = $byKey[$key];
            $existingDispensed = ($existing['dispense_status'] ?? '') === 'dispensed';
            $newDispensed = ($m['dispense_status'] ?? '') === 'dispensed';
            if ($newDispensed && !$existingDispensed) {
                $byKey[$key] = $m;
                continue;
            }
            if ($newDispensed === $existingDispensed && (int) ($m['id'] ?? 0) > (int) ($existing['id'] ?? 0)) {
                $byKey[$key] = $m;
            }
        }

        return array_values($byKey);
    }

    public static function forPatientConsolidated(PDO $db, int $patientId, int $limit = 100): array
    {
        $rows = self::forPatient($db, $patientId, max($limit * 3, $limit));
        $groups = [];
        foreach ($rows as $row) {
            $cid = (int) ($row['consultation_id'] ?? 0);
            $key = $cid > 0 ? 'c' . $cid : 'r' . (int) ($row['id'] ?? 0);
            $groups[$key][] = $row;
        }

        $out = [];
        foreach ($groups as $group) {
            foreach (self::consolidateLines($group) as $line) {
                $out[] = $line;
            }
        }

        usort($out, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return array_slice($out, 0, $limit);
    }

    public static function forPatient(PDO $db, int $patientId, int $limit = 100): array
    {
        $stmt = $db->prepare(
            "SELECT md.*, u.username AS recorded_by_name,
                    cr.diagnosis, qt.ticket_no
             FROM medicine_dispensings md
             LEFT JOIN users u ON u.id = md.created_by
             LEFT JOIN consultation_records cr ON cr.id = md.consultation_id
             LEFT JOIN queue_tickets qt ON qt.id = md.queue_ticket_id
             WHERE md.patient_id = :patient_id
             ORDER BY md.created_at DESC, md.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function forConsultation(PDO $db, int $consultationId): array
    {
        $stmt = $db->prepare(
            "SELECT * FROM medicine_dispensings
             WHERE consultation_id = :consultation_id
             ORDER BY id ASC"
        );
        $stmt->execute([':consultation_id' => $consultationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function forTicket(PDO $db, int $ticketId): array
    {
        $stmt = $db->prepare(
            "SELECT * FROM medicine_dispensings
             WHERE queue_ticket_id = :ticket_id
             ORDER BY id ASC"
        );
        $stmt->execute([':ticket_id' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<int,array<string,mixed>> $lines */
    public static function nonEmptyLines(array $lines): array
    {
        return array_values(array_filter(
            $lines,
            static fn (array $line): bool => trim((string) ($line['medicine_name'] ?? '')) !== ''
        ));
    }

    /**
     * Whether saving these lines should create or open a medicine receipt document.
     *
     * @param array<int,array<string,mixed>> $lines
     */
    public static function shouldIssueMedicineReceipt(array $lines, bool $treatAsDispensed = false): bool
    {
        $lines = self::nonEmptyLines($lines);
        if (empty($lines)) {
            return false;
        }
        if ($treatAsDispensed) {
            return true;
        }
        foreach ($lines as $line) {
            if (!empty($line['receipt_issued'])) {
                return true;
            }
            $source = self::normalizeProcurementSource((string) ($line['procurement_source'] ?? self::SOURCE_CLINIC));
            if (in_array($source, [self::SOURCE_LGU, self::SOURCE_EXTERNAL], true)) {
                return true;
            }
        }
        return false;
    }

    public static function consultationShouldHaveReceipt(PDO $db, int $consultationId, bool $treatAsDispensed = false): bool
    {
        if ($consultationId <= 0) {
            return false;
        }

        $medicines = self::forConsultation($db, $consultationId);
        if (empty($medicines)) {
            return false;
        }

        $lines = [];
        foreach ($medicines as $medicine) {
            $lines[] = [
                'medicine_name' => (string) ($medicine['medicine_name'] ?? ''),
                'procurement_source' => (string) ($medicine['procurement_source'] ?? self::SOURCE_CLINIC),
                'receipt_issued' => !empty($medicine['receipt_issued']),
            ];
        }

        return self::shouldIssueMedicineReceipt($lines, $treatAsDispensed);
    }

    /** @return array<int,array<string,mixed>> */
    public static function parseLinesFromPost(array $post): array
    {
        $names = $post['medicine_name'] ?? [];
        $qtys = $post['medicine_quantity'] ?? [];
        $units = $post['medicine_unit'] ?? [];
        $receipts = $post['medicine_receipt'] ?? [];
        $ids = $post['medicine_id'] ?? [];
        $sources = $post['medicine_source'] ?? [];

        if (!is_array($names)) {
            return [];
        }

        $lines = [];
        foreach ($names as $i => $name) {
            $lines[] = [
                'medicine_name' => (string) $name,
                'medicine_id' => (int) ($ids[$i] ?? 0),
                'quantity' => max(1, (int) round((float) ($qtys[$i] ?? 1))),
                'unit' => (string) ($units[$i] ?? 'tablet(s)'),
                'procurement_source' => self::normalizeProcurementSource((string) ($sources[$i] ?? self::SOURCE_CLINIC)),
                'receipt_issued' => !empty($receipts[$i]),
            ];
        }
        return $lines;
    }

    /** @return array{total_lines:int, prescribed:int, dispensed:int, receipts:int, unique_patients:int} */
    public static function monthlyTotals(PDO $db, string $month): array
    {
        $b = ReportMonth::bounds($month);
        return self::totalsForRange($db, $b['start'], $b['end']);
    }

    public static function totalsForRange(PDO $db, string $start, string $end): array
    {
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total_lines,
                SUM(dispense_status = 'prescribed') AS prescribed,
                SUM(dispense_status = 'dispensed') AS dispensed,
                SUM(receipt_issued = 1) AS receipts,
                COUNT(DISTINCT patient_id) AS unique_patients
             FROM medicine_dispensings
             WHERE created_at >= :start AND created_at < :end"
        );
        $stmt->execute([':start' => $start, ':end' => $end]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total_lines' => (int) ($r['total_lines'] ?? 0),
            'prescribed' => (int) ($r['prescribed'] ?? 0),
            'dispensed' => (int) ($r['dispensed'] ?? 0),
            'receipts' => (int) ($r['receipts'] ?? 0),
            'unique_patients' => (int) ($r['unique_patients'] ?? 0),
        ];
    }

    public static function monthlyTopMedicines(PDO $db, string $month, int $limit = 15): array
    {
        $b = ReportMonth::bounds($month);
        return self::topMedicinesForRange($db, $b['start'], $b['end'], $limit);
    }

    public static function topMedicinesForRange(PDO $db, string $start, string $end, int $limit = 15): array
    {
        $stmt = $db->prepare(
            "SELECT medicine_name, unit,
                    SUM(quantity) AS total_quantity,
                    COUNT(*) AS line_count,
                    SUM(dispense_status = 'dispensed') AS dispensed_count
             FROM medicine_dispensings
             WHERE created_at >= :start AND created_at < :end
             GROUP BY medicine_name, unit
             ORDER BY line_count DESC, total_quantity DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function monthlyList(PDO $db, string $month, int $limit = 500): array
    {
        $b = ReportMonth::bounds($month);
        return self::listForRange($db, $b['start'], $b['end'], $limit);
    }

    public static function listForRange(PDO $db, string $start, string $end, int $limit = 500): array
    {
        $stmt = $db->prepare(
            "SELECT md.*, p.bhc_id, p.full_name, cr.diagnosis
             FROM medicine_dispensings md
             JOIN patients p ON p.id = md.patient_id
             LEFT JOIN consultation_records cr ON cr.id = md.consultation_id
             WHERE md.created_at >= :start AND md.created_at < :end
             ORDER BY md.created_at DESC, md.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':start', $start);
        $stmt->bindValue(':end', $end);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
