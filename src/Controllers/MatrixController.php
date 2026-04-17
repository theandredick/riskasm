<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\RiskMatrix;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

class MatrixController
{
    /**
     * GET /matrices
     * Display all system matrices plus any owned by the current user.
     */
    public function index(Request $request): Response
    {
        if ($redirect = AuthMiddleware::require($request)) {
            return $redirect;
        }

        Session::start();
        $userId   = (int) Session::get('user_id');
        $matrices = RiskMatrix::findAllForUser($userId);
        $canClone = RoleMiddleware::require('assessor') === null;

        return Response::html(View::render('matrices/index', [
            'pageTitle' => 'Risk Matrices',
            'matrices'  => $matrices,
            'canClone'  => $canClone,
        ]));
    }

    /**
     * GET /matrices/{id}
     * Show a single matrix: colour-coded grid, band reference, level descriptions.
     */
    public function show(Request $request): Response
    {
        if ($redirect = AuthMiddleware::require($request)) {
            return $redirect;
        }

        Session::start();
        $userId   = (int) Session::get('user_id');
        $id       = (int) $request->param('id');
        $canClone = RoleMiddleware::require('assessor') === null;

        $matrix = RiskMatrix::fullData($id, $userId);

        if ($matrix === null) {
            Session::flash('error', 'Matrix not found or you do not have access to it.');
            return Response::redirect('/matrices');
        }

        return Response::html(View::render('matrices/view', [
            'pageTitle' => $matrix['name'],
            'matrix'    => $matrix,
            'csrf'      => Csrf::token(),
            'canClone'  => $canClone,
        ]));
    }

    /**
     * POST /matrices/{id}/copy
     * Clone a matrix to create a user-owned editable copy. Requires assessor+.
     */
    public function copy(Request $request): Response
    {
        if ($redirect = AuthMiddleware::require($request)) {
            return $redirect;
        }

        if ($denied = RoleMiddleware::require('assessor')) {
            return $denied;
        }

        Session::start();
        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        // Verify access
        if (RiskMatrix::findForUser($id, $userId) === null) {
            Session::flash('error', 'Matrix not found or you do not have access to it.');
            return Response::redirect('/matrices');
        }

        $cloneName = trim($request->input('clone_name', ''));

        try {
            $newId = RiskMatrix::cloneForUser($id, $userId, $cloneName ?: null);
            Session::flash('success', 'Matrix cloned successfully. You can now customise your copy.');
            return Response::redirect("/matrices/{$newId}");
        } catch (\Throwable $e) {
            Session::flash('error', 'Failed to clone matrix: ' . $e->getMessage());
            return Response::redirect("/matrices/{$id}");
        }
    }

    /**
     * POST /matrices/{id}/delete
     * Delete a user-owned matrix (system matrices are protected). Requires assessor+.
     */
    public function destroy(Request $request): Response
    {
        if ($redirect = AuthMiddleware::require($request)) {
            return $redirect;
        }

        if ($denied = RoleMiddleware::require('assessor')) {
            return $denied;
        }

        Session::start();
        $userId = (int) Session::get('user_id');
        $id     = (int) $request->param('id');

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $matrix = RiskMatrix::find($id);

        if ($matrix === null) {
            Session::flash('error', 'Matrix not found.');
            return Response::redirect('/matrices');
        }

        if ($matrix['is_system']) {
            Session::flash('error', 'System matrices cannot be deleted. Clone it first to create your own copy.');
            return Response::redirect("/matrices/{$id}");
        }

        if ((int) $matrix['owner_id'] !== $userId) {
            Session::flash('error', 'You do not own this matrix.');
            return Response::redirect('/matrices');
        }

        RiskMatrix::deleteOwned($id, $userId);
        Session::flash('success', 'Matrix deleted.');
        return Response::redirect('/matrices');
    }

    // ── Stubs for Phase 2 custom builder ──────────────────────────────────────

    public function create(Request $request): Response
    {
        if ($redirect = AuthMiddleware::require($request)) {
            return $redirect;
        }

        if ($denied = RoleMiddleware::require('assessor')) {
            return $denied;
        }

        Session::flash('info', 'The custom matrix builder is coming in Phase 2.');
        return Response::redirect('/matrices');
    }

    public function store(Request $request): Response
    {
        return $this->create($request);
    }

    public function edit(Request $request): Response
    {
        return $this->create($request);
    }

    public function update(Request $request): Response
    {
        return $this->create($request);
    }
}
