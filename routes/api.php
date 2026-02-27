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
use App\Http\Controllers\Api\CalendrierController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\QuizController;


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
    Route::get('/enseignants-grouped', [EnseignantController::class, 'indexGrouped']);
    Route::get('/cours-grouped', [CoursController::class, 'indexGrouped']);
    Route::get('/enseignants/{enseignant}/cours', [EnseignantController::class, 'cours']);

    // ====================================================================
    // 👨‍🎓 ÉTUDIANTS - Liste accessible à admin + enseignant (pour messagerie)
    // ====================================================================

    Route::get('/etudiants', [EtudiantController::class, 'index']);
    Route::get('/etudiants-grouped', [EtudiantController::class, 'indexGrouped']);
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
    // 📊 ANALYTICS - Chaque rôle accède à ses propres stats
    // ====================================================================

    Route::get('/analytics/admin', [App\Http\Controllers\Api\AnalyticsController::class, 'statsAdmin'])
        ->middleware('role:admin');

    Route::get('/analytics/etudiant', [App\Http\Controllers\Api\AnalyticsController::class, 'statsEtudiant'])
        ->middleware('role:etudiant');

    Route::get('/analytics/enseignant', [App\Http\Controllers\Api\AnalyticsController::class, 'statsEnseignant'])
        ->middleware('role:enseignant');

    // ====================================================================
    // 📚 COURS - Admin + Enseignant
    // ====================================================================

    Route::middleware('role:admin,enseignant')->group(function () {
        Route::get('/mes-cours', [CoursController::class, 'mesCours']);
        Route::get('/mes-etudiants', [EtudiantController::class, 'mesEtudiants']);
        Route::get('/etudiants-par-cours/{id_cours}', [EtudiantController::class, 'getEtudiantsParCours']);
        Route::get('/mes-notes', [CoursController::class, 'mesNotes']);

        Route::apiResource('cours', CoursController::class);
        Route::get('/cours/{cour}/notes', [CoursController::class, 'notes']);
    });

    // ====================================================================
    // 📝 NOTES - Admin + Enseignant
    // ====================================================================

    Route::middleware('role:admin,enseignant')->group(function () {
        Route::apiResource('notes', NoteController::class);
        Route::get('/notes-grouped', [NoteController::class, 'indexGrouped']);
    });

    // ====================================================================
    // 👨‍🎓 ÉTUDIANT - Ses propres informations et notes
    // ====================================================================

    Route::middleware('role:etudiant')->group(function () {
        Route::get('/mes-informations', [EtudiantController::class, 'show']);
        Route::get('/mes-notes-etudiant', [NoteController::class, 'mesNotes']);
        Route::get('/mes-cours-etudiant', [CoursController::class, 'mesCoursEtudiant']);
        Route::get('/mes-cours-etudiant/{id_cours}', [CoursController::class, 'detailCoursEtudiant']);
    });

    // ====================================================================
    // 📚 BIBLIOTHÈQUE MÉDICALE - Ressources
    // ====================================================================
    // ✅ CORRECTION : Routes spécifiques AVANT les routes paramétrées /{ressourceMedicale}
    // ✅ CORRECTION : Suppression du 2ème bloc dupliqué (middleware auth:api) qui causait le bug 404

    Route::prefix('ressources')->group(function () {

        // ── Routes sans paramètre EN PREMIER ──
        Route::get('/', [RessourceMedicaleController::class, 'index']);

        // ── Réservées aux admin + enseignants ──
        Route::middleware('role:admin,enseignant')->group(function () {
            Route::post('/', [RessourceMedicaleController::class, 'store']);
        });

        // ✅ Routes avec suffixe spécifique AVANT /{ressourceMedicale} seul
        // (sinon Laravel intercepte "telecharger", "previsualiser", "like" comme un ID)
        Route::get('/{ressourceMedicale}/telecharger', [RessourceMedicaleController::class, 'telecharger']);
        Route::get('/{ressourceMedicale}/previsualiser', [RessourceMedicaleController::class, 'previsualiser']);
        Route::post('/{ressourceMedicale}/like', [RessourceMedicaleController::class, 'like']);

        // ── Routes paramétrées simples EN DERNIER ──
        Route::get('/{ressourceMedicale}', [RessourceMedicaleController::class, 'show']);

        // ── Modification/Suppression réservées aux admin + enseignants ──
        Route::middleware('role:admin,enseignant')->group(function () {
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

        // Recherche par code (avant /{id})
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

        // ✅ Routes avec suffixe spécifique AVANT /{message} seul
        Route::post('/{message}/toggle-epingle', [MessageController::class, 'toggleEpingle']);
        Route::post('/{message}/like', [MessageController::class, 'like']);
        Route::get('/{message}/reponses', [MessageController::class, 'reponses']);
        Route::post('/{message}/repondre', [MessageController::class, 'repondre']);

        // ── Routes paramétrées simples EN DERNIER ──
        Route::get('/{message}', [MessageController::class, 'show']);
        Route::post('/', [MessageController::class, 'store']);
        Route::delete('/{message}', [MessageController::class, 'destroy']);
    });


    Route::prefix('calendrier')->group(function () {
        Route::get('mois',                    [CalendrierController::class, 'donneesCalendrier']);
        Route::get('evenements',              [CalendrierController::class, 'indexEvenements']);
        Route::get('evenements/etudiant',     [CalendrierController::class, 'evenementsEtudiant']);
        Route::post('evenements',             [CalendrierController::class, 'storeEvenement']);
        Route::put('evenements/{id}',         [CalendrierController::class, 'updateEvenement']);
        Route::delete('evenements/{id}',      [CalendrierController::class, 'destroyEvenement']);
        Route::get('emploi-du-temps',         [CalendrierController::class, 'indexEmploi']);
        Route::post('emploi-du-temps',        [CalendrierController::class, 'storeEmploi']);
        Route::put('emploi-du-temps/{id}',    [CalendrierController::class, 'updateEmploi']);
        Route::delete('emploi-du-temps/{id}', [CalendrierController::class, 'destroyEmploi']);
        Route::get('examens',                 [CalendrierController::class, 'indexExamens']);
        Route::post('examens',                [CalendrierController::class, 'storeExamen']);
        Route::put('examens/{id}',            [CalendrierController::class, 'updateExamen']);
        Route::delete('examens/{id}',         [CalendrierController::class, 'destroyExamen']);
    });

    // ── RECHERCHE GLOBALE ─────────────────────────────────────────
    Route::get('search', [SearchController::class, 'search']);

    // ✅ QUIZ — Sprint 5
    Route::prefix('quiz')->group(function () {
        Route::get('/',                          [QuizController::class, 'index']);
        Route::post('/',                         [QuizController::class, 'store']);
        Route::get('/{id}',                      [QuizController::class, 'show']);
        Route::put('/{id}',                      [QuizController::class, 'update']);
        Route::delete('/{id}',                   [QuizController::class, 'destroy']);
        Route::post('/{id}/questions',           [QuizController::class, 'storeQuestion']);
        Route::put('/questions/{idQuestion}',    [QuizController::class, 'updateQuestion']);
        Route::delete('/questions/{idQuestion}', [QuizController::class, 'destroyQuestion']);
        Route::post('/{id}/soumettre',           [QuizController::class, 'soumettre']);
        Route::get('/{id}/stats',                [QuizController::class, 'stats']);
        Route::get('/{id}/mes-tentatives',       [QuizController::class, 'mesTentatives']);
        Route::post('/{id}/toggle-publie',       [QuizController::class, 'togglePublie']);
    });
    // ====================================================================
    // ✅ SUPPRIMÉ : Le 2ème bloc "ressources" dupliqué avec middleware('auth:api')
    // qui causait le conflit de routes et le bug 404 sur /telecharger
    // Toutes les routes ressources sont déjà gérées dans le bloc ci-dessus
    // ====================================================================
});
