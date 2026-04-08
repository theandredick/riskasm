<?php

declare(strict_types=1);

// Application-wide constants derived from .env
define('APP_NAME',  $_ENV['APP_NAME']  ?? 'Smart Risk Assessment');
define('APP_URL',   $_ENV['APP_URL']   ?? 'http://localhost:10000');
define('APP_ENV',   $_ENV['APP_ENV']   ?? 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

// Error reporting — verbose in local, silent in production
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/app.log');
}

// Session
ini_set('session.name',            $_ENV['SESSION_NAME']     ?? 'riskasm_session');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
if (str_starts_with(APP_URL, 'https://')) {
    ini_set('session.cookie_secure', '1');
}
