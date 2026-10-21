<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\HabitTrackingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Web routes for habits
    Route::get('/habits/create', [DashboardController::class, 'createHabit'])->name('habits.create');
    Route::get('/statistics', [DashboardController::class, 'statistics'])->name('habits.statistics.view');
    Route::get('/premium', [DashboardController::class, 'premium'])->name('premium.view');
    Route::post('/premium/toggle', [DashboardController::class, 'togglePremium'])->name('premium.toggle');

    // AJAX API routes under session auth
    Route::prefix('api')->group(function () {
        Route::apiResource('categories', CategoryController::class)->only(['index', 'store']);
        Route::apiResource('habits', HabitController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::get('habits/{habit}/statistics', [HabitController::class, 'statistics']);
        Route::post('habit-tracking', [HabitTrackingController::class, 'store']);
    });
});

require __DIR__.'/auth.php';
