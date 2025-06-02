<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;



Route::controller(AuthController::class)->prefix('/auth')->group(function() {
    Route::get('user', 'user')->middleware('auth:sanctum');
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware(['auth:sanctum']);
});