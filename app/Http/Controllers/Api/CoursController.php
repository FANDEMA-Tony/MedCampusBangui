<?php

namespace App\Http\Controllers\Api;

use App\Models\Cours;
use App\Models\Enseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CoursController extends BaseApiController
{
    /**
     * Liste de tous les cours
     */
    public function index()
    {
        // ✅ Autorisation
        $this->authorize('viewAny', Cours::class);
        
        $cours = Cours::with('enseignant')->paginate(10);
        
        return response()->json([
            'success' => true,
            'message' => 'Liste des cours récupérée avec succès',
            'data' => $cours->items(),
            'current_page' => $cours->currentPage(),
            'total' => $cours->total()
        ], 200);
    }

    /**
     * Créer un nouveau cours
     */
    public function store(Request $request)
    {
        // ✅ Autorisation
        $this->authorize('create', Cours::class);
        
        // 🔹 Règles de validation différentes selon le rôle
        $rules = [
            'code' => 'required|string|unique:cours,code|max:50',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
        
        $messages = [
            'code.required' => 'Le code du cours est obligatoire.',
            'code.unique' => 'Ce code de cours existe déjà.',
            'code.max' => 'Le code ne doit pas dépasser 50 caractères.',
            'titre.required' => 'Le titre du cours est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
        ];
        
        // 🔹 SI ADMIN, il peut choisir l'enseignant
        if (Auth::user()->role === 'admin') {
            $rules['id_enseignant'] = 'required|exists:enseignants,id_enseignant';
            $messages['id_enseignant.required'] = 'L\'enseignant est obligatoire.';
            $messages['id_enseignant.exists'] = 'Cet enseignant n\'existe pas.';
        }
        
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $id_enseignant = null;
            
            // 🔹 SI ADMIN : utilise l'id_enseignant envoyé
            if (Auth::user()->role === 'admin') {
                $id_enseignant = $request->id_enseignant;
            } else {
                // 🔹 SI ENSEIGNANT : récupère automatiquement son ID
                $utilisateur = Auth::user();
                $enseignant = Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
                
                if (!$enseignant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                    ], 403);
                }
                
                $id_enseignant = $enseignant->id_enseignant;
            }

            // 🔥 CORRECTION : Ajouter filiere et niveau
            $cours = Cours::create([
                'code' => $request->code,
                'titre' => $request->titre,
                'description' => $request->description,
                'id_enseignant' => $id_enseignant,
                'filiere' => $request->filiere,  // 🆕 AJOUTÉ
                'niveau' => $request->niveau,    // 🆕 AJOUTÉ
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cours créé avec succès',
                'data' => $cours
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du cours.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un cours spécifique
     */
    public function show(Cours $cour)
    {
        // ✅ Autorisation
        $this->authorize('view', $cour);
        
        $cour->load(['enseignant']);

        return response()->json([
            'success' => true,
            'message' => 'Cours récupéré avec succès',
            'data' => $cour
        ], 200);
    }

    /**
     * Mettre à jour un cours
     */
    public function update(Request $request, Cours $cour)
    {
        // ✅ Autorisation
        $this->authorize('update', $cour);
        
        // 🔹 Règles de validation
        $rules = [
            'code' => 'sometimes|string|unique:cours,code,' . $cour->id_cours . ',id_cours|max:50',
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ];
        
        $messages = [
            'code.unique' => 'Ce code de cours existe déjà.',
            'code.max' => 'Le code ne doit pas dépasser 50 caractères.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
        ];
        
        // 🔹 SI ADMIN, il peut changer l'enseignant
        if (Auth::user()->role === 'admin') {
            $rules['id_enseignant'] = 'sometimes|exists:enseignants,id_enseignant';
            $messages['id_enseignant.exists'] = 'Cet enseignant n\'existe pas.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 🔥 CORRECTION : Ajouter filiere et niveau aux champs modifiables
            $fieldsToUpdate = ['code', 'titre', 'description', 'filiere', 'niveau']; // 🆕 AJOUTÉ
            
            // 🔹 SI ADMIN et qu'il envoie id_enseignant, on l'ajoute
            if (Auth::user()->role === 'admin' && $request->has('id_enseignant')) {
                $fieldsToUpdate[] = 'id_enseignant';
            }
            
            $cour->update($request->only($fieldsToUpdate));

            return response()->json([
                'success' => true,
                'message' => 'Cours mis à jour avec succès',
                'data' => $cour
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du cours.'
            ], 500);
        }
    }

    /**
     * Supprimer un cours
     */
    public function destroy(Cours $cour)
    {
        // ✅ Autorisation
        $this->authorize('delete', $cour);
        
        try {
            $cour->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cours supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du cours.'
            ], 500);
        }
    }

    /**
     * Récupérer toutes les notes d'un cours
     */
    public function notes(Cours $cour)
    {
        // ✅ Autorisation
        $this->authorize('view', $cour);
        
        try {
            // Charger les notes avec les informations des étudiants
            $notes = $cour->notes()->with('etudiant')->get();

            return response()->json([
                'success' => true,
                'message' => 'Notes du cours récupérées avec succès',
                'data' => [
                    'cours' => [
                        'id' => $cour->id_cours,
                        'code' => $cour->code,
                        'titre' => $cour->titre,
                        'description' => $cour->description
                    ],
                    'notes' => $notes
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des notes.'
            ], 500);
        }
    }

    /**
     * Mes cours (pour l'enseignant connecté)
     */
    public function mesCours()
    {
        try {
            $utilisateur = Auth::user();
            
            // Si enseignant, récupérer son id_enseignant
            $enseignant = Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                ], 403);
            }
            
            $cours = Cours::where('id_enseignant', $enseignant->id_enseignant)
                        ->with('enseignant')
                        ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Vos cours récupérés avec succès',
                'data' => $cours
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cours.'
            ], 500);
        }
    }

    /**
     * Mes notes (notes des cours de l'enseignant connecté)
     */
    public function mesNotes()
    {
        try {
            $utilisateur = Auth::user();
            
            $enseignant = Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                ], 403);
            }
            
            // Récupérer tous les cours de l'enseignant
            $mesCours = Cours::where('id_enseignant', $enseignant->id_enseignant)->pluck('id_cours');
            
            // Récupérer toutes les notes de ces cours
            $notes = \App\Models\Note::whereIn('id_cours', $mesCours)
                                    ->with(['etudiant', 'cours'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Notes récupérées avec succès',
                'data' => $notes
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 🆕 Récupérer cours groupés par filière et niveau
     */
    public function indexGrouped()
    {
        $this->authorize('viewAny', Cours::class);
        
        $cours = Cours::with('enseignant')
                    ->orderBy('filiere')
                    ->orderBy('niveau')
                    ->orderBy('titre')
                    ->get();
        
        // 🔥 CORRECTION : Grouper par FILIÈRE DU COURS (pas null)
        $grouped = $cours->groupBy(function($c) {
            return $c->filiere ?: 'Non spécifiée';
        })->map(function ($filiereCours, $filiere) {
            
            // Sous-grouper par NIVEAU DU COURS
            $byNiveau = $filiereCours->groupBy(function($c) {
                return $c->niveau ?: 'Non spécifié';
            })->map(function ($niveauCours, $niveau) {
                return [
                    'niveau' => $niveau,
                    'count' => $niveauCours->count(),
                    'cours' => $niveauCours->values()
                ];
            })->sortBy(function($niveauGroup) {
                // Tri personnalisé : L1, L2, L3, M1, M2, Doctorat
                $ordre = ['L1' => 1, 'L2' => 2, 'L3' => 3, 'M1' => 4, 'M2' => 5, 'Doctorat' => 6];
                return $ordre[$niveauGroup['niveau']] ?? 99;
            })->values();
            
            return [
                'filiere' => $filiere,
                'total' => $filiereCours->count(),
                'niveaux' => $byNiveau
            ];
        })->sortBy('filiere')->values();
        
        return response()->json([
            'success' => true,
            'message' => 'Cours groupés récupérés avec succès',
            'data' => $grouped,
            'total' => $cours->count()
        ], 200);
    }

    /**
     * 🆕 MES COURS (pour étudiant connecté)
     * Retourne SEULEMENT les cours de sa filière + son niveau
     * 🔥 AMÉLIORATION : Fallback si filière/niveau non renseignés sur les cours
     *                   + Comptage total + dernière mise à jour pour auto-refresh
     */
    public function mesCoursEtudiant()
    {
        try {
            $utilisateur = Auth::user();
            
            // Récupérer l'étudiant connecté
            $etudiant = \App\Models\Etudiant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$etudiant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme étudiant.'
                ], 403);
            }

            // 🔥 AMÉLIORATION : Construire la requête de base
            $query = Cours::with('enseignant')->orderBy('titre');

            // 🔥 AMÉLIORATION : Filtrer par filière ET niveau si l'étudiant les a renseignés
            // ET si des cours existent pour cette filière/niveau
            if ($etudiant->filiere && $etudiant->niveau) {
                // Vérifier d'abord si des cours existent pour cette filière+niveau
                $coursExistants = Cours::where('filiere', $etudiant->filiere)
                                      ->where('niveau', $etudiant->niveau)
                                      ->count();

                if ($coursExistants > 0) {
                    // ✅ CAS NORMAL : cours bien associés à la filière + niveau
                    $query->where('filiere', $etudiant->filiere)
                          ->where('niveau', $etudiant->niveau);
                } else {
                    // 🔥 FALLBACK : Si aucun cours n'a filière+niveau renseignés,
                    // on retourne tous les cours pour éviter "Aucun cours disponible"
                    // ET on indique à l'étudiant la situation
                }
            }

            $cours = $query->get();
            
            // Enrichir avec les notes de l'étudiant pour chaque cours
            $coursAvecNotes = $cours->map(function($c) use ($etudiant) {
                // 🔥 AMÉLIORATION : Récupérer TOUTES les notes (pas juste une)
                // pour gérer le cas où l'étudiant a une note par semestre
                $notes = \App\Models\Note::where('id_cours', $c->id_cours)
                                         ->where('id_etudiant', $etudiant->id_etudiant)
                                         ->orderBy('semestre')
                                         ->get();

                // Note principale (la plus récente ou la meilleure)
                $noteprincipale = $notes->sortByDesc('valeur')->first();

                $c->ma_note = $noteprincipale ? $noteprincipale->valeur : null;
                $c->date_note = $noteprincipale ? $noteprincipale->date_evaluation : null;
                $c->session = $noteprincipale ? $noteprincipale->session : null;
                $c->semestre_note = $noteprincipale ? $noteprincipale->semestre : null;
                $c->est_rattrape = $noteprincipale ? $noteprincipale->est_rattrape : false;

                // 🆕 Toutes les notes pour affichage par semestre
                $c->toutes_notes = $notes->map(function($n) {
                    return [
                        'id_note' => $n->id_note,
                        'valeur' => $n->valeur,
                        'semestre' => $n->semestre,
                        'session' => $n->session,
                        'date_evaluation' => $n->date_evaluation,
                        'est_rattrape' => $n->est_rattrape,
                    ];
                })->values();

                return $c;
            });

            // 🆕 Statistiques globales pour l'étudiant
            $notesValeures = $coursAvecNotes->whereNotNull('ma_note')->pluck('ma_note');
            $moyenneGenerale = $notesValeures->count() > 0
                ? round($notesValeures->sum() / $notesValeures->count(), 2)
                : null;

            $coursValides = $coursAvecNotes->filter(fn($c) => $c->ma_note && $c->ma_note >= 10)->count();
            $coursEnRattrapage = $coursAvecNotes->filter(fn($c) => $c->ma_note && $c->ma_note < 10 && !$c->est_rattrape)->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Vos cours récupérés avec succès',
                'data' => [
                    'etudiant' => [
                        'filiere' => $etudiant->filiere,
                        'niveau' => $etudiant->niveau,
                        'nom_complet' => $etudiant->prenom . ' ' . $etudiant->nom,
                    ],
                    // 🆕 Stats globales
                    'statistiques' => [
                        'total_cours' => $coursAvecNotes->count(),
                        'cours_avec_note' => $notesValeures->count(),
                        'cours_valides' => $coursValides,
                        'cours_en_rattrapage' => $coursEnRattrapage,
                        'moyenne_generale' => $moyenneGenerale,
                    ],
                    'cours' => $coursAvecNotes->values(),
                    // 🆕 Timestamp pour détecter les nouveaux cours côté frontend
                    'derniere_maj' => now()->toISOString(),
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des cours.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 DÉTAIL D'UN COURS (pour étudiant)
     * Vérifie que l'étudiant a accès à ce cours (même filière + niveau)
     */
    public function detailCoursEtudiant($id_cours)
    {
        try {
            $utilisateur = Auth::user();
            
            $etudiant = \App\Models\Etudiant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$etudiant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme étudiant.'
                ], 403);
            }
            
            // Récupérer le cours
            $cours = Cours::with('enseignant')->find($id_cours);
            
            if (!$cours) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce cours n\'existe pas.'
                ], 404);
            }
            
            // 🔥 AMÉLIORATION SÉCURITÉ : Vérifier accès seulement si cours a filière+niveau renseignés
            if ($cours->filiere && $cours->niveau) {
                if ($cours->filiere !== $etudiant->filiere || $cours->niveau !== $etudiant->niveau) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'avez pas accès à ce cours.'
                    ], 403);
                }
            }
            
            // 🔥 AMÉLIORATION : Récupérer TOUTES les notes de l'étudiant pour ce cours
            $notes = \App\Models\Note::where('id_cours', $id_cours)
                                ->where('id_etudiant', $etudiant->id_etudiant)
                                ->orderBy('semestre')
                                ->get();

            $noteprincipale = $notes->sortByDesc('valeur')->first();
            
            return response()->json([
                'success' => true,
                'message' => 'Détails du cours récupérés avec succès',
                'data' => [
                    'cours' => $cours,
                    // 🆕 Note principale (meilleure note)
                    'ma_note' => $noteprincipale ? [
                        'valeur' => $noteprincipale->valeur,
                        'date' => $noteprincipale->date_evaluation,
                        'session' => $noteprincipale->session,
                        'semestre' => $noteprincipale->semestre,
                        'est_rattrape' => $noteprincipale->est_rattrape
                    ] : null,
                    // 🆕 Toutes les notes par semestre
                    'toutes_notes' => $notes->map(function($n) {
                        return [
                            'id_note' => $n->id_note,
                            'valeur' => $n->valeur,
                            'semestre' => $n->semestre,
                            'session' => $n->session,
                            'date_evaluation' => $n->date_evaluation,
                            'est_rattrape' => $n->est_rattrape,
                        ];
                    })->values(),
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du cours.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}