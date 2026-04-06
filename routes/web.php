<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SSO Logout Redirect Handler (Fix 404)
Route::get('/auth/sso-logout', [App\Http\Controllers\Auth\SsoController::class, 'logout']);
