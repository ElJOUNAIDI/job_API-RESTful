<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Ici sont définies toutes les routes de l’API, publiques et protégées.
|--------------------------------------------------------------------------
*/

// ---------------------
// 🔓 Routes publiques
// ---------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Liste publique des offres
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{id}', [JobController::class, 'show']);

// ---------------------
// 🔐 Routes protégées
// ---------------------
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ---------------------
    // 🏢 Employeur
    // ---------------------
    Route::middleware('employer')->group(function () {
        Route::post('/jobs', [JobController::class, 'store']);
        Route::put('/jobs/{id}', [JobController::class, 'update']);
        Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
        Route::get('/employer/jobs', [JobController::class, 'myJobs']);
        Route::get('/employer/applications', [ApplicationController::class, 'employerApplications']);
        Route::put('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
    });

    // ---------------------
    // 👤 Candidat
    // ---------------------
    Route::middleware('candidate')->group(function () {
        Route::post('/jobs/{id}/apply', [ApplicationController::class, 'store']);
        Route::get('/candidate/applications', [ApplicationController::class, 'index']);
        Route::get('/candidate/applications/{id}', [ApplicationController::class, 'show']);
    });

    // ---------------------
    // ⭐ Favoris
    // ---------------------
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/jobs/{id}/favorite', [FavoriteController::class, 'toggle']);
    Route::get('/jobs/{id}/favorite/check', [FavoriteController::class, 'check']);

    // ---------------------
    // ⚙️ Admin
    // ---------------------
    Route::middleware('admin')->prefix('admin')->group(function () {
        // 👥 Gestion des utilisateurs
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // 💼 Gestion des offres
        Route::get('/jobs', [AdminController::class, 'allJobs']);
        Route::delete('/jobs/{id}', [AdminController::class, 'deleteJob']);

        // 📄 Gestion des candidatures
        Route::get('/applications', [AdminController::class, 'allApplications']);

        // 📊 Statistiques
        Route::get('/statistics', [AdminController::class, 'statistics']);
    });
});
