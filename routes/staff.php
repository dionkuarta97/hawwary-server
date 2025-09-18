<?php

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
    Route::put('/pasien/{id}', [PasienController::class, 'updatePasien']);
    Route::post('/transaksi', [TranksaksiController::class, 'createTranksaksi']);
    Route::get('/transaksi', [TranksaksiController::class, 'getTransaksi']);
    Route::patch('/transaksi/{id}/status', [TranksaksiController::class, 'updateStatusTransaksi']);
    Route::put('/transaksi/{id}', [TranksaksiController::class, 'updateTranksaksi']);
    Route::get('/operational', [OperationController::class, 'getOperation']);
    Route::post('/operational', [OperationController::class, 'createOperation']);
    Route::put('/operational/{id}', [OperationController::class, 'updateOperation']);
});
