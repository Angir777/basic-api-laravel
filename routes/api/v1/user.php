<?php

use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Middleware\ShouldLogoutOnNextRequestMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/user')->middleware(['auth:api', ShouldLogoutOnNextRequestMiddleware::class])->group(function () {
    Route::get('get-all', [UserController::class, 'getAll'])->middleware('can:USERS_VIEW');
    Route::get('', [UserController::class, 'query'])->middleware('can:USERS_VIEW');
    Route::get('deleted', [UserController::class, 'queryDeleted'])->middleware('can:USERS_VIEW');
    Route::get('{userId}', [UserController::class, 'getById'])->middleware('can:USERS_VIEW')->whereUuid('userId');
    Route::get('history/{user}', [UserController::class, 'getHistory'])->middleware('can:USERS_VIEW')->whereUuid('userId');

    Route::post('', [UserController::class, 'create'])->middleware('can:USERS_CREATE');
    Route::put('{userId}', [UserController::class, 'update'])->middleware('can:USERS_UPDATE')->whereUuid('userId');
    Route::patch('{userId}/change-password', [UserController::class, 'changePassword'])->middleware('can:USERS_UPDATE')->whereUuid('userId');

    Route::delete('{userId}', [UserController::class, 'delete'])->middleware('can:USERS_DELETE')->whereUuid('userId');
    Route::delete('{userId}/restore', [UserController::class, 'restore'])->middleware('can:USERS_DELETE')->whereUuid('userId');

    Route::put('/attach-roles/{userId}', [UserController::class, 'attachRoles'])->middleware('can:USERS_UPDATE')->whereUuid('userId');
});
