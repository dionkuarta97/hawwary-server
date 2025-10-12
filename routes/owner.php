<?php

use App\Http\Controllers\DocterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DantelController;
use App\Http\Controllers\AddtionalFeesController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\TranksaksiController;
use App\Http\Controllers\FeeDistributionController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\StatisticController;
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



    route::prefix('staff')->group(function () {
        Route::post('create', [UserController::class, 'createStaff']);
        Route::get('get', [UserController::class, 'getStaff']);
        Route::delete('delete/{id}', [UserController::class, 'softDeleteStaff']);
        Route::put('update/{id}', [UserController::class, 'updateStaff']);
        Route::post('change-password/{id}', [UserController::class, 'changePassword']);
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
    route::prefix('pasien')->group(function () {
        Route::get('get', [PasienController::class, 'getPasienForOwner']);
        Route::put('update/{id}', [PasienController::class, 'updatePasien']);
    });
    route::prefix('transaksi')->group(function () {
        Route::get('get', [TranksaksiController::class, 'getTransaksi']);
        Route::get('struk/{id}', [TranksaksiController::class, 'printStruk']);
        Route::put('update-status/{id}', [TranksaksiController::class, 'updateTransaksiStatusForOwner']);
        Route::delete('delete/{id}', [TranksaksiController::class, 'deleteTransaksiForOwner']);
    });
    route::prefix('fee-distribution')->group(function () {
        Route::get('get', [FeeDistributionController::class, 'getFeeDistributions']);
        Route::get('get/{id}', [FeeDistributionController::class, 'getFeeDistributionById']);
        Route::get('transaksi/{transaksi_id}', [FeeDistributionController::class, 'getFeeDistributionByTransaksi']);
        Route::get('recipient', [FeeDistributionController::class, 'getFeeDistributionByRecipient']);
    });
    route::prefix('operation')->group(function () {
        Route::get('get', [OperationController::class, 'getOperation']);
        Route::delete('delete/{id}', [OperationController::class, 'softDeleteOperation']);
        Route::put('update-status/{id}', [OperationController::class, 'updateStatusOperation']);
    });
    route::prefix('statistic')->group(function () {
        Route::get('dashboard', [StatisticController::class, 'getStatistic']);
    });
});
