<?php

use App\Http\Controllers\Dashboard\AnalysisController;
use App\Http\Controllers\Dashboard\AuthController;
use App\Http\Controllers\Dashboard\CompanyController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DeepAnalysisController;
use App\Http\Controllers\Dashboard\IntegrationsController;
use App\Http\Controllers\Dashboard\OnboardingController;
use App\Http\Controllers\Dashboard\RegisterController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/welcome');

Route::get('/welcome', [OnboardingController::class, 'welcome'])->name('onboarding.welcome');
Route::get('/onboarding/start', [OnboardingController::class, 'startConnect'])->name('onboarding.company.start');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/onboarding/company', [OnboardingController::class, 'create'])->name('onboarding.company.create');
    Route::post('/onboarding/company', [OnboardingController::class, 'store'])->name('onboarding.company.store');
    Route::get('/onboarding/success', [OnboardingController::class, 'success'])->name('onboarding.success');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
    Route::post('/companies/{company}/switch', [CompanyController::class, 'switch'])->name('companies.switch');
});

Route::middleware(['auth', 'company'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/employees/{employeeId}', [DashboardController::class, 'employee'])
        ->name('dashboard.employee');

    Route::get('/dashboard/analysis', [AnalysisController::class, 'index'])->name('dashboard.analysis');
    Route::get('/dashboard/analysis/responses', [AnalysisController::class, 'exportResponses'])
        ->name('dashboard.analysis.responses');
    Route::post('/dashboard/analysis/recommend', [AnalysisController::class, 'recommend'])
        ->middleware('throttle:10,1')
        ->name('dashboard.analysis.recommend');

    Route::get('/dashboard/integrations', [IntegrationsController::class, 'index'])
        ->name('dashboard.integrations');
    Route::post('/dashboard/integrations/{provider}', [IntegrationsController::class, 'store'])
        ->name('dashboard.integrations.store');
    Route::delete('/dashboard/integrations/{provider}', [IntegrationsController::class, 'destroy'])
        ->name('dashboard.integrations.destroy');
    Route::post('/dashboard/integrations/{provider}/test', [IntegrationsController::class, 'test'])
        ->name('dashboard.integrations.test');

    Route::get('/dashboard/deep-analysis', [DeepAnalysisController::class, 'index'])
        ->name('dashboard.deep-analysis');
    Route::post('/dashboard/deep-analysis/sync', [DeepAnalysisController::class, 'sync'])
        ->name('dashboard.deep-analysis.sync');
    Route::get('/dashboard/deep-analysis/preview', [DeepAnalysisController::class, 'preview'])
        ->name('dashboard.deep-analysis.preview');
    Route::post('/dashboard/deep-analysis/recommend', [DeepAnalysisController::class, 'recommend'])
        ->middleware('throttle:10,1')
        ->name('dashboard.deep-analysis.recommend');
});
