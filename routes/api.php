<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReturnLoanController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FineController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('v1/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [AuthController::class, 'profile']);

    // Categories (Géneros/Categorías)
    Route::apiResource('categories', CategoryController::class);

    // Books
    Route::apiResource('books', BookController::class);
    Route::get('books/{book}/copies', [BookController::class, 'copies']);

    // Loans
    Route::apiResource('loans', LoanController::class)->only(['index', 'store', 'show']);
    Route::post('loans/{loan}/return', ReturnLoanController::class);

    // Fines (Multas)
    Route::apiResource('fines', FineController::class)->only(['index', 'show']);
    Route::post('fines/{fine}/pay', [FineController::class, 'pay']);
});
