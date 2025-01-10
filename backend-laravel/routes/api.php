<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth'

], function () {
    Route::get('google/callback', [AuthController::class, 'googleCallback']);
    Route::get('refresh', [AuthController::class, 'refresh']);
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
});

// Route::options('/{any}', function () {
//     return response()->json([], 200)
//         ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
//         ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
//         ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
//         ->header('Access-Control-Allow-Credentials', 'true');
// })->where('any', '.*');
