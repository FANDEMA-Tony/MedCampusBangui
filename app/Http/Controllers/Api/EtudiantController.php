<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtudiantController extends BaseApiController
{
    /**
     * Liste TOUS les étudiants (pagination désactivée pour groupement frontend)
     */
    public function index()
    {
        $this->authorize('viewAny', Etudiant::class);
        
        // ✅ Récupérer TOUS les étudiants (pas de pagination)
        // Le groupement se fera côté frontend
        $etudiants = Etudiant::orderBy('filiere')
                             ->orderBy('niveau')
                             ->orderBy('nom')
                             ->get();
        
        return $this->successResponse($etudiants, "Liste des étudiants récupérée avec succès");
    }

    /**
     * 🆕 Récupérer étudiants groupés par filière et niveau
     */
    public function indexGrouped()
    {
        $this->authorize('viewAny', Etudiant::class);
        
        $etudiants = Etudiant::orderBy('filiere')
                             ->orderBy('niveau')
                             ->orderBy('nom')
                             ->get();
        
        // Grouper par filière
        $grouped = $etudiants->groupBy('filiere')->map(function ($filiereEtudiants, $filiere) {
            // Sous-grouper par niveau
            $byNiveau = $filiereEtudiants->groupBy('niveau')->map(function ($niveauEtudiants, $niveau) {
                return [
                    'niveau' => $niveau ?: 'Non spécifié',
                    'count' => $niveauEtudiants->count(),
                    'etudiants' => $niveauEtudiants->values()
                ];
            })->sortBy('niveau')->values();
            
            return [
                'filiere' => $filiere ?: 'Non spécifiée',
                'total' => $filiereEtudiants->count(),
                'niveaux' => $byNiveau
            ];
        })->sortBy('filiere')->values();
        
        return response()->json([
            'success' => true,
            'message' => 'Étudiants groupés récupérés avec succès',
            'data' => $grouped,
            'total' => $etudiants->count()
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Etudiant::class);
        
        try {
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|email|unique:etudiants,email',
                'mot_de_passe' => 'required|string|min:8',
                'date_naissance' => 'required|date',
                'filiere' => 'nullable|string|max:255',
                'niveau' => 'required|in:L1,L2,L3,M1,M2,Doctorat',
                'statut' => 'nullable|in:actif,suspendu,diplome'
            ]);

            // Créer l'utilisateur
            $utilisateur = \App\Models\Utilisateur::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'mot_de_passe' => bcrypt($data['mot_de_passe']),
                'role' => 'etudiant',
            ]);

            // Créer l'étudiant
            $etudiant = Etudiant::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'matricule' => 'ETU' . str_pad($utilisateur->id_utilisateur, 6, '0', STR_PAD_LEFT),
                'date_naissance' => $data['date_naissance'],
                'filiere' => $data['filiere'] ?? null,
                'niveau' => $data['niveau'],
                'statut' => $data['statut'] ?? 'actif',
            ]);

            return $this->successResponse($etudiant, "Étudiant créé avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Etudiant $etudiant)
    {
        $this->authorize('view', $etudiant);
        return $this->successResponse($etudiant->load('notes'), "Étudiant récupéré avec succès");
    }

    public function update(Request $request, Etudiant $etudiant)
    {
        $this->authorize('update', $etudiant);
        
        try {
            $data = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:etudiants,email,' . $etudiant->id_etudiant . ',id_etudiant',
                'mot_de_passe' => 'sometimes|nullable|string|min:8',
                'date_naissance' => 'sometimes|date',
                'filiere' => 'sometimes|nullable|string|max:255',
                'niveau' => 'sometimes|in:L1,L2,L3,M1,M2,Doctorat',
                'statut' => 'sometimes|in:actif,suspendu,diplome'
            ]);

            // Mettre à jour mot de passe utilisateur si fourni
            if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
                $utilisateur = \App\Models\Utilisateur::where('id_utilisateur', $etudiant->id_utilisateur)->first();
                if ($utilisateur) {
                    $utilisateur->update([
                        'mot_de_passe' => bcrypt($data['mot_de_passe']),
                    ]);
                }
                unset($data['mot_de_passe']);
            }

            $etudiant->update($data);
            return $this->successResponse($etudiant, "Étudiant mis à jour avec succès");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Etudiant $etudiant)
    {
        $this->authorize('delete', $etudiant);
        $etudiant->delete();
        return $this->successResponse(null, "Étudiant supprimé avec succès", 204);
    }

    public function notes(Etudiant $etudiant)
    {
        $this->authorize('view', $etudiant);
        
        try {
            $notes = $etudiant->notes()->with('cours')->get();

            return response()->json([
                'success' => true,
                'message' => 'Notes de l\'étudiant récupérées avec succès',
                'data' => [
                    'etudiant' => [
                        'id' => $etudiant->id_etudiant,
                        'nom' => $etudiant->nom,
                        'prenom' => $etudiant->prenom,
                        'matricule' => $etudiant->matricule,
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

    public function mesEtudiants()
    {
        try {
            $utilisateur = Auth::user();
            
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                ], 403);
            }
            
            $etudiants = Etudiant::orderBy('nom')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Étudiants récupérés avec succès',
                'data' => $etudiants
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
     * 🆕 Récupérer les étudiants FILTRÉS par filière et niveau d'un cours
     * Permet à l'enseignant de voir UNIQUEMENT les étudiants du cours sélectionné
     */
    public function getEtudiantsParCours($id_cours)
    {
        try {
            $utilisateur = Auth::user();
            
            // Vérifier que c'est bien un enseignant
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                ], 403);
            }
            
            // 🔥 RÉCUPÉRER LE COURS (vérifier que l'enseignant en est propriétaire)
            $cours = \App\Models\Cours::where('id_cours', $id_cours)
                                    ->where('id_enseignant', $enseignant->id_enseignant)
                                    ->first();
            
            if (!$cours) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce cours ne vous appartient pas ou n\'existe pas.'
                ], 403);
            }
            
            // 🎯 FILTRER LES ÉTUDIANTS : MÊME FILIÈRE + MÊME NIVEAU QUE LE COURS
            $etudiants = Etudiant::where('filiere', $cours->filiere)
                                ->where('niveau', $cours->niveau)
                                ->orderBy('nom')
                                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Étudiants filtrés récupérés avec succès',
                'data' => [
                    'cours' => [
                        'id' => $cours->id_cours,
                        'code' => $cours->code,
                        'titre' => $cours->titre,
                        'filiere' => $cours->filiere,
                        'niveau' => $cours->niveau
                    ],
                    'etudiants' => $etudiants,
                    'count' => $etudiants->count()
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des étudiants.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}