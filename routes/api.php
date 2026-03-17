<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\AccountController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Only for authenticated users)
Route::middleware('auth:sanctum')->group(function () {

    /**
     * Get the authenticated user's profile
     */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /**
     * Resourceful routes for Groups, Categories, and Transactions
     * Each handles: index, store, show, update, destroy
     */
    Route::apiResource('accounts', AccountController::class);
    Route::apiResource('groups', GroupController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('transactions', TransactionController::class);

    /**
     * Custom route to quickly toggle the favorite status of a transaction
     */
    Route::patch('/transactions/{transaction}/toggle-favorite', [TransactionController::class, 'toggleFavorite']);

    /**
     * User logout
     */
    Route::post('/logout', [AuthController::class, 'logout']);
});
