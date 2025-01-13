<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth',
    'middleware' => 'throttle:api',
], function () {
    Route::get('google/callback', [AuthController::class, 'googleCallback']);
    Route::get('refresh', [AuthController::class, 'refresh']);
    Route::get('logout', [AuthController::class, 'logout']);
});

Route::group([
    'prefix' => '/',
    'middleware' => ['throttle:api', 'jwt.middleware'],
], function () {
    Route::get('me', [AuthController::class, 'me']);
});
