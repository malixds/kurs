<?php

use App\Http\Controllers\Api\V1\Dashboard\AnalyticsController;
use App\Http\Controllers\Api\V1\Dashboard\AuthController;
use App\Http\Controllers\Api\V1\Extension\CheckInController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/extension')
    ->middleware(['throttle:extension'])
    ->group(function (): void {
        Route::get('survey/questions', [CheckInController::class, 'questions']);
        Route::post('check-in', [CheckInController::class, 'store']);
    });

Route::prefix('v1/dashboard')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'company'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('analytics/employees/{employeeId}/history', [AnalyticsController::class, 'employeeHistory']);
    });
});
