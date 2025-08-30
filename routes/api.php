<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Category routes
Route::get('categories', [CategoryController::class, 'index']);

// Ticket routes
Route::prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index']); // List tickets with filters
    Route::post('/', [TicketController::class, 'store']); // Create ticket
    Route::get('/{id}', [TicketController::class, 'show']); // Get ticket by ID
    Route::patch('/{id}', [TicketController::class, 'update'])->middleware('validate.ticket.update'); // Update ticket by ID
    Route::post('/{id}/classify', [TicketController::class, 'classify']); // Classify ticket
    Route::get('/classify/status', [TicketController::class, 'classifyStatus']); // Get classification rate limit status
});
