<?php

use App\Http\Controllers\Api\Auth\CurrentUserController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('register', [RegisterController::class, 'register']);
    Route::get('confirm-account/{confirmationCode}', [RegisterController::class, 'confirmAccount']);

    Route::post('send-reset-password-email', [ResetPasswordController::class, 'sendResetPasswordEmail']);
    Route::post('finish-password-reset', [ResetPasswordController::class, 'finishPasswordReset']);

    Route::get('admins-emails', [LoginController::class, 'getAdminsEmails']);
    Route::post('login', [LoginController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::get('current-user', [CurrentUserController::class, 'getCurrentUser']);
    });
});
