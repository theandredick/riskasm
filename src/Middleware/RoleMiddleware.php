<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class RoleMiddleware
{
    private const ROLE_RANK = [
        'viewer'   => 1,
        'assessor' => 2,
        'manager'  => 3,
        'admin'    => 4,
    ];

    /**
     * Require the current user's role to be at least $minimumRole.
     * Returns a forbidden Response if not; null if allowed.
     */
    public static function require(string $minimumRole): ?Response
    {
        $userRole = Session::userRole() ?? 'viewer';
        $required = self::ROLE_RANK[$minimumRole] ?? 1;
        $actual   = self::ROLE_RANK[$userRole]    ?? 0;

        if ($actual < $required) {
            return Response::forbidden('You do not have permission to access this page.');
        }

        return null;
    }
}
