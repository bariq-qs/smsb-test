<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\PurchaseOrderItemController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/permission-options', [RoleController::class, 'permissionOptions']);
    Route::get('/role-options', [RoleController::class, 'roleOptions']);
    Route::apiResource('roles', RoleController::class);

    Route::get('/supplier-options', [SupplierController::class, 'options']);
    Route::apiResource('suppliers', SupplierController::class);

    Route::get('/product-options', [ProductController::class, 'options']);
    Route::apiResource('products', ProductController::class);

    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{purchase_order}/items', [PurchaseOrderItemController::class, 'store']);
    Route::put('/purchase-orders/{purchase_order}/items/{item}', [PurchaseOrderItemController::class, 'update']);
    Route::delete('/purchase-orders/{purchase_order}/items/{item}', [PurchaseOrderItemController::class, 'destroy']);

    Route::middleware('role:Administrator')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
