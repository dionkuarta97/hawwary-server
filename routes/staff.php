<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
|
| Routes khusus untuk staff
|
*/

Route::middleware(['auth:sanctum', 'role:staff'])->group(function () {});
