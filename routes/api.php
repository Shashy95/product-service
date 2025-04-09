<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware(['CheckAudience'])->get('/products', [ProductController::class, 'index']);
Route::middleware(['CheckAudience'])->get('/products/{product}', [ProductController::class, 'show']);
Route::middleware(['CheckAudience'])->post('/products', [ProductController::class, 'store']);
Route::middleware(['CheckAudience'])->put('/products/{product}', [ProductController::class, 'update']);
Route::middleware(['CheckAudience'])->delete('/products/{product}', [ProductController::class, 'destroy']);
