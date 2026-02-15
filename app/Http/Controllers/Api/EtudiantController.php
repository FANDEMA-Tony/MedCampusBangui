<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;
use Illuminate\Http\Request;

class EtudiantController extends BaseApiController
{
    public function index()
    {
        // ✅ Autorisation
        $this->authorize('viewAny', Etudiant::class);
        
        $etudiants = Etudiant::paginate(10);
        return $this->successResponse($etudiants, "Liste des étudiants récupérée avec succès");
    }

    public function store(Request $request)
    {
        // ✅ Autorisation
        $this->authorize('create', Etudiant::class);
        
        try {
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|email|unique:etudiants,email',
                'mot_de_passe' => 'required|string|min:8', // 🆕 AJOUTÉ
                'date_naissance' => 'required|date',
                'filiere' => 'nullable|string|max:255',
                'statut' => 'nullable|in:actif,suspendu,diplome'
            ]);

            // 🆕 1. Créer l'utilisateur d'abord (pour la connexion)
            $utilisateur = \App\Models\Utilisateur::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'mot_de_passe' => bcrypt($data['mot_de_passe']),
                'role' => 'etudiant',
            ]);

            // 🆕 2. Créer l'étudiant lié
            $etudiant = Etudiant::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'matricule' => 'ETU' . str_pad($utilisateur->id_utilisateur, 6, '0', STR_PAD_LEFT),
                'date_naissance' => $data['date_naissance'],
                'filiere' => $data['filiere'] ?? null,
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
        // ✅ Autorisation
        $this->authorize('view', $etudiant);
        
        return $this->successResponse($etudiant->load('notes'), "Étudiant récupéré avec succès");
    }

    public function update(Request $request, Etudiant $etudiant)
    {
        // ✅ Autorisation
        $this->authorize('update', $etudiant);
        
        try {
            $data = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:etudiants,email,' . $etudiant->id_etudiant . ',id_etudiant',
                'mot_de_passe' => 'sometimes|nullable|string|min:8', // 🆕 AJOUTÉ
                'date_naissance' => 'sometimes|date',
                'filiere' => 'sometimes|nullable|string|max:255',
                'statut' => 'sometimes|in:actif,suspendu,diplome'
            ]);

            // 🆕 Mettre à jour l'utilisateur si mot de passe fourni
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
        // ✅ Autorisation
        $this->authorize('delete', $etudiant);
        
        $etudiant->delete();
        return $this->successResponse(null, "Étudiant supprimé avec succès", 204);
    }

    /**
     * Récupérer toutes les notes d'un étudiant
     */
    public function notes(Etudiant $etudiant)
    {
        // ✅ Autorisation
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

    /**
     * Liste des étudiants accessibles à l'enseignant connecté
     */
    public function mesEtudiants()
    {
        try {
            $utilisateur = auth()->user();
            
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
}