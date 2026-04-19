<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Validator;
use App\Helpers\View;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\Assessment;
use App\Models\AssessmentRow;
use App\Models\RiskMatrix;

class AssessmentController
{
    /**
     * GET /assessments
     * List all assessments visible to the current user.
     */
    public function index(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Session::start();
        $userId      = (int) Session::get('user_id');
        $assessments = Assessment::findAllForUser($userId);

        return Response::html(View::render('assessments/index', [
            'pageTitle'     => 'My Assessments',
            'assessments'   => $assessments,
            'csrf'          => Csrf::token(),
            'statusLabels'  => Assessment::STATUS_LABELS,
            'statusColors'  => Assessment::STATUS_COLORS,
            'templateLabels'=> Assessment::TEMPLATE_LABELS,
        ]));
    }

    /**
     * GET /assessments/new
     * Show the new-assessment form.
     */
    public function create(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        if ($denied = RoleMiddleware::require('assessor')) {
            return $denied;
        }

        Session::start();
        $userId   = (int) Session::get('user_id');
        $matrices = RiskMatrix::findAllForUser($userId);

        return Response::html(View::render('assessments/new', [
            'pageTitle'            => 'New Assessment',
            'matrices'             => $matrices,
            'templateTypes'        => Assessment::TEMPLATE_TYPES,
            'templateLabels'       => Assessment::TEMPLATE_LABELS,
            'templateDescriptions' => Assessment::TEMPLATE_DESCRIPTIONS,
            'csrf'                 => Csrf::token(),
            'errors'               => [],
            'old'                  => [],
        ]));
    }

    /**
     * POST /assessments/new
     * Validate and create a new assessment, then redirect to the editor.
     */
    public function store(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        if ($denied = RoleMiddleware::require('assessor')) {
            return $denied;
        }

        Session::start();
        $userId = (int) Session::get('user_id');

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $data = $request->all();

        $v = (new Validator($data))
            ->required('title',         'Assessment title')
            ->maxLength('title', 200,   'Assessment title')
            ->required('matrix_id',     'Risk matrix')
            ->in('template_type', Assessment::TEMPLATE_TYPES, 'Template type');

        if ($v->fails()) {
            $matrices = RiskMatrix::findAllForUser($userId);
            return Response::html(View::render('assessments/new', [
                'pageTitle'            => 'New Assessment',
                'matrices'             => $matrices,
                'templateTypes'        => Assessment::TEMPLATE_TYPES,
                'templateLabels'       => Assessment::TEMPLATE_LABELS,
                'templateDescriptions' => Assessment::TEMPLATE_DESCRIPTIONS,
                'csrf'                 => Csrf::token(),
                'errors'               => $v->errors(),
                'old'                  => $data,
            ]));
        }

        if (RiskMatrix::findForUser((int) $data['matrix_id'], $userId) === null) {
            Session::flash('error', 'Selected risk matrix not found or not accessible.');
            return Response::redirect('/assessments/new');
        }

        try {
            $id = Assessment::create($userId, $data);
            Session::flash('success', 'Assessment created. Add hazard rows below.');
            return Response::redirect("/assessments/{$id}");
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to create assessment: ' . $e->getMessage());
            return Response::redirect('/assessments/new');
        }
    }

    /**
     * GET /assessments/{id}
     * Show the assessment editor.
     */
    public function edit(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Session::start();
        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        $assessment = Assessment::findForUser($id, $userId);
        if ($assessment === null) {
            Session::flash('error', 'Assessment not found or you do not have access.');
            return Response::redirect('/assessments');
        }

        $isOwner = (int) $assessment['owner_id'] === $userId;
        $canEdit = $isOwner || Assessment::sharePermission($id, $userId) === 'edit';

        // Full matrix data for client-side cell lookup
        $matrix = RiskMatrix::fullDataById((int) $assessment['matrix_id']);

        $rows        = AssessmentRow::allForAssessment($id);
        $transitions = Assessment::STATUS_TRANSITIONS[$assessment['status']] ?? [];

        return Response::html(View::render('assessments/editor', [
            'pageTitle'      => $assessment['title'],
            'assessment'     => $assessment,
            'matrix'         => $matrix,
            'rows'           => $rows,
            'isOwner'        => $isOwner,
            'canEdit'        => $canEdit,
            'transitions'    => $transitions,
            'controlTypes'   => AssessmentRow::CONTROL_TYPES,
            'csrf'           => Csrf::token(),
            'statusLabels'   => Assessment::STATUS_LABELS,
            'statusColors'   => Assessment::STATUS_COLORS,
            'templateLabels' => Assessment::TEMPLATE_LABELS,
        ]));
    }

    /**
     * POST /assessments/{id}
     * Update the assessment header fields (owner only).
     */
    public function update(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Session::start();
        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $assessment = Assessment::findForUser($id, $userId);
        if ($assessment === null || (int) $assessment['owner_id'] !== $userId) {
            Session::flash('error', 'Assessment not found or you do not have permission to edit it.');
            return Response::redirect('/assessments');
        }

        $data = $request->all();
        $v    = (new Validator($data))
            ->required('title', 'Assessment title')
            ->maxLength('title', 200, 'Assessment title');

        if ($v->fails()) {
            $msgs = array_merge(...array_values($v->errors()));
            Session::flash('error', implode(' ', $msgs));
            return Response::redirect("/assessments/{$id}");
        }

        Assessment::update($id, $userId, $data);
        Session::flash('success', 'Assessment details updated.');
        return Response::redirect("/assessments/{$id}");
    }

    /**
     * POST /assessments/{id}/delete
     * Delete an assessment (owner only).
     */
    public function destroy(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Session::start();
        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $assessment = Assessment::findForUser($id, $userId);
        if ($assessment === null || (int) $assessment['owner_id'] !== $userId) {
            Session::flash('error', 'Assessment not found or you do not own it.');
            return Response::redirect('/assessments');
        }

        Assessment::delete($id, $userId);
        Session::flash('success', 'Assessment deleted.');
        return Response::redirect('/assessments');
    }

    /**
     * POST /assessments/{id}/copy
     * Duplicate an assessment (owner or share-accessible, assessor+ role).
     */
    public function copy(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        if ($denied = RoleMiddleware::require('assessor')) {
            return $denied;
        }

        Session::start();
        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $assessment = Assessment::findForUser($id, $userId);
        if ($assessment === null) {
            Session::flash('error', 'Assessment not found or you do not have access.');
            return Response::redirect('/assessments');
        }

        try {
            $newId = Assessment::copy($id, $userId);
            Session::flash('success', 'Assessment duplicated. You are now editing the copy.');
            return Response::redirect("/assessments/{$newId}");
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to duplicate assessment: ' . $e->getMessage());
            return Response::redirect("/assessments/{$id}");
        }
    }

    /**
     * POST /assessments/{id}/status
     * Transition the assessment status following the workflow (owner only).
     */
    public function updateStatus(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Session::start();
        $userId    = (int) Session::get('user_id');
        $id        = (int) $request->param('id');
        $newStatus = trim($request->input('status', ''));

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        if (!in_array($newStatus, Assessment::STATUSES, true)) {
            Session::flash('error', 'Invalid status value.');
            return Response::redirect("/assessments/{$id}");
        }

        if (Assessment::updateStatus($id, $userId, $newStatus)) {
            $label = Assessment::STATUS_LABELS[$newStatus] ?? $newStatus;
            Session::flash('success', "Status changed to: {$label}.");
        } else {
            Session::flash('error', 'Status change not permitted — check workflow rules.');
        }

        return Response::redirect("/assessments/{$id}");
    }
}
