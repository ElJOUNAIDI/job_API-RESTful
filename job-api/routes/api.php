<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public job listings
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{id}', [JobController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Jobs (Employer)
    Route::middleware('employer')->group(function () {
        Route::post('/jobs', [JobController::class, 'store']);
        Route::put('/jobs/{id}', [JobController::class, 'update']);
        Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
        Route::get('/employer/jobs', [JobController::class, 'myJobs']);
        Route::get('/employer/applications', [ApplicationController::class, 'employerApplications']);
        Route::put('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
    });

    // Applications (Candidate)
    Route::middleware('candidate')->group(function () {
        Route::post('/jobs/{id}/apply', [ApplicationController::class, 'store']);
        Route::get('/candidate/applications', [ApplicationController::class, 'index']);
        Route::get('/candidate/applications/{id}', [ApplicationController::class, 'show']);
    });

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/jobs/{id}/favorite', [FavoriteController::class, 'toggle']);
    Route::get('/jobs/{id}/favorite/check', [FavoriteController::class, 'check']);

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);
        Route::get('/jobs', [AdminController::class, 'allJobs']);
        Route::get('/applications', [AdminController::class, 'allApplications']);
        Route::get('/statistics', [AdminController::class, 'statistics']);
    });
});