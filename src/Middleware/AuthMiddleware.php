<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public static function require(Request $request): ?Response
    {
        Session::start();

        if (!Session::isLoggedIn()) {
            $returnUrl = urlencode($request->path());
            return Response::redirect("/auth/login?return=$returnUrl");
        }

        return null;
    }
}
