<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();

        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }

        return Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        $token = self::token();
        return "<input type=\"hidden\" name=\"_csrf\" value=\"" . htmlspecialchars($token, ENT_QUOTES) . "\">";
    }

    public static function validate(string $submitted): bool
    {
        $expected = Session::get(self::TOKEN_KEY, '');
        return hash_equals($expected, $submitted);
    }

    public static function verifyOrAbort(string $submitted): void
    {
        if (!self::validate($submitted)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }
}
