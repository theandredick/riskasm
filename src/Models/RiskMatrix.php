<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class RiskMatrix
{
    // ── Queries ───────────────────────────────────────────────────────────────

    /**
     * Return all matrices visible to a given user:
     * - all system/public matrices
     * - matrices owned by the user
     */
    public static function findAllForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT rm.*,
                    (SELECT COUNT(*) FROM matrix_levels
                      WHERE matrix_id = rm.id AND axis = \'severity\') AS severity_count,
                    (SELECT COUNT(*) FROM matrix_levels
                      WHERE matrix_id = rm.id AND axis = \'likelihood\') AS likelihood_count
             FROM risk_matrices rm
             WHERE rm.is_system = TRUE
                OR rm.is_public = TRUE
                OR rm.owner_id = :uid
             ORDER BY rm.is_system DESC, rm.name',
            ['uid' => $userId]
        );
    }

    /** Return all system matrices (read-only built-ins). */
    public static function findSystem(): array
    {
        return Database::fetchAll(
            'SELECT rm.*,
                    (SELECT COUNT(*) FROM matrix_levels
                      WHERE matrix_id = rm.id AND axis = \'severity\') AS severity_count,
                    (SELECT COUNT(*) FROM matrix_levels
                      WHERE matrix_id = rm.id AND axis = \'likelihood\') AS likelihood_count
             FROM risk_matrices rm
             WHERE rm.is_system = TRUE
             ORDER BY rm.id'
        );
    }

    /** Return a single matrix row by ID. */
    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM risk_matrices WHERE id = ?',
            [$id]
        );
    }

    /**
     * Check that $userId is allowed to view matrix $id.
     * Returns the matrix row or null.
     */
    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM risk_matrices
             WHERE id = ?
               AND (is_system = TRUE OR is_public = TRUE OR owner_id = ?)',
            [$id, $userId]
        );
    }

    // ── Levels ────────────────────────────────────────────────────────────────

    /** Severity levels for a matrix, ordered lowest → highest. */
    public static function severityLevels(int $matrixId): array
    {
        return Database::fetchAll(
            'SELECT * FROM matrix_levels
             WHERE matrix_id = ? AND axis = \'severity\'
             ORDER BY level_value',
            [$matrixId]
        );
    }

    /** Likelihood levels for a matrix, ordered lowest → highest. */
    public static function likelihoodLevels(int $matrixId): array
    {
        return Database::fetchAll(
            'SELECT * FROM matrix_levels
             WHERE matrix_id = ? AND axis = \'likelihood\'
             ORDER BY level_value',
            [$matrixId]
        );
    }

    // ── Risk bands ────────────────────────────────────────────────────────────

    /** All risk bands for a matrix, ordered lowest → highest risk. */
    public static function riskBands(int $matrixId): array
    {
        return Database::fetchAll(
            'SELECT * FROM matrix_risk_bands
             WHERE matrix_id = ?
             ORDER BY sort_order',
            [$matrixId]
        );
    }

    // ── Cells ─────────────────────────────────────────────────────────────────

    /**
     * All cells for a matrix keyed as "{severity_value}_{likelihood_value}".
     * Joins the band to include full band details.
     */
    public static function cellsKeyed(int $matrixId): array
    {
        $rows = Database::fetchAll(
            'SELECT mc.*, mrb.band_label, mrb.band_name, mrb.short_description AS band_short_description
             FROM matrix_cells mc
             LEFT JOIN matrix_risk_bands mrb ON mrb.id = mc.risk_band_id
             WHERE mc.matrix_id = ?
             ORDER BY mc.severity_value, mc.likelihood_value',
            [$matrixId]
        );

        $keyed = [];
        foreach ($rows as $row) {
            $keyed[$row['severity_value'] . '_' . $row['likelihood_value']] = $row;
        }

        return $keyed;
    }

    /** Look up a single cell by severity and likelihood values. */
    public static function cell(int $matrixId, int $severity, int $likelihood): ?array
    {
        return Database::fetchOne(
            'SELECT mc.*, mrb.band_label, mrb.band_name,
                    mrb.short_description AS band_short_description,
                    mrb.full_description  AS band_full_description
             FROM matrix_cells mc
             LEFT JOIN matrix_risk_bands mrb ON mrb.id = mc.risk_band_id
             WHERE mc.matrix_id = ? AND mc.severity_value = ? AND mc.likelihood_value = ?',
            [$matrixId, $severity, $likelihood]
        );
    }

    // ── Consequence categories + descriptions ─────────────────────────────────

    /**
     * Return consequence categories with all per-level descriptions nested.
     *
     * Returns:
     * [
     *   ['id' => ..., 'name' => ..., 'sort_order' => ...,
     *    'descriptions' => [ severity_level_value => text, ... ]],
     *   ...
     * ]
     */
    public static function categoriesWithDescriptions(int $matrixId): array
    {
        $categories = Database::fetchAll(
            'SELECT * FROM matrix_consequence_categories
             WHERE matrix_id = ?
             ORDER BY sort_order',
            [$matrixId]
        );

        if (empty($categories)) {
            return [];
        }

        $descs = Database::fetchAll(
            'SELECT * FROM matrix_level_category_descriptions
             WHERE matrix_id = ?
             ORDER BY category_id, severity_level_value',
            [$matrixId]
        );

        $descMap = [];
        foreach ($descs as $d) {
            $descMap[$d['category_id']][$d['severity_level_value']] = $d['description'];
        }

        foreach ($categories as &$cat) {
            $cat['descriptions'] = $descMap[$cat['id']] ?? [];
        }

        return $categories;
    }

    // ── Full matrix data bundle ───────────────────────────────────────────────

    /**
     * Return everything needed to render a matrix view:
     * matrix + severity levels + likelihood levels + bands + cells + categories.
     */
    public static function fullData(int $matrixId, int $userId): ?array
    {
        $matrix = self::findForUser($matrixId, $userId);
        if ($matrix === null) {
            return null;
        }

        $matrix['severity_levels']   = self::severityLevels($matrixId);
        $matrix['likelihood_levels']  = self::likelihoodLevels($matrixId);
        $matrix['bands']              = self::riskBands($matrixId);
        $matrix['cells']              = self::cellsKeyed($matrixId);
        $matrix['categories']         = self::categoriesWithDescriptions($matrixId);

        return $matrix;
    }

    /**
     * Same as fullData but without access control (for API use after
     * the controller has already verified access).
     */
    public static function fullDataById(int $matrixId): ?array
    {
        $matrix = self::find($matrixId);
        if ($matrix === null) {
            return null;
        }

        $matrix['severity_levels']   = self::severityLevels($matrixId);
        $matrix['likelihood_levels']  = self::likelihoodLevels($matrixId);
        $matrix['bands']              = self::riskBands($matrixId);
        $matrix['cells']              = self::cellsKeyed($matrixId);
        $matrix['categories']         = self::categoriesWithDescriptions($matrixId);

        return $matrix;
    }

    // ── Clone (copy for a user) ───────────────────────────────────────────────

    /**
     * Clone an existing matrix into a new user-owned copy.
     * Copies: matrix record, levels, bands, cells, consequence categories
     * and level category descriptions.
     * Returns the new matrix ID.
     */
    public static function cloneForUser(int $sourceId, int $userId): int
    {
        $src = self::find($sourceId);
        if ($src === null) {
            throw new \RuntimeException("Source matrix {$sourceId} not found.");
        }

        Database::beginTransaction();

        try {
            // Clone the matrix record
            $newId = Database::insert(
                'INSERT INTO risk_matrices
                    (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
                 VALUES (?, ?, ?, ?, ?, FALSE, FALSE)',
                [
                    $userId,
                    'Copy of ' . $src['name'],
                    $src['description'],
                    $src['severity_axis_label'],
                    $src['likelihood_axis_label'],
                ]
            );

            // Clone levels
            $levels = Database::fetchAll(
                'SELECT * FROM matrix_levels WHERE matrix_id = ?',
                [$sourceId]
            );
            foreach ($levels as $lev) {
                Database::execute(
                    'INSERT INTO matrix_levels
                        (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $newId,
                        $lev['axis'],
                        $lev['level_value'],
                        $lev['label'],
                        $lev['one_word'],
                        $lev['quantitative_range'],
                        $lev['description'],
                        $lev['sort_order'],
                    ]
                );
            }

            // Clone bands, keep a mapping old_id → new_id for cell references
            $bands   = Database::fetchAll(
                'SELECT * FROM matrix_risk_bands WHERE matrix_id = ? ORDER BY sort_order',
                [$sourceId]
            );
            $bandMap = [];
            foreach ($bands as $band) {
                $newBandId = Database::insert(
                    'INSERT INTO matrix_risk_bands
                        (matrix_id, band_label, band_name, score_min, score_max,
                         colour_hex, short_description, full_description, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $newId,
                        $band['band_label'],
                        $band['band_name'],
                        $band['score_min'],
                        $band['score_max'],
                        $band['colour_hex'],
                        $band['short_description'],
                        $band['full_description'],
                        $band['sort_order'],
                    ]
                );
                $bandMap[$band['id']] = $newBandId;
            }

            // Clone cells
            $cells = Database::fetchAll(
                'SELECT * FROM matrix_cells WHERE matrix_id = ?',
                [$sourceId]
            );
            foreach ($cells as $cell) {
                Database::execute(
                    'INSERT INTO matrix_cells
                        (matrix_id, severity_value, likelihood_value,
                         risk_band_id, risk_category, colour_hex, numeric_score)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $newId,
                        $cell['severity_value'],
                        $cell['likelihood_value'],
                        isset($cell['risk_band_id']) ? $bandMap[$cell['risk_band_id']] ?? null : null,
                        $cell['risk_category'],
                        $cell['colour_hex'],
                        $cell['numeric_score'],
                    ]
                );
            }

            // Clone consequence categories + descriptions
            $cats = Database::fetchAll(
                'SELECT * FROM matrix_consequence_categories WHERE matrix_id = ? ORDER BY sort_order',
                [$sourceId]
            );
            $catMap = [];
            foreach ($cats as $cat) {
                $newCatId = Database::insert(
                    'INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order)
                     VALUES (?, ?, ?)',
                    [$newId, $cat['name'], $cat['sort_order']]
                );
                $catMap[$cat['id']] = $newCatId;
            }

            $descRows = Database::fetchAll(
                'SELECT * FROM matrix_level_category_descriptions WHERE matrix_id = ?',
                [$sourceId]
            );
            foreach ($descRows as $dr) {
                if (!isset($catMap[$dr['category_id']])) {
                    continue;
                }
                Database::execute(
                    'INSERT INTO matrix_level_category_descriptions
                        (matrix_id, severity_level_value, category_id, description)
                     VALUES (?, ?, ?, ?)',
                    [
                        $newId,
                        $dr['severity_level_value'],
                        $catMap[$dr['category_id']],
                        $dr['description'],
                    ]
                );
            }

            Database::commit();
            return $newId;
        } catch (\Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    // ── Delete (user-owned only) ───────────────────────────────────────────────

    /**
     * Delete a user-owned matrix.  Cascades to levels, bands, cells, categories.
     */
    public static function deleteOwned(int $id, int $userId): bool
    {
        $affected = Database::execute(
            'DELETE FROM risk_matrices WHERE id = ? AND owner_id = ? AND is_system = FALSE',
            [$id, $userId]
        );
        return $affected > 0;
    }
}
