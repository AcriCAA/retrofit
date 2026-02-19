<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('searches', SearchRequestController::class)->parameters(['searches' => 'searchRequest']);

    Route::get('/settings/notifications', [NotificationPreferenceController::class, 'edit'])->name('notifications.edit');
    Route::put('/settings/notifications', [NotificationPreferenceController::class, 'update'])->name('notifications.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
