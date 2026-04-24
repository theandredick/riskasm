<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\View;
use App\Middleware\AuthMiddleware;
use App\Models\Assessment;

class DashboardController
{
    public function index(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Session::start();
        $userId = (int) Session::get('user_id');

        $stats  = Assessment::statsForUser($userId);
        $recent = Assessment::recentForUser($userId, 8);

        return Response::html(View::render('dashboard/index', [
            'pageTitle'    => 'Dashboard',
            'stats'        => $stats,
            'recent'       => $recent,
            'statusLabels' => Assessment::STATUS_LABELS,
            'statusColors' => Assessment::STATUS_COLORS,
        ]));
    }
}
