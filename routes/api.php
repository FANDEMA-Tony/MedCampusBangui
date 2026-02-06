<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EtudiantController;
use App\Http\Controllers\Api\EnseignantController;
use App\Http\Controllers\Api\CoursController;
use App\Http\Controllers\Api\NoteController;

/// 🔹 Authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.jwt');

// 🔹 Routes protégées par JWT + rôles
Route::middleware(['auth.jwt'])->group(function () {

    // 🎓 Étudiants → admin uniquement
    Route::apiResource('etudiants', EtudiantController::class)
        ->middleware('role:admin');

    // 👨‍🏫 Enseignants → admin uniquement
    Route::apiResource('enseignants', EnseignantController::class)
        ->middleware('role:admin');

    // 📚 Cours → admin + enseignant
    Route::apiResource('cours', CoursController::class)
        ->middleware('role:admin,enseignant');

    // 📝 Notes → enseignant uniquement
    Route::apiResource('notes', NoteController::class)
        ->middleware('role:enseignant');
});
Route::middleware(['auth.jwt', 'role:etudiant'])->group(function () {
    Route::get('/etudiants', [EtudiantController::class, 'index']);
    Route::get('/etudiants/{etudiant}', [EtudiantController::class, 'show']);
});
