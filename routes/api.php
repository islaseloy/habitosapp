<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitTrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::apiResource('categories', CategoryController::class)->only(['index', 'store']);
    Route::apiResource('habits', HabitController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('habits/{habit}/statistics', [HabitController::class, 'statistics']);
    Route::post('habit-tracking', [HabitTrackingController::class, 'store']);
});
