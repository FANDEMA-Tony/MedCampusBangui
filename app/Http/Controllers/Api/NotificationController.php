<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\AnnonceGenerale;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // ENVOYER UNE ANNONCE GÉNÉRALE (admin)
    // POST /api/notifications/annonce
    // ─────────────────────────────────────────────────────────────
    public function annonce(Request $request)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if ($role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'sujet'       => 'required|string|max:255',
            'contenu'     => 'required|string',
            'destinataires' => 'required|in:tous,etudiants,enseignants',
        ]);

        $expediteur = $user->prenom . ' ' . $user->nom . ' (Administration)';

        // Récupérer les destinataires
        $query = Utilisateur::query();

        if ($request->destinataires === 'etudiants') {
            $query->where('role', 'etudiant');
        } elseif ($request->destinataires === 'enseignants') {
            $query->where('role', 'enseignant');
        }
        // 'tous' = pas de filtre

        $utilisateurs = $query->get();
        $nbEnvoyes    = 0;
        $nbErreurs    = 0;

        foreach ($utilisateurs as $dest) {
            try {
                Mail::to($dest->email)->send(new AnnonceGenerale(
                    nomDestinataire: $dest->prenom . ' ' . $dest->nom,
                    sujet:           $request->sujet,
                    contenu:         $request->contenu,
                    expediteur:      $expediteur,
                ));
                $nbEnvoyes++;
            } catch (\Exception $e) {
                Log::warning("Email annonce non envoyé à {$dest->email} : " . $e->getMessage());
                $nbErreurs++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Annonce envoyée à {$nbEnvoyes} utilisateur(s)",
            'data'    => [
                'nb_envoyes' => $nbEnvoyes,
                'nb_erreurs' => $nbErreurs,
                'total'      => $utilisateurs->count(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // TESTER L'ENVOI EMAIL (admin)
    // POST /api/notifications/test
    // ─────────────────────────────────────────────────────────────
    public function test(Request $request)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if ($role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        try {
            Mail::to($user->email)->send(new AnnonceGenerale(
                nomDestinataire: $user->prenom . ' ' . $user->nom,
                sujet:           '✅ Test Email MedCampus',
                contenu:         "Ceci est un email de test envoyé depuis MedCampus Bangui.\n\nSi vous recevez cet email, la configuration SMTP fonctionne correctement. 🎉",
                expediteur:      'Système MedCampus',
            ));

            return response()->json([
                'success' => true,
                'message' => "Email de test envoyé à {$user->email}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur envoi : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // STATISTIQUES EMAILS (admin)
    // GET /api/notifications/stats
    // ─────────────────────────────────────────────────────────────
    public function stats()
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if ($role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $nbEtudiants   = Utilisateur::where('role', 'etudiant')->count();
        $nbEnseignants = Utilisateur::where('role', 'enseignant')->count();
        $nbTotal       = Utilisateur::count();

        return response()->json([
            'success' => true,
            'data'    => [
                'nb_etudiants'   => $nbEtudiants,
                'nb_enseignants' => $nbEnseignants,
                'nb_total'       => $nbTotal,
            ],
        ]);
    }
}