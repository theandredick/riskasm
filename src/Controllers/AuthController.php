<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\Validator;
use App\Helpers\View;
use App\Models\User;

class AuthController
{
    private const REMEMBER_COOKIE   = 'riskasm_remember';
    private const REMEMBER_DAYS     = 30;
    private const REMEMBER_LIFETIME = self::REMEMBER_DAYS * 24 * 3600;

    // ── Login ─────────────────────────────────────────────────────────────────

    public function showLogin(Request $request): Response
    {
        if (Session::isLoggedIn()) {
            return Response::redirect('/');
        }

        return Response::html(View::render('auth/login', [
            'pageTitle' => 'Log In',
            'returnUrl' => $request->query('return', '/'),
        ], 'auth'));
    }

    public function login(Request $request): Response
    {
        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $data = $request->all();
        $v    = (new Validator($data))
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password');

        if ($v->fails()) {
            return Response::html(View::render('auth/login', [
                'pageTitle' => 'Log In',
                'errors'    => $v->errors(),
                'old'       => $data,
                'returnUrl' => $request->input('return_url', '/'),
            ], 'auth'));
        }

        $user = User::findByEmail($data['email']);

        if ($user === null || !User::verifyPassword($user, $data['password'])) {
            return Response::html(View::render('auth/login', [
                'pageTitle'  => 'Log In',
                'formError'  => 'Invalid email address or password.',
                'old'        => $data,
                'returnUrl'  => $request->input('return_url', '/'),
            ], 'auth'));
        }

        if (!$user['is_active']) {
            return Response::html(View::render('auth/login', [
                'pageTitle'  => 'Log In',
                'formError'  => 'Your account has been disabled. Please contact an administrator.',
                'old'        => $data,
                'returnUrl'  => $request->input('return_url', '/'),
            ], 'auth'));
        }

        self::loginUser($user);
        User::touchLastLogin((int) $user['id']);

        if (!empty($data['remember_me'])) {
            $rawToken = User::createRememberToken((int) $user['id']);
            self::setRememberCookie($rawToken);
        }

        $returnUrl = $request->input('return_url', '/');
        if (!str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
            $returnUrl = '/';
        }

        return Response::redirect($returnUrl);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(Request $request): Response
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if ($cookie !== null) {
            User::deleteRememberToken($cookie);
            self::clearRememberCookie();
        }

        Session::destroy();

        return Response::redirect('/auth/login');
    }

    // ── Register ──────────────────────────────────────────────────────────────

    public function showRegister(Request $request): Response
    {
        if (Session::isLoggedIn()) {
            return Response::redirect('/');
        }

        return Response::html(View::render('auth/register', [
            'pageTitle' => 'Create Account',
        ], 'auth'));
    }

    public function register(Request $request): Response
    {
        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $data = $request->all();
        $v    = (new Validator($data))
            ->required('display_name', 'Display name')
            ->maxLength('display_name', 100, 'Display name')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password')
            ->minLength('password', 8, 'Password')
            ->required('password_confirm', 'Password confirmation');

        $errors = $v->errors();

        if (!$v->fails() && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'][] = 'Passwords do not match.';
        }

        if (!$v->fails() && empty($errors['email']) && User::emailExists($data['email'])) {
            $errors['email'][] = 'That email address is already registered.';
        }

        if (!empty($errors)) {
            return Response::html(View::render('auth/register', [
                'pageTitle' => 'Create Account',
                'errors'    => $errors,
                'old'       => $data,
            ], 'auth'));
        }

        // First user becomes admin automatically
        $role   = User::count() === 0 ? 'admin' : 'assessor';
        $userId = User::create($data['email'], $data['display_name'], $data['password'], $role);
        $user   = User::findById($userId);

        self::loginUser($user);
        User::touchLastLogin($userId);

        Session::flash('success', 'Welcome! Your account has been created.');

        return Response::redirect('/');
    }

    // ── Forgot password ───────────────────────────────────────────────────────

    public function showForgotPassword(Request $request): Response
    {
        return Response::html(View::render('auth/forgot-password', [
            'pageTitle' => 'Reset Password',
        ], 'auth'));
    }

    public function forgotPassword(Request $request): Response
    {
        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $email = strtolower(trim($request->input('email', '')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return Response::html(View::render('auth/forgot-password', [
                'pageTitle'  => 'Reset Password',
                'formError'  => 'Please enter a valid email address.',
                'old'        => ['email' => $email],
            ], 'auth'));
        }

        // Always show success to prevent user enumeration
        $user = User::findByEmail($email);
        if ($user !== null && $user['is_active']) {
            $rawToken = User::createResetToken((int) $user['id']);
            $resetUrl = APP_URL . '/auth/reset-password/' . $rawToken;

            Mailer::send(
                $user['email'],
                $user['display_name'],
                'Reset your Smart Risk Assessment password',
                self::resetEmailHtml($user['display_name'], $resetUrl),
                "Hi {$user['display_name']},\n\nReset your password: $resetUrl\n\nThis link expires in 60 minutes."
            );
        }

        return Response::html(View::render('auth/forgot-password', [
            'pageTitle' => 'Reset Password',
            'success'   => 'If that email is registered, a reset link has been sent.',
        ], 'auth'));
    }

    // ── Reset password ────────────────────────────────────────────────────────

    public function showResetPassword(Request $request): Response
    {
        $token    = $request->param('token');
        $tokenRow = User::findResetToken($token);

        if ($tokenRow === null) {
            return Response::html(View::render('auth/reset-password', [
                'pageTitle' => 'Reset Password',
                'invalid'   => true,
            ], 'auth'));
        }

        return Response::html(View::render('auth/reset-password', [
            'pageTitle' => 'Reset Password',
            'token'     => $token,
        ], 'auth'));
    }

    public function resetPassword(Request $request): Response
    {
        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $token    = $request->param('token');
        $tokenRow = User::findResetToken($token);

        if ($tokenRow === null) {
            return Response::html(View::render('auth/reset-password', [
                'pageTitle' => 'Reset Password',
                'invalid'   => true,
            ], 'auth'));
        }

        $data   = $request->all();
        $errors = [];

        if (strlen($data['password'] ?? '') < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $errors['password_confirm'][] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return Response::html(View::render('auth/reset-password', [
                'pageTitle' => 'Reset Password',
                'token'     => $token,
                'errors'    => $errors,
            ], 'auth'));
        }

        User::updatePassword((int) $tokenRow['user_id'], $data['password']);
        User::consumeResetToken($token);
        User::deleteUserRememberTokens((int) $tokenRow['user_id']);

        Session::flash('success', 'Your password has been reset. Please log in.');

        return Response::redirect('/auth/login');
    }

    // ── Remember-me bootstrap (called from index.php before routing) ──────────

    public static function bootRememberMe(): void
    {
        if (Session::isLoggedIn()) {
            return;
        }

        $rawToken = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if ($rawToken === null) {
            return;
        }

        $user = User::findByRememberToken($rawToken);
        if ($user === null) {
            self::clearRememberCookie();
            return;
        }

        // Restore session
        self::loginUser($user);

        // Slide cookie expiry: replace token
        $newToken = User::refreshRememberToken($rawToken);
        if ($newToken !== '') {
            self::setRememberCookie($newToken);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function loginUser(array $user): void
    {
        Session::start();
        session_regenerate_id(true);
        Session::set('user_id',   (int) $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('user_name', $user['display_name']);
    }

    private static function setRememberCookie(string $rawToken): void
    {
        $secure   = str_starts_with(APP_URL, 'https://');
        $expires  = time() + self::REMEMBER_LIFETIME;

        setcookie(self::REMEMBER_COOKIE, $rawToken, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => str_starts_with(APP_URL, 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function resetEmailHtml(string $name, string $resetUrl): string
    {
        $appName = APP_NAME;
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES);
        $safeName = htmlspecialchars($name, ENT_QUOTES);

        return <<<HTML
        <!DOCTYPE html>
        <html><body style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:20px;">
        <h2 style="color:#363636;">{$appName}</h2>
        <p>Hi {$safeName},</p>
        <p>We received a request to reset your password. Click the button below to set a new password.
           This link expires in <strong>60 minutes</strong>.</p>
        <p style="margin:30px 0;">
            <a href="{$safeUrl}"
               style="background:#3273dc;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block;">
               Reset Password
            </a>
        </p>
        <p style="color:#666;font-size:14px;">
            If you did not request this, you can safely ignore this email.<br>
            Or copy and paste this URL: {$safeUrl}
        </p>
        </body></html>
        HTML;
    }
}
