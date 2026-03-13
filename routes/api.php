<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Only for authenticated users)
Route::middleware('auth:sanctum')->group(function () {

    // Get current user profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Transaction routes: fetch all for user and create new ones
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);

    // Category routes: fetch list of categories and create new ones
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Logout route to revoke the token
    Route::post('/logout', [AuthController::class, 'logout']);
});
