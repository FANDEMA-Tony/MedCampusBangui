<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EtudiantController;
use App\Http\Controllers\Api\EnseignantController;
use App\Http\Controllers\Api\CoursController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\RessourceMedicaleController;
use App\Http\Controllers\Api\DonneeSanitaireController;
use App\Http\Controllers\Api\MessageController;

// 🔹 Routes publiques - Pas besoin d'être connecté
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔹 Routes protégées - Il faut être connecté avec JWT
Route::middleware('auth.jwt')->group(function () {
    
    // Déconnexion et informations utilisateur
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 👨‍💼 ADMIN uniquement
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('etudiants', EtudiantController::class);
        Route::apiResource('enseignants', EnseignantController::class);
        
        // 🔹 Relations - Notes d'un étudiant
        Route::get('/etudiants/{etudiant}/notes', [EtudiantController::class, 'notes']);
        
        // 🔹 Relations - Cours d'un enseignant
        Route::get('/enseignants/{enseignant}/cours', [EnseignantController::class, 'cours']);
    });

    // 📚 ADMIN ou ENSEIGNANT
    Route::middleware('role:admin,enseignant')->group(function () {
        Route::apiResource('cours', CoursController::class);
        
        // 🔹 Relations - Notes d'un cours
        Route::get('/cours/{cour}/notes', [CoursController::class, 'notes']);
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

    // 📚 Bibliothèque médicale - Ressources accessibles selon les rôles
    Route::prefix('ressources')->group(function () {
        
        // Routes accessibles à tous les utilisateurs authentifiés
        Route::get('/', [RessourceMedicaleController::class, 'index']); // Liste
        Route::get('/{ressourceMedicale}', [RessourceMedicaleController::class, 'show']); // Détails
        Route::get('/{ressourceMedicale}/telecharger', [RessourceMedicaleController::class, 'telecharger']); // Télécharger
        
        // Routes réservées aux admin et enseignants
        Route::middleware('role:admin,enseignant')->group(function () {
            Route::post('/', [RessourceMedicaleController::class, 'store']); // Créer
            Route::put('/{ressourceMedicale}', [RessourceMedicaleController::class, 'update']); // Modifier
            Route::delete('/{ressourceMedicale}', [RessourceMedicaleController::class, 'destroy']); // Supprimer
        });
    });

    // 🏥 Suivi sanitaire - Données sanitaires accessibles selon les rôles
    Route::prefix('donnees-sanitaires')->group(function () {
        
        // Routes accessibles à tous les utilisateurs authentifiés
        Route::get('/', [DonneeSanitaireController::class, 'index']); // Liste avec filtres
        Route::get('/statistiques', [DonneeSanitaireController::class, 'statistiques']); // Statistiques
        Route::get('/{donneeSanitaire}', [DonneeSanitaireController::class, 'show']); // Détails
        
        // Création accessible à tous (admin, enseignant, étudiant)
        Route::post('/', [DonneeSanitaireController::class, 'store']); // Créer
        
        // Modification/Suppression selon permissions
        Route::put('/{donneeSanitaire}', [DonneeSanitaireController::class, 'update']); // Modifier
        Route::delete('/{donneeSanitaire}', [DonneeSanitaireController::class, 'destroy']); // Supprimer
    });

    // 💬 Messagerie - Accessible à tous les utilisateurs authentifiés
    Route::prefix('messages')->group(function () {
        Route::get('/boite-reception', [MessageController::class, 'boiteReception']); // Messages reçus
        Route::get('/boite-envoi', [MessageController::class, 'boiteEnvoi']); // Messages envoyés
        Route::get('/non-lus', [MessageController::class, 'nonLus']); // Compteur non lus
        Route::get('/conversation/{utilisateurId}', [MessageController::class, 'conversation']); // Conversation
        Route::get('/{message}', [MessageController::class, 'show']); // Détails d'un message
        Route::post('/', [MessageController::class, 'store']); // Envoyer un message
        Route::delete('/{message}', [MessageController::class, 'destroy']); // Supprimer
    });
});