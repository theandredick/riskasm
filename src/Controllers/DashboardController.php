<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\View;
use App\Middleware\AuthMiddleware;

class DashboardController
{
    public function index(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        return Response::html(View::render('dashboard/index', [
            'pageTitle' => 'Dashboard',
        ]));
    }
}
