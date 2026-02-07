<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EtudiantController;
use App\Http\Controllers\Api\EnseignantController;
use App\Http\Controllers\Api\CoursController;
use App\Http\Controllers\Api\NoteController;

// 🔹 Routes publiques - Pas besoin d'être connecté
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔹 Routes protégées - Il faut être connecté avec JWT
Route::middleware('auth.jwt')->group(function () {
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']); // 🔹 AJOUTE CETTE LIGNE

    // 👨‍💼 ADMIN uniquement
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('etudiants', EtudiantController::class);
        Route::apiResource('enseignants', EnseignantController::class);
    });

    // 📚 ADMIN ou ENSEIGNANT
    Route::middleware('role:admin,enseignant')->group(function () {
        Route::apiResource('cours', CoursController::class);
    });

    // 📝 ENSEIGNANT uniquement
    Route::middleware('role:enseignant')->group(function () {
        Route::apiResource('notes', NoteController::class);
    });

    // 👨‍🎓 ETUDIANT uniquement
    Route::middleware('role:etudiant')->group(function () {
        Route::get('/mes-informations', [EtudiantController::class, 'show']);
        Route::get('/mes-cours', [CoursController::class, 'index']);
        Route::get('/mes-notes', [NoteController::class, 'index']);
    });
});