<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;


Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'environment' => app()->environment(),
        'time' => now()->toISOString(),
    ]);
});

// Public routes

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/forgot-password', [
    PasswordResetController::class,
    'forgotPassword'
])->middleware('throttle:3,1');

Route::post('/reset-password', [
    PasswordResetController::class,
    'resetPassword'
])->middleware('throttle:5,1');


// Email verification link
Route::get('/email/verify/{id}/{hash}', [
    EmailVerificationController::class,
    'verify'
])
    ->middleware('signed')
    ->name('verification.verify');


// Authenticated routes
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Auth routes available before verification
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Email verification
    Route::get('/email/verification-status', [
        EmailVerificationController::class,
        'notice'
    ]);

  Route::post('/email/verification-notification', [
    EmailVerificationController::class,
    'send'
])->middleware('throttle:3,1');

    // Verified users only
    Route::middleware('verified')->group(function () {

        // Dashboard
        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ]);

        // Profile settings
        Route::get('/settings/profile', [
            SettingsController::class,
            'profile'
        ]);

        Route::put('/settings/profile', [
            SettingsController::class,
            'updateProfile'
        ]);

        Route::put('/settings/password', [
            SettingsController::class,
            'updatePassword'
        ]);

        // Company settings
        Route::get('/settings/company', [
            SettingsController::class,
            'company'
        ]);

        Route::put('/settings/company', [
            SettingsController::class,
            'updateCompany'
        ]);

        // CRM resources
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('leads', LeadController::class);
        Route::apiResource('appointments', AppointmentController::class);
        Route::apiResource('tasks', TaskController::class);

        // Team management
        Route::middleware('role:owner,admin')->group(function () {

            Route::get('/team', [
                TeamController::class,
                'index'
            ]);

            Route::post('/team', [
                TeamController::class,
                'store'
            ]);

            Route::put('/team/{user}', [
                TeamController::class,
                'update'
            ]);

            Route::delete('/team/{user}', [
                TeamController::class,
                'destroy'
            ]);
        });
    });
});