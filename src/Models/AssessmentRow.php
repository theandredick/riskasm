<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AssessmentRow
{
    /** Hierarchy of control types (ISO/IEC hierarchy of controls). */
    public const CONTROL_TYPES = [
        'elimination'    => 'Elimination',
        'substitution'   => 'Substitution',
        'engineering'    => 'Engineering',
        'administrative' => 'Administrative',
        'ppe'            => 'PPE',
    ];

    // ── Queries ───────────────────────────────────────────────────────────────

    /**
     * Return all rows for an assessment with controls as ordered arrays,
     * ordered by sort_order then id.
     */
    public static function allForAssessment(int $assessmentId): array
    {
        $rows = Database::fetchAll(
            'SELECT ar.*,
                    COALESCE(
                        (SELECT json_agg(json_build_object(\'description\', rc.description, \'control_type\', rc.control_type)
                                         ORDER BY rc.sort_order)
                         FROM row_controls rc
                         WHERE rc.assessment_row_id = ar.id AND rc.phase = \'existing\'),
                        \'[]\'::json
                    ) AS existing_controls,
                    COALESCE(
                        (SELECT json_agg(json_build_object(\'description\', rc.description, \'control_type\', rc.control_type)
                                         ORDER BY rc.sort_order)
                         FROM row_controls rc
                         WHERE rc.assessment_row_id = ar.id AND rc.phase = \'proposed\'),
                        \'[]\'::json
                    ) AS proposed_controls
             FROM assessment_rows ar
             WHERE ar.assessment_id = ?
             ORDER BY ar.sort_order, ar.id',
            [$assessmentId]
        );

        return array_map(self::decodeControlArrays(...), $rows);
    }

    /** Find a single row by ID with controls as ordered arrays. */
    public static function find(int $id): ?array
    {
        $row = Database::fetchOne(
            'SELECT ar.*,
                    COALESCE(
                        (SELECT json_agg(json_build_object(\'description\', rc.description, \'control_type\', rc.control_type)
                                         ORDER BY rc.sort_order)
                         FROM row_controls rc
                         WHERE rc.assessment_row_id = ar.id AND rc.phase = \'existing\'),
                        \'[]\'::json
                    ) AS existing_controls,
                    COALESCE(
                        (SELECT json_agg(json_build_object(\'description\', rc.description, \'control_type\', rc.control_type)
                                         ORDER BY rc.sort_order)
                         FROM row_controls rc
                         WHERE rc.assessment_row_id = ar.id AND rc.phase = \'proposed\'),
                        \'[]\'::json
                    ) AS proposed_controls
             FROM assessment_rows ar
             WHERE ar.id = ?',
            [$id]
        );

        return $row !== null ? self::decodeControlArrays($row) : null;
    }

    /** Decode JSON control array strings returned by the DB driver into PHP arrays. */
    private static function decodeControlArrays(array $row): array
    {
        $row['existing_controls'] = json_decode($row['existing_controls'] ?? '[]', true) ?? [];
        $row['proposed_controls'] = json_decode($row['proposed_controls'] ?? '[]', true) ?? [];
        return $row;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /** Create a new row. Returns the new row ID. */
    public static function create(int $assessmentId, array $data): int
    {
        $maxOrder = (int) Database::fetchScalar(
            'SELECT COALESCE(MAX(sort_order), -1) FROM assessment_rows WHERE assessment_id = ?',
            [$assessmentId]
        );

        $id = Database::insert(
            'INSERT INTO assessment_rows
                (assessment_id, sort_order,
                 activity_condition, hazard, exposure_description, exposed_assets, effect,
                 natural_severity_value, natural_likelihood_value,
                 natural_risk_category, natural_colour_hex, natural_risk_accept,
                 severity_value, likelihood_value,
                 risk_category, colour_hex, current_risk_accept,
                 residual_severity_value, residual_likelihood_value,
                 residual_risk_category, residual_colour_hex, residual_risk_accept,
                 comments)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $assessmentId,
                $data['sort_order'] ?? ($maxOrder + 1),
                self::nullStr($data['activity_condition']   ?? null),
                self::nullStr($data['hazard']               ?? null),
                self::nullStr($data['exposure_description'] ?? null),
                self::nullStr($data['exposed_assets']       ?? null),
                self::nullStr($data['effect']               ?? null),
                self::intOrNull($data['natural_severity_value']   ?? null),
                self::intOrNull($data['natural_likelihood_value'] ?? null),
                self::nullStr($data['natural_risk_category'] ?? null),
                self::nullStr($data['natural_colour_hex']    ?? null),
                self::boolOrNull($data['natural_risk_accept'] ?? null),
                self::intOrNull($data['severity_value']   ?? null),
                self::intOrNull($data['likelihood_value'] ?? null),
                self::nullStr($data['risk_category'] ?? null),
                self::nullStr($data['colour_hex']    ?? null),
                self::boolOrNull($data['current_risk_accept'] ?? null),
                self::intOrNull($data['residual_severity_value']   ?? null),
                self::intOrNull($data['residual_likelihood_value'] ?? null),
                self::nullStr($data['residual_risk_category'] ?? null),
                self::nullStr($data['residual_colour_hex']    ?? null),
                self::boolOrNull($data['residual_risk_accept'] ?? null),
                self::nullStr($data['comments'] ?? null),
            ]
        );

        self::saveControls($id, $data);

        return $id;
    }

    /** Update an existing row by ID. */
    public static function update(int $id, array $data): bool
    {
        $affected = Database::execute(
            'UPDATE assessment_rows
             SET activity_condition = ?, hazard = ?, exposure_description = ?, exposed_assets = ?,
                 effect = ?,
                 natural_severity_value = ?, natural_likelihood_value = ?,
                 natural_risk_category = ?, natural_colour_hex = ?, natural_risk_accept = ?,
                 severity_value = ?, likelihood_value = ?,
                 risk_category = ?, colour_hex = ?, current_risk_accept = ?,
                 residual_severity_value = ?, residual_likelihood_value = ?,
                 residual_risk_category = ?, residual_colour_hex = ?, residual_risk_accept = ?,
                 comments = ?, updated_at = NOW()
             WHERE id = ?',
            [
                self::nullStr($data['activity_condition']   ?? null),
                self::nullStr($data['hazard']               ?? null),
                self::nullStr($data['exposure_description'] ?? null),
                self::nullStr($data['exposed_assets']       ?? null),
                self::nullStr($data['effect']               ?? null),
                self::intOrNull($data['natural_severity_value']   ?? null),
                self::intOrNull($data['natural_likelihood_value'] ?? null),
                self::nullStr($data['natural_risk_category'] ?? null),
                self::nullStr($data['natural_colour_hex']    ?? null),
                self::boolOrNull($data['natural_risk_accept'] ?? null),
                self::intOrNull($data['severity_value']   ?? null),
                self::intOrNull($data['likelihood_value'] ?? null),
                self::nullStr($data['risk_category'] ?? null),
                self::nullStr($data['colour_hex']    ?? null),
                self::boolOrNull($data['current_risk_accept'] ?? null),
                self::intOrNull($data['residual_severity_value']   ?? null),
                self::intOrNull($data['residual_likelihood_value'] ?? null),
                self::nullStr($data['residual_risk_category'] ?? null),
                self::nullStr($data['residual_colour_hex']    ?? null),
                self::boolOrNull($data['residual_risk_accept'] ?? null),
                self::nullStr($data['comments'] ?? null),
                $id,
            ]
        );

        self::saveControls($id, $data);

        return $affected > 0;
    }

    /** Delete a row (cascades to row_controls). */
    public static function delete(int $id): bool
    {
        return Database::execute(
            'DELETE FROM assessment_rows WHERE id = ?',
            [$id]
        ) > 0;
    }

    /**
     * Reorder rows: accepts an array of row IDs in the desired order.
     * Sets sort_order = array index for each ID that belongs to $assessmentId.
     */
    public static function reorder(int $assessmentId, array $orderedIds): void
    {
        foreach ($orderedIds as $order => $rowId) {
            Database::execute(
                'UPDATE assessment_rows
                 SET sort_order = ?
                 WHERE id = ? AND assessment_id = ?',
                [$order, (int) $rowId, $assessmentId]
            );
        }
    }

    /**
     * Batch-sync all rows from a localStorage payload.
     *
     * - Deletes rows listed in $deletedIds (that belong to this assessment).
     * - Updates rows that have a valid positive ID.
     * - Inserts rows that have a null/negative/zero ID (temp client IDs).
     * - Touches the assessment updated_at timestamp.
     *
     * Returns the fresh row list after sync.
     */
    public static function batchSync(int $assessmentId, array $rows, array $deletedIds): array
    {
        Database::beginTransaction();

        try {
            foreach ($deletedIds as $rowId) {
                $rowId = (int) $rowId;
                if ($rowId > 0) {
                    Database::execute(
                        'DELETE FROM assessment_rows WHERE id = ? AND assessment_id = ?',
                        [$rowId, $assessmentId]
                    );
                }
            }

            foreach ($rows as $order => $rowData) {
                $rowId = isset($rowData['id']) ? (int) $rowData['id'] : 0;

                if ($rowId > 0) {
                    $exists = Database::fetchScalar(
                        'SELECT 1 FROM assessment_rows WHERE id = ? AND assessment_id = ?',
                        [$rowId, $assessmentId]
                    );

                    if ($exists) {
                        $rowData['sort_order'] = $order;
                        self::update($rowId, $rowData);
                        Database::execute(
                            'UPDATE assessment_rows SET sort_order = ? WHERE id = ?',
                            [$order, $rowId]
                        );
                    }
                } else {
                    $rowData['sort_order'] = $order;
                    self::create($assessmentId, $rowData);
                }
            }

            Assessment::touch($assessmentId);

            Database::commit();

        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }

        return self::allForAssessment($assessmentId);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Save all control measures for a row, replacing any previously stored ones.
     * Accepts each phase as an array of {description, control_type} objects.
     * Plain strings are accepted for backward compatibility.
     */
    private static function saveControls(int $rowId, array $data): void
    {
        self::savePhaseControls($rowId, 'existing', $data['existing_controls'] ?? [], $data['existing_controls_type'] ?? null);
        self::savePhaseControls($rowId, 'proposed', $data['proposed_controls'] ?? [], null);
    }

    private static function savePhaseControls(int $rowId, string $phase, mixed $controls, mixed $fallbackType): void
    {
        Database::execute(
            'DELETE FROM row_controls WHERE assessment_row_id = ? AND phase = ?',
            [$rowId, $phase]
        );

        // Backward compatibility: plain string → single-item array
        if (is_string($controls)) {
            $desc = trim($controls);
            $controls = $desc !== '' ? [['description' => $desc, 'control_type' => $fallbackType]] : [];
        }

        if (!is_array($controls)) {
            return;
        }

        foreach ($controls as $order => $ctrl) {
            $desc = self::nullStr($ctrl['description'] ?? '');
            if ($desc === null) {
                continue;
            }
            $type = $phase === 'existing' ? self::nullStr($ctrl['control_type'] ?? '') : null;
            Database::execute(
                'INSERT INTO row_controls (assessment_row_id, phase, description, control_type, sort_order)
                 VALUES (?, ?, ?, ?, ?)',
                [$rowId, $phase, $desc, $type, (int) $order]
            );
        }
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            return $value === '1' || strtolower($value) === 'true';
        }
        return (bool) $value;
    }

    private static function nullStr(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        return $s !== '' ? $s : null;
    }
}
