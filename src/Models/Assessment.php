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
     * Dashboard quick-stats for the given user.
     * Returns: total, drafts, shared_with_me, overdue_reviews.
     */
    public static function statsForUser(int $userId): array
    {
        $total = (int) Database::fetchScalar(
            'SELECT COUNT(*)
             FROM assessments a
             WHERE a.owner_id = ?
                OR EXISTS (
                    SELECT 1 FROM assessment_shares s
                    WHERE s.assessment_id = a.id AND s.shared_with_user_id = ?
                )',
            [$userId, $userId]
        );

        $drafts = (int) Database::fetchScalar(
            "SELECT COUNT(*) FROM assessments
             WHERE owner_id = ? AND status = 'draft'",
            [$userId]
        );

        $sharedWithMe = (int) Database::fetchScalar(
            'SELECT COUNT(*)
             FROM assessments a
             JOIN assessment_shares s ON s.assessment_id = a.id
             WHERE s.shared_with_user_id = ? AND a.owner_id != ?',
            [$userId, $userId]
        );

        $overdue = (int) Database::fetchScalar(
            "SELECT COUNT(*)
             FROM assessments
             WHERE owner_id = ?
               AND review_date IS NOT NULL
               AND review_date < CURRENT_DATE
               AND status NOT IN ('archived')",
            [$userId]
        );

        return [
            'total'          => $total,
            'drafts'         => $drafts,
            'shared_with_me' => $sharedWithMe,
            'overdue'        => $overdue,
        ];
    }

    /**
     * Return the most-recently-updated $limit assessments for the dashboard widget.
     * Includes highest_risk_category + highest_risk_colour from assessment rows.
     */
    public static function recentForUser(int $userId, int $limit = 8): array
    {
        $rows = Database::fetchAll(
            'SELECT a.id, a.title, a.reference_number, a.status, a.updated_at,
                    a.owner_id, rm.name AS matrix_name,
                    (a.owner_id = ?) AS is_owned,
                    (SELECT ar.risk_category
                     FROM assessment_rows ar
                     WHERE ar.assessment_id = a.id
                       AND ar.severity_value IS NOT NULL
                       AND ar.likelihood_value IS NOT NULL
                     ORDER BY (ar.severity_value * ar.likelihood_value) DESC
                     LIMIT 1) AS highest_risk_category,
                    (SELECT ar.colour_hex
                     FROM assessment_rows ar
                     WHERE ar.assessment_id = a.id
                       AND ar.severity_value IS NOT NULL
                       AND ar.likelihood_value IS NOT NULL
                     ORDER BY (ar.severity_value * ar.likelihood_value) DESC
                     LIMIT 1) AS highest_risk_colour
             FROM assessments a
             JOIN risk_matrices rm ON rm.id = a.matrix_id
             WHERE a.owner_id = ?
                OR EXISTS (
                    SELECT 1 FROM assessment_shares s
                    WHERE s.assessment_id = a.id AND s.shared_with_user_id = ?
                )
             ORDER BY a.updated_at DESC
             LIMIT ?',
            [$userId, $userId, $userId, $limit]
        );

        return $rows;
    }

    /**
     * Return all assessments visible to $userId with optional search, status filter,
     * and sort order. Includes highest risk from rows.
     *
     * @param string $sort   Column key: title|created_at|updated_at|status|row_count
     * @param string $dir    asc|desc
     * @param string $search ILIKE search across title, reference_number, and hazard text
     * @param string $status Filter by status value, or '' for all
     */
    public static function findAllFiltered(
        int    $userId,
        string $sort    = 'updated_at',
        string $dir     = 'desc',
        string $search  = '',
        string $status  = '',
        bool   $overdue = false,
    ): array {
        $allowed = ['title', 'created_at', 'updated_at', 'review_date', 'status', 'row_count'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'review_date';
        }
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $orderClause = match ($sort) {
            'title'       => "a.title {$dir}",
            'created_at'  => "a.created_at {$dir}",
            'updated_at'  => "a.updated_at {$dir}",
            'review_date' => "a.review_date IS NULL, a.review_date {$dir}",
            'status'      => "a.status {$dir}",
            'row_count'   => "(SELECT COUNT(*) FROM assessment_rows WHERE assessment_id = a.id) {$dir}",
            default       => "a.review_date IS NULL, a.review_date ASC",
        };

        // First param is for is_owned in SELECT; remainder are for WHERE
        $params = [$userId, $userId, $userId];
        $where  = ['(a.owner_id = ? OR EXISTS (SELECT 1 FROM assessment_shares s WHERE s.assessment_id = a.id AND s.shared_with_user_id = ?))'];

        if ($overdue) {
            $where[] = 'a.review_date IS NOT NULL';
            $where[] = 'a.review_date < CURRENT_DATE';
            $where[] = "a.status NOT IN ('approved', 'archived')";
        } elseif ($status !== '' && in_array($status, self::STATUSES, true)) {
            $where[]  = 'a.status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(a.title ILIKE ? OR a.reference_number ILIKE ? OR EXISTS (SELECT 1 FROM assessment_rows ar WHERE ar.assessment_id = a.id AND (ar.hazard ILIKE ? OR ar.effect ILIKE ?)))';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSQL = implode(' AND ', $where);

        $rows = Database::fetchAll(
            "SELECT a.*,
                    rm.name AS matrix_name,
                    u.display_name AS owner_name,
                    (a.owner_id = ?) AS is_owned,
                    (SELECT COUNT(*) FROM assessment_rows WHERE assessment_id = a.id) AS row_count,
                    (SELECT ar.risk_category
                     FROM assessment_rows ar
                     WHERE ar.assessment_id = a.id
                       AND ar.severity_value IS NOT NULL
                       AND ar.likelihood_value IS NOT NULL
                     ORDER BY (ar.severity_value * ar.likelihood_value) DESC
                     LIMIT 1) AS highest_risk_category,
                    (SELECT ar.colour_hex
                     FROM assessment_rows ar
                     WHERE ar.assessment_id = a.id
                       AND ar.severity_value IS NOT NULL
                       AND ar.likelihood_value IS NOT NULL
                     ORDER BY (ar.severity_value * ar.likelihood_value) DESC
                     LIMIT 1) AS highest_risk_colour
             FROM assessments a
             JOIN risk_matrices rm ON rm.id = a.matrix_id
             JOIN users u ON u.id = a.owner_id
             WHERE {$whereSQL}
             ORDER BY {$orderClause}",
            $params
        );

        foreach ($rows as &$row) {
            $row['column_config'] = json_decode($row['column_config'], true)
                ?? self::columnConfigForTemplate($row['template_type']);
        }

        return $rows;
    }

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
                        WHERE s.assessment_id = a.id AND s.shared_with_user_id = ?
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
                    WHERE s.assessment_id = a.id AND s.shared_with_user_id = ?
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
             WHERE assessment_id = ? AND shared_with_user_id = ?',
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
