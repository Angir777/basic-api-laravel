<?php

use App\Http\Controllers\Api\V1\User\PermissionController;
use App\Http\Middleware\ShouldLogoutOnNextRequestMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/permission')->middleware(['auth:api', ShouldLogoutOnNextRequestMiddleware::class])->group(function () {
    Route::get('get-all', [PermissionController::class, 'getAll'])->middleware('can:PERMISSIONS_VIEW');
    Route::get('', [PermissionController::class, 'query'])->middleware('can:PERMISSIONS_VIEW');
    Route::get('{permissionId}', [PermissionController::class, 'getById'])->middleware('can:PERMISSIONS_VIEW')->whereNumber('permissionId');

    Route::post('', [PermissionController::class, 'create'])->middleware('can:PERMISSIONS_CREATE');
    Route::put('{permissionId}', [PermissionController::class, 'update'])->middleware('can:PERMISSIONS_UPDATE')->whereNumber('permissionId');
    Route::delete('{permissionId}', [PermissionController::class, 'delete'])->middleware('can:PERMISSIONS_DELETE')->whereNumber('permissionId');
});
