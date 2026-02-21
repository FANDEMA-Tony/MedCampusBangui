<?php

namespace App\Http\Controllers\Api;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoteController extends BaseApiController
{
    /**
     * Liste simple des notes (pour modals)
     */
    public function index()
    {
        $this->authorize('viewAny', Note::class);
        
        $notes = Note::with(['etudiant', 'cours'])
                     ->orderBy('created_at', 'desc')
                     ->get();
        
        return $this->successResponse($notes, "Liste des notes récupérée avec succès");
    }

    /**
     * 🆕 Liste hiérarchique : Filière → Niveau → Semestre → Session
     */
    public function indexGrouped()
    {
        $this->authorize('viewAny', Note::class);
        
        $notes = Note::with(['etudiant', 'cours'])
                    ->get();
        
        // 🔥 CORRECTION : Grouper par FILIÈRE/NIVEAU DE L'ÉTUDIANT (car note n'a pas ces champs)
        $grouped = $notes->groupBy(function($note) {
            return $note->etudiant->filiere ?: 'Non spécifiée';
        })->map(function($filiereNotes, $filiere) {
            
            // Sous-grouper par niveau DE L'ÉTUDIANT
            $byNiveau = $filiereNotes->groupBy(function($note) {
                return $note->etudiant->niveau ?: 'Non spécifié';
            })->map(function($niveauNotes, $niveau) {
                
                // Sous-grouper par semestre DE LA NOTE
                $bySemestre = $niveauNotes->groupBy('semestre')->map(function($semestreNotes, $semestre) {
                    
                    // Sous-grouper par session DE LA NOTE
                    $bySession = $semestreNotes->groupBy('session')->map(function($sessionNotes, $session) {
                        return [
                            'session' => $session,
                            'count' => $sessionNotes->count(),
                            'notes' => $sessionNotes->values()
                        ];
                    })->values();
                    
                    return [
                        'semestre' => $semestre ?: 'S1',
                        'count' => $semestreNotes->count(),
                        'sessions' => $bySession
                    ];
                })->sortBy('semestre')->values();
                
                return [
                    'niveau' => $niveau,
                    'count' => $niveauNotes->count(),
                    'semestres' => $bySemestre
                ];
            })->sortBy(function($niveauGroup) {
                // Tri personnalisé : L1, L2, L3, M1, M2, Doctorat
                $ordre = ['L1' => 1, 'L2' => 2, 'L3' => 3, 'M1' => 4, 'M2' => 5, 'Doctorat' => 6];
                return $ordre[$niveauGroup['niveau']] ?? 99;
            })->values();
            
            return [
                'filiere' => $filiere,
                'total' => $filiereNotes->count(),
                'niveaux' => $byNiveau
            ];
        })->sortBy('filiere')->values();
        
        return response()->json([
            'success' => true,
            'message' => 'Notes groupées récupérées avec succès',
            'data' => $grouped,
            'total' => $notes->count()
        ], 200);
    }

    /**
     * Afficher une note spécifique
     */
    public function show(Note $note)
    {
        $this->authorize('view', $note);
        
        $note->load(['etudiant', 'cours']);
        
        return $this->successResponse($note, "Note récupérée avec succès");
    }

    /**
     * Créer une nouvelle note
     */
    public function store(Request $request)
    {
        $this->authorize('create', Note::class);
        
        $validator = Validator::make($request->all(), [
            'id_etudiant' => 'required|exists:etudiants,id_etudiant',
            'id_cours' => 'required|exists:cours,id_cours',
            'valeur' => 'required|numeric|min:0|max:20',
            'semestre' => 'required|in:S1,S2,S3,S4,S5,S6', // 🆕
            'date_evaluation' => 'required|date',
        ], [
            'id_etudiant.required' => 'L\'étudiant est obligatoire.',
            'id_etudiant.exists' => 'Cet étudiant n\'existe pas.',
            'id_cours.required' => 'Le cours est obligatoire.',
            'id_cours.exists' => 'Ce cours n\'existe pas.',
            'valeur.required' => 'La note est obligatoire.',
            'valeur.min' => 'La note ne peut pas être négative.',
            'valeur.max' => 'La note ne peut pas dépasser 20.',
            'semestre.required' => 'Le semestre est obligatoire.', // 🆕
            'semestre.in' => 'Le semestre doit être S1, S2, S3, S4, S5 ou S6.', // 🆕
            'date_evaluation.required' => 'La date d\'évaluation est obligatoire.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        try {
            // Créer la note (la session sera déterminée automatiquement par le modèle)
            $note = Note::create([
                'id_etudiant' => $request->id_etudiant,
                'id_cours' => $request->id_cours,
                'valeur' => $request->valeur,
                'semestre' => $request->semestre, // 🆕
                'date_evaluation' => $request->date_evaluation,
                // session et est_rattrape seront gérés automatiquement
            ]);

            $note->load(['etudiant', 'cours']);

            return $this->successResponse($note, "Note créée avec succès", 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la note.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une note
     */
    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);
        
        $validator = Validator::make($request->all(), [
            'id_etudiant' => 'sometimes|exists:etudiants,id_etudiant',
            'id_cours' => 'sometimes|exists:cours,id_cours',
            'valeur' => 'sometimes|numeric|min:0|max:20',
            'semestre' => 'sometimes|in:S1,S2,S3,S4,S5,S6', // 🆕
            'date_evaluation' => 'sometimes|date',
        ], [
            'id_etudiant.exists' => 'Cet étudiant n\'existe pas.',
            'id_cours.exists' => 'Ce cours n\'existe pas.',
            'valeur.min' => 'La note ne peut pas être négative.',
            'valeur.max' => 'La note ne peut pas dépasser 20.',
            'semestre.in' => 'Le semestre doit être S1, S2, S3, S4, S5 ou S6.', // 🆕
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        try {
            // La mise à jour déclenchera automatiquement la logique de session (Observer)
            $note->update($request->only([
                'id_etudiant',
                'id_cours',
                'valeur',
                'semestre', // 🆕
                'date_evaluation'
            ]));

            $note->load(['etudiant', 'cours']);

            return $this->successResponse($note, "Note mise à jour avec succès");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de la note.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une note
     */
    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);
        
        try {
            $note->delete();
            return $this->successResponse(null, "Note supprimée avec succès", 204);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression.'
            ], 500);
        }
    }

    /**
     * Mes notes (pour étudiant connecté)
     */
    public function mesNotes()
    {
        $utilisateur = auth()->user();
        
        $etudiant = \App\Models\Etudiant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
        
        if (!$etudiant) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas enregistré comme étudiant.'
            ], 403);
        }
        
        $notes = Note::where('id_etudiant', $etudiant->id_etudiant)
                     ->with('cours')
                     ->orderBy('date_evaluation', 'desc')
                     ->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Vos notes récupérées avec succès',
            'data' => $notes
        ], 200);
    }
}