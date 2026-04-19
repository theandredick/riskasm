<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Assessment
{
    public const STATUSES = ['draft', 'in_review', 'approved', 'archived'];

    public const TEMPLATE_TYPES = ['simple', 'simple_natural', 'detailed', 'detailed_natural'];

    public const TEMPLATE_LABELS = [
        'simple'           => 'Simple',
        'simple_natural'   => 'Simple + Natural Risk',
        'detailed'         => 'Detailed',
        'detailed_natural' => 'Detailed + Natural Risk',
    ];

    public const TEMPLATE_DESCRIPTIONS = [
        'simple'           => 'Hazard, Effect, Existing Controls, Severity × Likelihood risk rating, Accept Y/N.',
        'simple_natural'   => 'Simple columns plus a Natural (inherent) risk rating before controls are applied.',
        'detailed'         => 'Full columns including Activity/Condition, Exposure Description, Exposed Assets, Control Type, Proposed Controls and Residual Risk rating.',
        'detailed_natural' => 'Detailed columns plus a Natural risk rating — the most comprehensive option.',
    ];

    public const STATUS_LABELS = [
        'draft'     => 'Draft',
        'in_review' => 'In Review',
        'approved'  => 'Approved',
        'archived'  => 'Archived',
    ];

    public const STATUS_COLORS = [
        'draft'     => 'is-light',
        'in_review' => 'is-warning',
        'approved'  => 'is-success',
        'archived'  => 'is-dark',
    ];

    /** Valid next statuses from each current status. */
    public const STATUS_TRANSITIONS = [
        'draft'     => ['in_review', 'archived'],
        'in_review' => ['draft', 'approved', 'archived'],
        'approved'  => ['archived'],
        'archived'  => ['draft'],
    ];

    private const COLUMN_CONFIGS = [
        'simple' => [
            'show_activity_condition'   => true,
            'show_exposure_description' => false,
            'show_exposed_assets'       => false,
            'show_natural_risk'         => false,
            'show_control_type'         => true,
            'show_accept_yn'            => true,
            'show_proposed_controls'    => false,
            'show_residual_risk'        => false,
        ],
        'simple_natural' => [
            'show_activity_condition'   => true,
            'show_exposure_description' => false,
            'show_exposed_assets'       => false,
            'show_natural_risk'         => true,
            'show_control_type'         => true,
            'show_accept_yn'            => true,
            'show_proposed_controls'    => false,
            'show_residual_risk'        => false,
        ],
        'detailed' => [
            'show_activity_condition'   => true,
            'show_exposure_description' => true,
            'show_exposed_assets'       => true,
            'show_natural_risk'         => false,
            'show_control_type'         => true,
            'show_accept_yn'            => true,
            'show_proposed_controls'    => true,
            'show_residual_risk'        => true,
        ],
        'detailed_natural' => [
            'show_activity_condition'   => true,
            'show_exposure_description' => true,
            'show_exposed_assets'       => true,
            'show_natural_risk'         => true,
            'show_control_type'         => true,
            'show_accept_yn'            => true,
            'show_proposed_controls'    => true,
            'show_residual_risk'        => true,
        ],
    ];

    public static function columnConfigForTemplate(string $templateType): array
    {
        return self::COLUMN_CONFIGS[$templateType] ?? self::COLUMN_CONFIGS['simple'];
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    /**
     * Find an assessment by ID accessible to $userId (owner or share).
     * Returns null if not found or not accessible.
     */
    public static function findForUser(int $id, int $userId): ?array
    {
        $row = Database::fetchOne(
            'SELECT a.*,
                    rm.name AS matrix_name, rm.is_system AS matrix_is_system,
                    u.display_name AS owner_name
             FROM assessments a
             JOIN risk_matrices rm ON rm.id = a.matrix_id
             JOIN users u ON u.id = a.owner_id
             WHERE a.id = ?
               AND (a.owner_id = ?
                    OR EXISTS (
                        SELECT 1 FROM assessment_shares s
                        WHERE s.assessment_id = a.id AND s.shared_with_id = ?
                    ))',
            [$id, $userId, $userId]
        );

        if ($row === null) {
            return null;
        }

        $row['column_config'] = json_decode($row['column_config'], true)
            ?? self::columnConfigForTemplate($row['template_type']);

        return $row;
    }

    /**
     * Return all assessments visible to $userId (owned + shared), most recent first.
     */
    public static function findAllForUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT a.*,
                    rm.name AS matrix_name,
                    u.display_name AS owner_name,
                    (a.owner_id = ?) AS is_owned,
                    (SELECT COUNT(*) FROM assessment_rows WHERE assessment_id = a.id) AS row_count
             FROM assessments a
             JOIN risk_matrices rm ON rm.id = a.matrix_id
             JOIN users u ON u.id = a.owner_id
             WHERE a.owner_id = ?
                OR EXISTS (
                    SELECT 1 FROM assessment_shares s
                    WHERE s.assessment_id = a.id AND s.shared_with_id = ?
                )
             ORDER BY a.updated_at DESC',
            [$userId, $userId, $userId]
        );

        foreach ($rows as &$row) {
            $row['column_config'] = json_decode($row['column_config'], true)
                ?? self::columnConfigForTemplate($row['template_type']);
        }

        return $rows;
    }

    /**
     * Check whether $userId has an edit-permission share on $assessmentId.
     */
    public static function sharePermission(int $assessmentId, int $userId): ?string
    {
        $share = Database::fetchOne(
            'SELECT permission FROM assessment_shares
             WHERE assessment_id = ? AND shared_with_id = ?',
            [$assessmentId, $userId]
        );
        return $share['permission'] ?? null;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /** Create a new assessment. Returns the new ID. */
    public static function create(int $userId, array $data): int
    {
        $templateType = $data['template_type'] ?? 'simple';
        $columnConfig = self::columnConfigForTemplate($templateType);

        return Database::insert(
            'INSERT INTO assessments
                (owner_id, matrix_id, title, description, reference_number,
                 location, assessor_name, review_date, template_type, column_config)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb)',
            [
                $userId,
                (int) $data['matrix_id'],
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['reference_number'] ?? ''),
                trim($data['location'] ?? ''),
                trim($data['assessor_name'] ?? ''),
                ($data['review_date'] ?? '') !== '' ? $data['review_date'] : null,
                $templateType,
                json_encode($columnConfig),
            ]
        );
    }

    /** Update the header fields of an assessment owned by $userId. */
    public static function update(int $id, int $userId, array $data): bool
    {
        $affected = Database::execute(
            'UPDATE assessments
             SET title = ?, description = ?, reference_number = ?,
                 location = ?, assessor_name = ?, review_date = ?, updated_at = NOW()
             WHERE id = ? AND owner_id = ?',
            [
                trim($data['title']),
                trim($data['description'] ?? ''),
                trim($data['reference_number'] ?? ''),
                trim($data['location'] ?? ''),
                trim($data['assessor_name'] ?? ''),
                ($data['review_date'] ?? '') !== '' ? $data['review_date'] : null,
                $id,
                $userId,
            ]
        );
        return $affected > 0;
    }

    /**
     * Transition the assessment status following the allowed workflow.
     * Returns false if the transition is not permitted or assessment not found.
     */
    public static function updateStatus(int $id, int $userId, string $newStatus): bool
    {
        $row = Database::fetchOne(
            'SELECT status FROM assessments WHERE id = ? AND owner_id = ?',
            [$id, $userId]
        );

        if ($row === null) {
            return false;
        }

        $allowed = self::STATUS_TRANSITIONS[$row['status']] ?? [];
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }

        if ($newStatus === 'approved') {
            Database::execute(
                'UPDATE assessments
                 SET status = ?, approved_at = NOW(), approved_by_id = ?, updated_at = NOW()
                 WHERE id = ? AND owner_id = ?',
                [$newStatus, $userId, $id, $userId]
            );
        } else {
            Database::execute(
                'UPDATE assessments
                 SET status = ?, approved_at = NULL, approved_by_id = NULL, updated_at = NOW()
                 WHERE id = ? AND owner_id = ?',
                [$newStatus, $id, $userId]
            );
        }

        return true;
    }

    /** Delete an assessment owned by $userId (cascades to rows and controls). */
    public static function delete(int $id, int $userId): bool
    {
        return Database::execute(
            'DELETE FROM assessments WHERE id = ? AND owner_id = ?',
            [$id, $userId]
        ) > 0;
    }

    /**
     * Deep-copy an assessment (header + rows + controls) for $userId.
     * Returns the new assessment ID.
     */
    public static function copy(int $id, int $userId): int
    {
        $src = self::findForUser($id, $userId);
        if ($src === null) {
            throw new \RuntimeException("Assessment {$id} not found.");
        }

        Database::beginTransaction();

        try {
            $newId = Database::insert(
                'INSERT INTO assessments
                    (owner_id, matrix_id, title, description, reference_number,
                     location, assessor_name, template_type, column_config, copied_from_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb, ?)',
                [
                    $userId,
                    $src['matrix_id'],
                    'Copy of ' . $src['title'],
                    $src['description'],
                    $src['reference_number'],
                    $src['location'],
                    $src['assessor_name'],
                    $src['template_type'],
                    json_encode($src['column_config']),
                    $src['id'],
                ]
            );

            // Deep-copy rows + their controls
            $rows = AssessmentRow::allForAssessment($id);
            foreach ($rows as $row) {
                $newRowId = AssessmentRow::create($newId, $row);

                $controls = Database::fetchAll(
                    'SELECT phase, description, control_type, sort_order
                     FROM row_controls
                     WHERE assessment_row_id = ?
                     ORDER BY phase, sort_order',
                    [$row['id']]
                );

                foreach ($controls as $ctrl) {
                    Database::execute(
                        'INSERT INTO row_controls (assessment_row_id, phase, description, control_type, sort_order)
                         VALUES (?, ?, ?, ?, ?)',
                        [$newRowId, $ctrl['phase'], $ctrl['description'], $ctrl['control_type'], $ctrl['sort_order']]
                    );
                }
            }

            Database::commit();
            return $newId;

        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /** Touch updated_at on an assessment (called after row batch-sync). */
    public static function touch(int $id): void
    {
        Database::execute(
            'UPDATE assessments SET updated_at = NOW() WHERE id = ?',
            [$id]
        );
    }
}
