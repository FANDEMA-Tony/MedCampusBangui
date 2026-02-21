<?php

namespace App\Http\Controllers\Api;

use App\Models\Cours;
use App\Models\Enseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        if (auth()->user()->role === 'admin') {
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
            if (auth()->user()->role === 'admin') {
                $id_enseignant = $request->id_enseignant;
            } else {
                // 🔹 SI ENSEIGNANT : récupère automatiquement son ID
                $utilisateur = auth()->user();
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
        if (auth()->user()->role === 'admin') {
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
            if (auth()->user()->role === 'admin' && $request->has('id_enseignant')) {
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
            $utilisateur = auth()->user();
            
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
            $utilisateur = auth()->user();
            
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
}