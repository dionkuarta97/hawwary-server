<?php

use App\Http\Controllers\DocterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DantelController;
use App\Http\Controllers\AddtionalFeesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Owner Routes
|--------------------------------------------------------------------------
|
| Routes khusus untuk owner
|
*/

Route::middleware(['auth:sanctum', 'role:owner'])->group(function () {


    Route::post('change-password/{id}', [UserController::class, 'changePassword']);
    route::prefix('staff')->group(function () {
        Route::post('create', [UserController::class, 'createStaff']);
        Route::get('get', [UserController::class, 'getStaff']);
        Route::delete('delete/{id}', [UserController::class, 'softDeleteStaff']);
        Route::put('update/{id}', [UserController::class, 'updateStaff']);
    });
    route::prefix('docter')->group(function () {
        Route::post('create', [DocterController::class, 'createDocter']);
        Route::get('get', [DocterController::class, 'getDocter']);
        Route::put('update/{id}', [DocterController::class, 'updateDocter']);
        Route::delete('delete/{id}', [DocterController::class, 'softDeleteDocter']);
    });
    route::prefix('dantel')->group(function () {
        Route::post('create', [DantelController::class, 'createDantel']);
        Route::get('get', [DantelController::class, 'getDantel']);
        Route::put('update/{id}', [DantelController::class, 'updateDantel']);
        Route::delete('delete/{id}', [DantelController::class, 'softDeleteDantel']);
    });
    route::prefix('addtional-fees')->group(function () {
        Route::post('create', [AddtionalFeesController::class, 'createAddtionalFees']);
        Route::get('get', [AddtionalFeesController::class, 'getAddtionalFees']);
        Route::put('update/{id}', [AddtionalFeesController::class, 'updateAddtionalFees']);
        Route::delete('delete/{id}', [AddtionalFeesController::class, 'softDeleteAddtionalFees']);
    });
});
