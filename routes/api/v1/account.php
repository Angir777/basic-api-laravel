<?php

use App\Http\Controllers\Api\V1\Account\AccountController;
use App\Http\Middleware\ShouldLogoutOnNextRequestMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/account')->middleware(['auth:api', ShouldLogoutOnNextRequestMiddleware::class])->group(function () {
    Route::patch('change-password', [AccountController::class, 'changePassword']);
    Route::post('resend-confirmation-email', [AccountController::class, 'resendConfirmationEmail']);

    Route::put('update-details-user', [AccountController::class, 'updateDetailsUser']);

    Route::get('get-user-settings', [AccountController::class, 'getUserSettings']);
    Route::patch('update-user-settings', [AccountController::class, 'updateUserSettings']);

    Route::delete('delete-account', [AccountController::class, 'deleteAccount']);
});
