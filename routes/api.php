<?php

use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\SearchResultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/chat/start', [ChatbotController::class, 'start']);
    Route::post('/chat/{uuid}', [ChatbotController::class, 'send']);
    Route::get('/chat/{uuid}/history', [ChatbotController::class, 'history']);

    Route::patch('/results/{result}/status', [SearchResultController::class, 'updateStatus']);
});
