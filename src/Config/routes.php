<?php

declare(strict_types=1);

/**
 * Route definitions.
 *
 * $router->add(METHOD, '/path', 'ControllerClass@method');
 * $router->add(METHOD, '/path/{id}', 'ControllerClass@method');
 *
 * Populated in Phase 1 as controllers are built.
 */

// ── Auth ──────────────────────────────────────────────────────────────────────
$router->add('GET',  '/auth/login',                    'AuthController@showLogin');
$router->add('POST', '/auth/login',                    'AuthController@login');
$router->add('GET',  '/auth/logout',                   'AuthController@logout');
$router->add('GET',  '/auth/register',                 'AuthController@showRegister');
$router->add('POST', '/auth/register',                 'AuthController@register');
$router->add('GET',  '/auth/forgot-password',          'AuthController@showForgotPassword');
$router->add('POST', '/auth/forgot-password',          'AuthController@forgotPassword');
$router->add('GET',  '/auth/reset-password/{token}',   'AuthController@showResetPassword');
$router->add('POST', '/auth/reset-password/{token}',   'AuthController@resetPassword');

// ── Dashboard ─────────────────────────────────────────────────────────────────
$router->add('GET', '/', 'DashboardController@index');

// ── Assessments ───────────────────────────────────────────────────────────────
$router->add('GET',  '/assessments',                         'AssessmentController@index');
$router->add('GET',  '/assessments/new',                     'AssessmentController@create');
$router->add('POST', '/assessments/new',                     'AssessmentController@store');
$router->add('GET',  '/assessments/{id}',                    'AssessmentController@edit');
$router->add('POST', '/assessments/{id}',                    'AssessmentController@update');
$router->add('POST', '/assessments/{id}/delete',             'AssessmentController@destroy');
$router->add('POST', '/assessments/{id}/copy',               'AssessmentController@copy');
$router->add('POST', '/assessments/{id}/status',             'AssessmentController@updateStatus');
$router->add('GET',  '/assessments/{id}/export/pdf',         'ExportController@pdf');
$router->add('GET',  '/assessments/{id}/export/xlsx',        'ExportController@xlsx');
$router->add('GET',  '/assessments/{id}/export/csv',         'ExportController@csv');
$router->add('GET',  '/assessments/{id}/share',              'ShareController@index');
$router->add('POST', '/assessments/{id}/share',              'ShareController@store');
$router->add('POST', '/assessments/{id}/share/{sid}/revoke', 'ShareController@revoke');

// ── Public shared view ────────────────────────────────────────────────────────
$router->add('GET', '/shared/{token}', 'ShareController@publicView');

// ── JSON API — Assessment rows ─────────────────────────────────────────────────
$router->add('GET',    '/api/assessments/{id}/rows',           'AssessmentRowApiController@index');
$router->add('POST',   '/api/assessments/{id}/rows',           'AssessmentRowApiController@store');
$router->add('PUT',    '/api/assessments/{id}/rows/{rowId}',   'AssessmentRowApiController@update');
$router->add('DELETE', '/api/assessments/{id}/rows/{rowId}',   'AssessmentRowApiController@destroy');
$router->add('POST',   '/api/assessments/{id}/rows/reorder',   'AssessmentRowApiController@reorder');
$router->add('POST',   '/api/assessments/{id}/sync',           'AssessmentRowApiController@sync');

// ── JSON API — Row controls ────────────────────────────────────────────────────
$router->add('GET',    '/api/rows/{rowId}/controls',                        'RowControlApiController@index');
$router->add('POST',   '/api/rows/{rowId}/controls',                        'RowControlApiController@store');
$router->add('PUT',    '/api/rows/{rowId}/controls/{controlId}',            'RowControlApiController@update');
$router->add('DELETE', '/api/rows/{rowId}/controls/{controlId}',            'RowControlApiController@destroy');
$router->add('POST',   '/api/rows/{rowId}/controls/reorder',                'RowControlApiController@reorder');

// ── JSON API — Matrices ────────────────────────────────────────────────────────
$router->add('GET', '/api/matrices/{id}',      'MatrixApiController@show');
$router->add('GET', '/api/matrices/{id}/cell', 'MatrixApiController@cell');

// ── JSON API — Library ─────────────────────────────────────────────────────────
$router->add('GET',  '/api/library/hazards',  'LibraryApiController@hazards');
$router->add('POST', '/api/library/hazards',  'LibraryApiController@storeHazard');
$router->add('GET',  '/api/library/controls', 'LibraryApiController@controls');
$router->add('POST', '/api/library/controls', 'LibraryApiController@storeControl');

// ── Matrices ──────────────────────────────────────────────────────────────────
$router->add('GET',  '/matrices',          'MatrixController@index');
$router->add('GET',  '/matrices/new',      'MatrixController@create');
$router->add('POST', '/matrices/new',      'MatrixController@store');
$router->add('GET',  '/matrices/{id}',     'MatrixController@show');
$router->add('GET',  '/matrices/{id}/edit','MatrixController@edit');
$router->add('POST', '/matrices/{id}/edit','MatrixController@update');
$router->add('POST', '/matrices/{id}/delete','MatrixController@destroy');
$router->add('POST', '/matrices/{id}/copy','MatrixController@copy');

// ── Library ───────────────────────────────────────────────────────────────────
$router->add('GET',  '/library',                      'LibraryController@index');
$router->add('GET',  '/library/hazards',              'LibraryController@hazards');
$router->add('GET',  '/library/controls',             'LibraryController@controls');
$router->add('POST', '/library/hazards/{id}/edit',    'LibraryController@editHazard');
$router->add('POST', '/library/hazards/{id}/delete',  'LibraryController@deleteHazard');
$router->add('POST', '/library/controls/{id}/edit',   'LibraryController@editControl');
$router->add('POST', '/library/controls/{id}/delete', 'LibraryController@deleteControl');

// ── Admin ─────────────────────────────────────────────────────────────────────
$router->add('GET',  '/admin',                    'AdminController@index');
$router->add('GET',  '/admin/users',              'AdminController@users');
$router->add('POST', '/admin/users/{id}/toggle',  'AdminController@toggleUser');
$router->add('POST', '/admin/users/{id}/role',    'AdminController@updateRole');
$router->add('GET',  '/admin/audit',              'AdminController@audit');
