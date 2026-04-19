<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Csrf;
use App\Models\Assessment;
use App\Models\AssessmentRow;

/**
 * JSON API for assessment row CRUD and batch sync.
 * All endpoints require an active session (login).
 * Write endpoints additionally check CSRF via the _csrf body field.
 */
class AssessmentRowApiController
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function requireLogin(): ?Response
    {
        Session::start();
        if (!Session::isLoggedIn()) {
            return Response::json(['error' => 'Unauthorised'], 401);
        }
        return null;
    }

    private function assessmentAccess(int $id, int $userId): ?array
    {
        return Assessment::findForUser($id, $userId);
    }

    private function canWrite(array $assessment, int $userId): bool
    {
        if ((int) $assessment['owner_id'] === $userId) {
            return true;
        }
        return Assessment::sharePermission((int) $assessment['id'], $userId) === 'edit';
    }

    private function verifyCsrf(Request $request): ?Response
    {
        $token = $request->input('_csrf', '');
        if (!Csrf::validate($token)) {
            return Response::json(['error' => 'Invalid CSRF token'], 403);
        }
        return null;
    }

    // ── Endpoints ─────────────────────────────────────────────────────────────

    /**
     * GET /api/assessments/{id}/rows
     * Return all rows for an assessment as JSON.
     */
    public function index(Request $request): Response
    {
        if ($err = $this->requireLogin()) {
            return $err;
        }

        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        if ($this->assessmentAccess($id, $userId) === null) {
            return Response::json(['error' => 'Not found'], 404);
        }

        return Response::json(AssessmentRow::allForAssessment($id));
    }

    /**
     * POST /api/assessments/{id}/rows
     * Create a single new row.
     */
    public function store(Request $request): Response
    {
        if ($err = $this->requireLogin()) {
            return $err;
        }

        if ($err = $this->verifyCsrf($request)) {
            return $err;
        }

        $userId     = (int) Session::get('user_id');
        $id         = (int) $request->param('id');
        $assessment = $this->assessmentAccess($id, $userId);

        if ($assessment === null) {
            return Response::json(['error' => 'Not found'], 404);
        }

        if (!$this->canWrite($assessment, $userId)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        try {
            $rowId = AssessmentRow::create($id, $request->all());
            $row   = AssessmentRow::find($rowId);
            Assessment::touch($id);
            return Response::json(['success' => true, 'row' => $row], 201);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/assessments/{id}/rows/{rowId}
     * Update a single row.
     */
    public function update(Request $request): Response
    {
        if ($err = $this->requireLogin()) {
            return $err;
        }

        if ($err = $this->verifyCsrf($request)) {
            return $err;
        }

        $userId     = (int) Session::get('user_id');
        $id         = (int) $request->param('id');
        $rowId      = (int) $request->param('rowId');
        $assessment = $this->assessmentAccess($id, $userId);

        if ($assessment === null) {
            return Response::json(['error' => 'Not found'], 404);
        }

        if (!$this->canWrite($assessment, $userId)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $existing = AssessmentRow::find($rowId);
        if ($existing === null || (int) $existing['assessment_id'] !== $id) {
            return Response::json(['error' => 'Row not found'], 404);
        }

        try {
            AssessmentRow::update($rowId, $request->all());
            Assessment::touch($id);
            return Response::json(['success' => true, 'row' => AssessmentRow::find($rowId)]);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/assessments/{id}/rows/{rowId}
     * Delete a single row.
     */
    public function destroy(Request $request): Response
    {
        if ($err = $this->requireLogin()) {
            return $err;
        }

        if ($err = $this->verifyCsrf($request)) {
            return $err;
        }

        $userId     = (int) Session::get('user_id');
        $id         = (int) $request->param('id');
        $rowId      = (int) $request->param('rowId');
        $assessment = $this->assessmentAccess($id, $userId);

        if ($assessment === null) {
            return Response::json(['error' => 'Not found'], 404);
        }

        if (!$this->canWrite($assessment, $userId)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $existing = AssessmentRow::find($rowId);
        if ($existing === null || (int) $existing['assessment_id'] !== $id) {
            return Response::json(['error' => 'Row not found'], 404);
        }

        AssessmentRow::delete($rowId);
        Assessment::touch($id);
        return Response::json(['success' => true]);
    }

    /**
     * POST /api/assessments/{id}/rows/reorder
     * Body: { ordered_ids: [1, 2, 3, …] }
     */
    public function reorder(Request $request): Response
    {
        if ($err = $this->requireLogin()) {
            return $err;
        }

        if ($err = $this->verifyCsrf($request)) {
            return $err;
        }

        $userId     = (int) Session::get('user_id');
        $id         = (int) $request->param('id');
        $assessment = $this->assessmentAccess($id, $userId);

        if ($assessment === null) {
            return Response::json(['error' => 'Not found'], 404);
        }

        if (!$this->canWrite($assessment, $userId)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $orderedIds = $request->input('ordered_ids', []);
        if (!is_array($orderedIds)) {
            return Response::json(['error' => 'ordered_ids must be an array'], 400);
        }

        AssessmentRow::reorder($id, $orderedIds);
        Assessment::touch($id);
        return Response::json(['success' => true]);
    }

    /**
     * POST /api/assessments/{id}/sync
     * Batch-sync all rows from localStorage.
     * Body: { rows: [...], deleted_ids: [...], _csrf: '...' }
     */
    public function sync(Request $request): Response
    {
        if ($err = $this->requireLogin()) {
            return $err;
        }

        if ($err = $this->verifyCsrf($request)) {
            return $err;
        }

        $userId     = (int) Session::get('user_id');
        $id         = (int) $request->param('id');
        $assessment = $this->assessmentAccess($id, $userId);

        if ($assessment === null) {
            return Response::json(['error' => 'Not found'], 404);
        }

        if (!$this->canWrite($assessment, $userId)) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $body       = $request->all();
        $rows       = $body['rows']        ?? [];
        $deletedIds = $body['deleted_ids'] ?? [];

        if (!is_array($rows)) {
            return Response::json(['error' => '\'rows\' must be an array'], 400);
        }

        try {
            $fresh = AssessmentRow::batchSync($id, $rows, $deletedIds);
            return Response::json(['success' => true, 'rows' => $fresh]);
        } catch (\Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
