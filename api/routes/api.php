<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SymbolsController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/symbols', [SymbolsController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);

    Route::get('/orders', [OrdersController::class, 'index']);
    Route::post('/orders', [OrdersController::class, 'store']);
    Route::post('/orders/{id}/cancel', [OrdersController::class, 'cancel']);
});
