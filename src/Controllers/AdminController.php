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
use App\Models\User;

class AdminController
{
    public function index(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        return Response::redirect('/admin/users');
    }

    public function users(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        $users = User::all('display_name ASC');

        return Response::html(View::render('admin/users', [
            'pageTitle' => 'User Management',
            'users'     => $users,
            'roles'     => User::ROLES,
        ]));
    }

    public function toggleUser(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $id   = (int) $request->param('id');
        $user = User::findById($id);

        if ($user === null) {
            Session::flash('error', 'User not found.');
            return Response::redirect('/admin/users');
        }

        // Prevent disabling your own account
        if ($id === Session::userId()) {
            Session::flash('error', 'You cannot disable your own account.');
            return Response::redirect('/admin/users');
        }

        User::setActive($id, !$user['is_active']);

        $state = $user['is_active'] ? 'disabled' : 'enabled';
        Session::flash('success', "User \"{$user['display_name']}\" has been {$state}.");

        return Response::redirect('/admin/users');
    }

    public function updateRole(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $id   = (int) $request->param('id');
        $role = $request->input('role', '');
        $user = User::findById($id);

        if ($user === null) {
            Session::flash('error', 'User not found.');
            return Response::redirect('/admin/users');
        }

        if (!in_array($role, User::ROLES, true)) {
            Session::flash('error', 'Invalid role.');
            return Response::redirect('/admin/users');
        }

        // Prevent removing admin role from yourself
        if ($id === Session::userId() && $role !== 'admin') {
            Session::flash('error', 'You cannot change your own admin role.');
            return Response::redirect('/admin/users');
        }

        User::updateRole($id, $role);
        Session::flash('success', "Role updated for \"{$user['display_name']}\".");

        return Response::redirect('/admin/users');
    }

    public function createUser(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        return Response::html(View::render('admin/create-user', [
            'pageTitle' => 'Create User',
            'roles'     => User::ROLES,
        ]));
    }

    public function storeUser(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        Csrf::verifyOrAbort($request->input('_csrf', ''));

        $data = $request->all();
        $v    = (new Validator($data))
            ->required('display_name', 'Display name')
            ->maxLength('display_name', 100, 'Display name')
            ->required('email', 'Email')
            ->email('email', 'Email')
            ->required('password', 'Password')
            ->minLength('password', 8, 'Password')
            ->required('role', 'Role')
            ->in('role', User::ROLES, 'Role');

        $errors = $v->errors();

        if (!$v->fails() && empty($errors['email']) && User::emailExists($data['email'])) {
            $errors['email'][] = 'That email address is already registered.';
        }

        if (!empty($errors)) {
            return Response::html(View::render('admin/create-user', [
                'pageTitle' => 'Create User',
                'errors'    => $errors,
                'old'       => $data,
                'roles'     => User::ROLES,
            ]));
        }

        $newUser = User::create($data['email'], $data['display_name'], $data['password'], $data['role']);
        Session::flash('success', "User \"{$data['display_name']}\" has been created.");

        return Response::redirect('/admin/users');
    }

    public function audit(Request $request): Response
    {
        if ($guard = AuthMiddleware::require($request)) {
            return $guard;
        }
        if ($guard = RoleMiddleware::require('admin')) {
            return $guard;
        }

        return Response::html(View::render('admin/index', [
            'pageTitle' => 'Audit Log',
            'stub'      => true,
        ]));
    }
}
