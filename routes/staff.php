<?php

use App\Http\Controllers\DantelController;
use App\Http\Controllers\DocterController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\TranksaksiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
|
| Routes khusus untuk staff
|
*/

Route::middleware(['auth:sanctum', 'role:staff'])->group(function () {
    Route::post('/pasien', [PasienController::class, 'createPasien']);
    Route::get('/pasien', [PasienController::class, 'getPasien']);
    Route::get('/pasien/no-rm', [PasienController::class, 'getNoRm']);
    Route::put('/pasien/{id}', [PasienController::class, 'updatePasien']);
    Route::get('/pasien/{id}', [PasienController::class, 'getPasienById']);
    Route::get('/docter', [DocterController::class, 'getDocter']);
    Route::get('/dantel', [DantelController::class, 'getDantel']);
    Route::post('/transaksi', [TranksaksiController::class, 'createTranksaksi']);
    Route::get('/transaksi', [TranksaksiController::class, 'getTransaksi']);
    Route::get('/transaksi/{id}', [TranksaksiController::class, 'getTransaksiById']);
    Route::patch('/transaksi/{id}/status', [TranksaksiController::class, 'updateStatusTransaksi']);
    Route::put('/transaksi/{id}', [TranksaksiController::class, 'updateTranksaksi']);
    Route::get('/operational', [OperationController::class, 'getOperation']);
    Route::get('/operational/{id}', [OperationController::class, 'getOperationById']);
    Route::post('/operational', [OperationController::class, 'createOperation']);
    Route::put('/operational/{id}', [OperationController::class, 'updateOperation']);
});
