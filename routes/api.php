<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('login', [UserController::class, 'login']);
Route::post('login-owner', [UserController::class, 'loginOwner']);

// Protected routes (perlu token)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::delete('logout', [UserController::class, 'logout']);
});

// Include owner and staff routes
Route::prefix('owner')->group(base_path('routes/owner.php'));
Route::prefix('staff')->group(base_path('routes/staff.php'));
