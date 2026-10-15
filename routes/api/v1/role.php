<?php

use App\Http\Controllers\Api\V1\User\RoleController;
use App\Http\Middleware\ShouldLogoutOnNextRequestMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/role')->middleware(['auth:api', ShouldLogoutOnNextRequestMiddleware::class])->group(function () {
    Route::get('get-all', [RoleController::class, 'getAll'])->middleware('can:ROLES_VIEW');
    Route::get('', [RoleController::class, 'query'])->middleware('can:ROLES_VIEW');
    Route::get('{roleId}', [RoleController::class, 'getById'])->middleware('can:ROLES_VIEW')->whereNumber('roleId');
    Route::get('history/{role}', [RoleController::class, 'getHistory'])->middleware('can:ROLES_VIEW')->where('role', '[0-9]+');

    Route::post('', [RoleController::class, 'create'])->middleware('can:ROLES_CREATE');
    Route::put('{roleId}', [RoleController::class, 'update'])->middleware('can:ROLES_UPDATE')->whereNumber('roleId');
    Route::delete('{roleId}', [RoleController::class, 'delete'])->middleware('can:ROLES_DELETE')->whereNumber('roleId');

    Route::get('/get-permissions', [RoleController::class, 'getPermissions'])->middleware(['can:ROLES_VIEW']);
});
