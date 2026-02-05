<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EtudiantController;
use App\Http\Controllers\Api\EnseignantController;
use App\Http\Controllers\Api\CoursController;
use App\Http\Controllers\Api\NoteController;

// 🔹 Authentification (publiques)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔹 Routes protégées par JWT
Route::middleware(['auth.jwt'])->group(function () {
    // 🎓 Étudiants
    Route::apiResource('etudiants', EtudiantController::class);

    // 👨‍🏫 Enseignants
    Route::apiResource('enseignants', EnseignantController::class);

    // 📚 Cours
    Route::apiResource('cours', CoursController::class);

    // 📝 Notes
    Route::apiResource('notes', NoteController::class);
});