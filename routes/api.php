<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [ApiController::class, 'dashboard']);
    Route::get('/customers', [ApiController::class, 'customers']);
    Route::get('/customers/{id}', [ApiController::class, 'customerShow']);
    Route::get('/onts', [ApiController::class, 'onts']);
    Route::get('/onts/{id}', [ApiController::class, 'ontShow']);
    Route::get('/alarms', [ApiController::class, 'alarms']);
    Route::get('/tickets', [ApiController::class, 'tickets']);

    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    });
});
