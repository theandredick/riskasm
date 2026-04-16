<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\RiskMatrix;

/**
 * JSON API for risk matrix data used by the assessment editor and matrix viewer.
 */
class MatrixApiController
{
    /**
     * GET /api/matrices/{id}
     * Returns the full matrix bundle: levels, bands, cells, consequence categories.
     */
    public function show(Request $request): Response
    {
        Session::start();

        if (!Session::isLoggedIn()) {
            return Response::json(['error' => 'Unauthorised'], 401);
        }

        $id = (int) $request->param('id');
        if ($id < 1) {
            return Response::json(['error' => 'Invalid matrix ID'], 400);
        }

        $userId = (int) Session::get('user_id');
        $matrix = RiskMatrix::fullData($id, $userId);

        if ($matrix === null) {
            return Response::json(['error' => 'Matrix not found'], 404);
        }

        return Response::json($matrix);
    }

    /**
     * GET /api/matrices/{id}/cell?severity=N&likelihood=N
     * Returns a single cell's risk_category, colour_hex, and band details.
     */
    public function cell(Request $request): Response
    {
        Session::start();

        if (!Session::isLoggedIn()) {
            return Response::json(['error' => 'Unauthorised'], 401);
        }

        $id         = (int) $request->param('id');
        $severity   = (int) ($request->query('severity')   ?? 0);
        $likelihood = (int) ($request->query('likelihood') ?? 0);

        if ($id < 1 || $severity < 1 || $likelihood < 1) {
            return Response::json(['error' => 'Invalid parameters'], 400);
        }

        $userId = (int) Session::get('user_id');

        // Verify access to the matrix first
        if (RiskMatrix::findForUser($id, $userId) === null) {
            return Response::json(['error' => 'Matrix not found'], 404);
        }

        $cell = RiskMatrix::cell($id, $severity, $likelihood);

        if ($cell === null) {
            return Response::json(['error' => 'Cell not found'], 404);
        }

        return Response::json($cell);
    }
}
