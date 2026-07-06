<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/permission-options', [RoleController::class, 'permissionOptions']);
    Route::get('/role-options', [RoleController::class, 'roleOptions']);
    Route::apiResource('roles', RoleController::class);

    Route::middleware('role:Administrator')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
