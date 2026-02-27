<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Certificat;
use App\Models\Etudiant;
use App\Models\Note;
use App\Mail\CertificatSigne;
use Illuminate\Support\Facades\Mail;

class CertificatController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // VÉRIFIER ÉLIGIBILITÉ — quels niveaux l'étudiant peut certifier
    // GET /api/certificats/eligibilite
    // ─────────────────────────────────────────────────────────────
    public function eligibilite(Request $request)
    {
        /** @var \App\Models\Utilisateur $user */
        $user     = auth()->user();
        $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)
            ->first();

        if (!$etudiant) {
            return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
        }

        // Récupérer toutes les notes de l'étudiant avec les cours
        $notes = Note::where('id_etudiant', $etudiant->id_etudiant)
            ->with('cours')
            ->get();

        // Grouper par niveau
        $niveauxGroupes = [];
        foreach ($notes as $note) {
            $niveau  = $note->cours->niveau  ?? 'Inconnu';
            $filiere = $note->cours->filiere ?? 'Inconnue';
            $cle     = "{$filiere}_{$niveau}";

            if (!isset($niveauxGroupes[$cle])) {
                $niveauxGroupes[$cle] = [
                    'filiere'      => $filiere,
                    'niveau'       => $niveau,
                    'notes'        => [],
                    'tous_valides' => true,
                    'moyenne'      => 0,
                ];
            }

            $niveauxGroupes[$cle]['notes'][] = [
                'id_note'      => $note->id_note,
                'id_cours'     => $note->id_cours,
                'titre_cours'  => $note->cours->titre ?? 'Inconnu',
                'code_cours'   => $note->cours->code  ?? '',
                'valeur'       => $note->valeur,
                'valide'       => floatval($note->valeur) >= 10,
                'session'      => $note->session ?? 'normale',
            ];

            if (floatval($note->valeur) < 10) {
                $niveauxGroupes[$cle]['tous_valides'] = false;
            }
        }

        // Calculer moyennes et éligibilité
        $resultats = [];
        foreach ($niveauxGroupes as $cle => $groupe) {
            $somme   = array_sum(array_column($groupe['notes'], 'valeur'));
            $nb      = count($groupe['notes']);
            $moyenne = $nb > 0 ? round($somme / $nb, 2) : 0;

            // Vérifier si certificat déjà généré
            $dejaGenere = Certificat::where('id_etudiant', $etudiant->id_etudiant)
                ->where('filiere',       $groupe['filiere'])
                ->where('niveau_valide', $groupe['niveau'])
                ->exists();

            $resultats[] = [
                'filiere'        => $groupe['filiere'],
                'niveau'         => $groupe['niveau'],
                'niveau_suivant' => Certificat::niveauSuivant($groupe['niveau']),
                'nb_cours'       => $nb,
                'nb_valides'     => count(array_filter($groupe['notes'], fn($n) => $n['valide'])),
                'moyenne'        => $moyenne,
                'mention'        => Certificat::calculerMention($moyenne),
                'tous_valides'   => $groupe['tous_valides'],
                'eligible'       => $groupe['tous_valides'] && $moyenne >= 10,
                'deja_genere'    => $dejaGenere,
                'cours'          => $groupe['notes'],
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'etudiant'  => $etudiant,
                'niveaux'   => $resultats,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GÉNÉRER UN CERTIFICAT
    // POST /api/certificats/generer
    // ─────────────────────────────────────────────────────────────
    public function generer(Request $request)
    {
        /** @var \App\Models\Utilisateur $user */
        $user     = auth()->user();
        $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)
            ->first();

        if (!$etudiant) {
            return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
        }

        $request->validate([
            'filiere' => 'required|string',
            'niveau'  => 'required|string',
        ]);

        // Vérifier que le certificat n'existe pas déjà
        $existe = Certificat::where('id_etudiant',   $etudiant->id_etudiant)
            ->where('filiere',        $request->filiere)
            ->where('niveau_valide',  $request->niveau)
            ->first();

        if ($existe) {
            return response()->json([
                'success' => true,
                'data'    => $existe->load('etudiant'),
                'message' => 'Certificat déjà généré',
            ]);
        }

        // Récupérer les notes du niveau
        $notes = Note::where('id_etudiant', $etudiant->id_etudiant)
            ->with('cours')
            ->whereHas('cours', function ($q) use ($request) {
                $q->where('filiere', $request->filiere)
                    ->where('niveau',  $request->niveau);
            })
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Aucune note trouvée pour ce niveau'], 404);
        }

        // Vérifier que tous les cours sont validés
        $tousValides = $notes->every(fn($n) => floatval($n->valeur) >= 10);
        if (!$tousValides) {
            return response()->json(['success' => false, 'message' => 'Tous les cours ne sont pas validés'], 422);
        }

        // Calculer moyenne
        $moyenne = round($notes->avg('valeur'), 2);

        // Construire liste cours validés
        $coursValides = $notes->map(fn($n) => [
            'titre'   => $n->cours->titre ?? 'Inconnu',
            'code'    => $n->cours->code  ?? '',
            'note'    => $n->valeur,
            'session' => $n->session ?? 'normale',
        ])->toArray();

        // Créer le certificat
        $certificat = Certificat::create([
            'id_etudiant'      => $etudiant->id_etudiant,
            'filiere'          => $request->filiere,
            'niveau_valide'    => $request->niveau,
            'niveau_suivant'   => Certificat::niveauSuivant($request->niveau),
            'annee_academique' => date('Y') . '-' . (date('Y') + 1),
            'cours_valides'    => $coursValides,
            'moyenne_generale' => $moyenne,
            'mention'          => Certificat::calculerMention($moyenne),
            'code_verification' => Certificat::genererCode($request->filiere, $request->niveau),
            'nom_responsable'  => null,
            'titre_responsable' => null,
            'signature_base64' => null,
            'est_signe'        => false,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $certificat->load('etudiant'),
            'message' => 'Certificat généré avec succès',
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // MES CERTIFICATS (étudiant)
    // GET /api/certificats
    // ─────────────────────────────────────────────────────────────
    public function mesCertificats()
    {
        /** @var \App\Models\Utilisateur $user */
        $user     = auth()->user();
        $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)
            ->first();

        if (!$etudiant) {
            return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
        }

        $certificats = Certificat::where('id_etudiant', $etudiant->id_etudiant)
            ->with('etudiant')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $certificats]);
    }

    // ─────────────────────────────────────────────────────────────
    // TOUS LES CERTIFICATS (admin)
    // GET /api/certificats/tous
    // ─────────────────────────────────────────────────────────────
    public function tous()
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if ($role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $certificats = Certificat::with('etudiant')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $certificats]);
    }

    // ─────────────────────────────────────────────────────────────
    // AJOUTER SIGNATURE RESPONSABLE (admin)
    // POST /api/certificats/{id}/signer
    // ─────────────────────────────────────────────────────────────
    public function signer(Request $request, $id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if ($role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'nom_responsable'   => 'required|string|max:255',
            'titre_responsable' => 'required|string|max:255',
            'signature_base64'  => 'nullable|string',
        ]);

        $certificat = Certificat::findOrFail($id);
        $certificat->update([
            'nom_responsable'   => $request->nom_responsable,
            'titre_responsable' => $request->titre_responsable,
            'signature_base64'  => $request->signature_base64,
            'est_signe'         => true,
        ]);

        try {
            $etudiant    = $certificat->etudiant;
            $utilisateur = $etudiant->utilisateur;

            Mail::to($utilisateur->email)->send(new CertificatSigne(
                nomEtudiant: $etudiant->prenom . ' ' . $etudiant->nom,
                niveauValide: $certificat->niveau_valide,
                filiere: $certificat->filiere,
                nomResponsable: $request->nom_responsable,
                codeVerification: $certificat->code_verification,
            ));
        } catch (\Exception $e) {
            \Log::warning('Email certificat non envoyé : ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data'    => $certificat->load('etudiant'),
            'message' => 'Certificat signé avec succès',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // VÉRIFICATION PUBLIQUE (sans auth)
    // GET /api/certificats/verifier/{code}
    // ─────────────────────────────────────────────────────────────
    public function verifier($code)
    {
        $certificat = Certificat::where('code_verification', $code)
            ->with('etudiant')
            ->first();

        if (!$certificat) {
            return response()->json([
                'success' => false,
                'message' => 'Certificat introuvable ou invalide',
                'valide'  => false,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'valide'  => true,
            'data'    => [
                'nom_etudiant'     => $certificat->etudiant->prenom . ' ' . $certificat->etudiant->nom,
                'matricule'        => $certificat->etudiant->matricule,
                'filiere'          => $certificat->filiere,
                'niveau_valide'    => $certificat->niveau_valide,
                'niveau_suivant'   => $certificat->niveau_suivant,
                'annee_academique' => $certificat->annee_academique,
                'moyenne_generale' => $certificat->moyenne_generale,
                'mention'          => $certificat->mention,
                'date_emission'    => $certificat->created_at->format('d/m/Y'),
                'nom_responsable'  => $certificat->nom_responsable,
                'titre_responsable' => $certificat->titre_responsable,
                'est_signe'        => $certificat->est_signe,
                'code_verification' => $certificat->code_verification,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DONNÉES POUR TÉLÉCHARGEMENT PDF (frontend génère le PDF)
    // GET /api/certificats/{id}/telecharger
    // ─────────────────────────────────────────────────────────────
    public function telecharger($id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user     = auth()->user();
        $role     = (string) $user->role;

        $certificat = Certificat::with('etudiant')->findOrFail($id);

        // Seul l'étudiant concerné ou admin peut télécharger
        if ($role === 'etudiant') {
            $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)->first();
            if (!$etudiant || $certificat->id_etudiant !== $etudiant->id_etudiant) {
                return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $certificat,
        ]);
    }
}
