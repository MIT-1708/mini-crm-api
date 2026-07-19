<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ReportController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Leads routes
    Route::apiResource('leads', LeadController::class)->except(['destroy']);
    Route::post('/leads/{lead}/assign', [LeadController::class, 'assign']);

    // Activities routes
    Route::post('/leads/{lead}/activities', [ActivityController::class, 'store']);

    // Reports routes
    Route::get('/reports/rep-performance', [ReportController::class, 'repPerformance']);
});
