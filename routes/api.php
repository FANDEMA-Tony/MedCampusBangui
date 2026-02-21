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

/*
|--------------------------------------------------------------------------
| API Routes - MedCampus Bangui
|--------------------------------------------------------------------------
| Système de gestion médicale avec authentification JWT
| Rôles : admin, enseignant, etudiant
*/

// ========================================================================
// 🔓 ROUTES PUBLIQUES - Pas besoin d'être connecté
// ========================================================================

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ========================================================================
// 🔐 ROUTES PROTÉGÉES - JWT requis
// ========================================================================

Route::middleware('auth.jwt')->group(function () {
    
    // ====================================================================
    // 👤 AUTHENTIFICATION
    // ====================================================================
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ====================================================================
    // 👥 ENSEIGNANTS - Liste accessible à tous (pour messagerie)
    // ====================================================================
    
    Route::get('/enseignants', [EnseignantController::class, 'index']);
    Route::get('/enseignants-grouped', [EnseignantController::class, 'indexGrouped']); // 🆕 AJOUTER CETTE LIGNE
    Route::get('/cours-grouped', [CoursController::class, 'indexGrouped']); // 🆕 NOUVELLE ROUTE
    Route::get('/enseignants/{enseignant}/cours', [EnseignantController::class, 'cours']);
    
    // ====================================================================
    // 👨‍🎓 ÉTUDIANTS - Liste accessible à admin + enseignant (pour messagerie)
    // ====================================================================
    
    Route::get('/etudiants', [EtudiantController::class, 'index']);
    Route::get('/etudiants-grouped', [EtudiantController::class, 'indexGrouped']); // 🆕 NOUVELLE ROUTE
    Route::get('/etudiants/{etudiant}/notes', [EtudiantController::class, 'notes']);

    // ====================================================================
    // 👨‍💼 ADMIN UNIQUEMENT - CRUD Complet
    // ====================================================================
    
    Route::middleware('role:admin')->group(function () {
        
        // Enseignants - Création, modification, suppression
        Route::post('/enseignants', [EnseignantController::class, 'store']);
        Route::get('/enseignants/{enseignant}', [EnseignantController::class, 'show']);
        Route::put('/enseignants/{enseignant}', [EnseignantController::class, 'update']);
        Route::delete('/enseignants/{enseignant}', [EnseignantController::class, 'destroy']);
        
        // Étudiants - Création, modification, suppression
        Route::post('/etudiants', [EtudiantController::class, 'store']);
        Route::get('/etudiants/{etudiant}', [EtudiantController::class, 'show']);
        Route::put('/etudiants/{etudiant}', [EtudiantController::class, 'update']);
        Route::delete('/etudiants/{etudiant}', [EtudiantController::class, 'destroy']);
    });

    // ====================================================================
    // 📚 COURS - Admin + Enseignant
    // ====================================================================
    
    Route::middleware('role:admin,enseignant')->group(function () {
        Route::get('/mes-cours', [CoursController::class, 'mesCours']);
        Route::get('/mes-etudiants', [EtudiantController::class, 'mesEtudiants']);
        Route::get('/mes-notes', [CoursController::class, 'mesNotes']);
        
        Route::apiResource('cours', CoursController::class);
        Route::get('/cours/{cour}/notes', [CoursController::class, 'notes']);
    });

    // ====================================================================
    // 📝 NOTES - Admin + Enseignant
    // ====================================================================
    
    Route::middleware('role:admin,enseignant')->group(function () {
        Route::apiResource('notes', NoteController::class);
        Route::get('/notes-grouped', [NoteController::class, 'indexGrouped']); // 🆕 NOUVELLE ROUTE
    });

    // ====================================================================
    // 👨‍🎓 ÉTUDIANT - Ses propres informations et notes
    // ====================================================================
    
    Route::middleware('role:etudiant')->group(function () {
        Route::get('/mes-informations', [EtudiantController::class, 'show']);
        Route::get('/mes-notes-etudiant', [NoteController::class, 'mesNotes']);
    });

    // ====================================================================
    // 📚 BIBLIOTHÈQUE MÉDICALE - Ressources
    // ====================================================================
    
    Route::prefix('ressources')->group(function () {
    
        // Accessibles à TOUS les utilisateurs authentifiés
        Route::get('/', [RessourceMedicaleController::class, 'index']);
        Route::get('/{ressourceMedicale}', [RessourceMedicaleController::class, 'show']);
        Route::get('/{ressourceMedicale}/telecharger', [RessourceMedicaleController::class, 'telecharger']);
        
        // 🆕 LIKE - Accessible à tous
        Route::post('/{ressourceMedicale}/like', [RessourceMedicaleController::class, 'like']);
        
        // 🆕 PRÉVISUALISATION - Accessible à tous
        Route::get('/{ressourceMedicale}/previsualiser', [RessourceMedicaleController::class, 'previsualiser']);
        
        // Réservées aux admin + enseignants
        Route::middleware('role:admin,enseignant')->group(function () {
            Route::post('/', [RessourceMedicaleController::class, 'store']);
            Route::put('/{ressourceMedicale}', [RessourceMedicaleController::class, 'update']);
            Route::delete('/{ressourceMedicale}', [RessourceMedicaleController::class, 'destroy']);
        });
    });

    // ====================================================================
    // 🏥 SUIVI SANITAIRE - Données Sanitaires
    // ====================================================================

    Route::prefix('donnees-sanitaires')->group(function () {
        
        // ✅ IMPORTANT : Routes spécifiques AVANT les routes paramétrées
        
        // Statistiques (avant /{id})
        Route::get('/statistiques', [DonneeSanitaireController::class, 'statistiques']);
        
        // 🆕 Recherche par code (avant /{id})
        Route::get('/rechercher-code', [DonneeSanitaireController::class, 'rechercherParCode']);
        
        // Liste
        Route::get('/', [DonneeSanitaireController::class, 'index']);
        
        // ✅ Routes paramétrées EN DERNIER
        Route::get('/{id}', [DonneeSanitaireController::class, 'show']);
        
        // Création accessible à tous (admin, enseignant, étudiant)
        Route::post('/', [DonneeSanitaireController::class, 'store']);
        
        // Modification/Suppression selon permissions (Policies)
        Route::put('/{id}', [DonneeSanitaireController::class, 'update']);
        Route::delete('/{id}', [DonneeSanitaireController::class, 'destroy']);
    });

    // ====================================================================
    // 📧 MESSAGERIE COMPLÈTE - Messages privés, Annonces, Forum
    // ====================================================================
    
    Route::prefix('messages')->group(function () {
        
        // Messages privés
        Route::get('/boite-reception', [MessageController::class, 'boiteReception']);
        Route::get('/boite-envoi', [MessageController::class, 'boiteEnvoi']);
        Route::get('/non-lus', [MessageController::class, 'nonLus']);
        Route::get('/conversation/{utilisateurId}', [MessageController::class, 'conversation']);
        
        // Annonces publiques
        Route::get('/annonces', [MessageController::class, 'annonces']);
        
        // Forum de discussion
        Route::get('/forum', [MessageController::class, 'forum']);
        
        // CRUD Messages
        Route::get('/{message}', [MessageController::class, 'show']);
        Route::post('/', [MessageController::class, 'store']);
        Route::delete('/{message}', [MessageController::class, 'destroy']);
        
        // Épingler une annonce (admin uniquement)
        Route::post('/{message}/toggle-epingle', [MessageController::class, 'toggleEpingle']);

        // Liker un message
        Route::post('/{message}/like', [MessageController::class, 'like']);

        // Réponses à un message
        Route::get('/{message}/reponses', [MessageController::class, 'reponses']);
        Route::post('/{message}/repondre', [MessageController::class, 'repondre']);
    });


    // 📚 RESSOURCES MÉDICALES
    Route::prefix('ressources')->middleware('auth:api')->group(function () {
        Route::get('/', [RessourceMedicaleController::class, 'index']);
        Route::get('/{ressourceMedicale}', [RessourceMedicaleController::class, 'show']);
        Route::post('/', [RessourceMedicaleController::class, 'store']);
        Route::put('/{ressourceMedicale}', [RessourceMedicaleController::class, 'update']);
        Route::delete('/{ressourceMedicale}', [RessourceMedicaleController::class, 'destroy']);
        Route::get('/{ressourceMedicale}/telecharger', [RessourceMedicaleController::class, 'telecharger']);
    });
});