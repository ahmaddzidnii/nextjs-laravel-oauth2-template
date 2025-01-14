<?php

use App\Http\Controllers\Auth\Oauth\GoogleController;
use App\Http\Controllers\Auth\TokenController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'throttle:api'], function () {
    /*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
    Route::group([
        'prefix' => 'public',
        'as' => 'public.',
    ], function () {
        // Home/Landing Page Data
        // Route::get('home', [HomeController::class, 'index'])->name('home');

        // Product Catalog
        Route::group([
            'prefix' => 'products',
            'as' => 'products.'
        ], function () {
            // Route::get('/', [ProductController::class, 'index'])->name('index');
            // Route::get('/{product}', [ProductController::class, 'show'])->name('show');
            // Route::get('/featured', [ProductController::class, 'featured'])->name('featured');
            // Route::get('/categories/{category}', [ProductController::class, 'byCategory'])->name('by-category');
        });

        // Categories
        Route::group([
            'prefix' => 'categories',
            'as' => 'categories.'
        ], function () {
            // Route::get('/', [CategoryController::class, 'index'])->name('index');
            // Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'prefix' => 'auth',
        'as' => 'auth.',
    ], function () {
        // OAuth Routes
        Route::group([
            'prefix' => 'oauth',
            'as' => 'oauth.'
        ], function () {
            Route::get('google/callback', [GoogleController::class, 'callback'])->name('google.callback');
            // Mudah menambahkan provider OAuth lain
            // Route::get('facebook/callback', [FacebookAuthController::class, 'callback'])->name('facebook.callback');
        });

        // Token Management
        Route::get('refresh', [TokenController::class, 'refresh'])->name('refresh');
        Route::get('logout', [TokenController::class, 'logout'])->name('logout');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */
    Route::group([
        'middleware' => 'jwt.middleware'
    ], function () {
        // User Profile Routes
        Route::group([
            'prefix' => 'profile',
            'as' => 'profile.'
        ], function () {
            Route::get('me', [ProfileController::class, 'me'])->name('me');
            // Route::get('/', [ProfileController::class, 'show'])->name('show');
            // Route::put('/', [ProfileController::class, 'update'])->name('update');
        });

        // Example Resource Routes Structure
        /*
        // Posts Routes
        Route::group([
            'prefix' => 'posts',
            'as' => 'posts.'
        ], function () {
            Route::apiResource('/', PostController::class);
            
            // Nested Resources
            Route::apiResource('comments', CommentController::class);
        });
        */
    });
});
