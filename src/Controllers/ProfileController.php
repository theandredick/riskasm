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
use App\Models\User;

class ProfileController
{
    public function show(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        $user = User::findById(Session::userId());

        return Response::html(View::render('profile/edit', [
            'pageTitle' => 'My Profile',
            'user'      => $user,
        ]));
    }

    public function update(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $userId = Session::userId();
        $user   = User::findById($userId);
        $data   = $request->all();

        $v = (new Validator($data))
            ->required('display_name', 'Display name')
            ->maxLength('display_name', 100, 'Display name')
            ->required('email', 'Email')
            ->email('email', 'Email');

        $errors = $v->errors();

        // Email uniqueness check — ignore if unchanged
        if (empty($errors['email'])
            && strtolower(trim($data['email'])) !== $user['email']
            && User::emailExists($data['email'])
        ) {
            $errors['email'][] = 'That email address is already in use by another account.';
        }

        // Password change — only if the user typed a new password
        $changePassword = !empty($data['new_password']);
        if ($changePassword) {
            if (empty($data['current_password'])) {
                $errors['current_password'][] = 'Enter your current password to set a new one.';
            } elseif (!User::verifyPassword($user, $data['current_password'])) {
                $errors['current_password'][] = 'Current password is incorrect.';
            }

            if (strlen($data['new_password']) < 8) {
                $errors['new_password'][] = 'New password must be at least 8 characters.';
            }

            if (($data['new_password'] ?? '') !== ($data['new_password_confirm'] ?? '')) {
                $errors['new_password_confirm'][] = 'Passwords do not match.';
            }
        }

        if (!empty($errors)) {
            return Response::html(View::render('profile/edit', [
                'pageTitle' => 'My Profile',
                'user'      => $user,
                'errors'    => $errors,
                'old'       => $data,
            ]));
        }

        User::updateProfile($userId, $data['display_name'], $data['email']);

        if ($changePassword) {
            User::updatePassword($userId, $data['new_password']);
            User::deleteUserRememberTokens($userId);
        }

        // Keep session display name in sync
        Session::set('user_name', trim($data['display_name']));

        Session::flash('success', 'Profile updated successfully.');

        return Response::redirect('/profile');
    }
}
