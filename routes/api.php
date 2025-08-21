<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Orders API Routes
Route::apiResource('orders', OrderController::class);
