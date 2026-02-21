<?php

namespace App\Http\Controllers\Api;

use App\Models\Enseignant;
use Illuminate\Http\Request;

class EnseignantController extends BaseApiController
{
    public function index()
    {
        // ✅ Autorisation
        $this->authorize('viewAny', Enseignant::class);
        
        $enseignants = Enseignant::paginate(10);
        return $this->successResponse($enseignants, "Liste des enseignants récupérée avec succès");
    }

    public function store(Request $request)
    {
        // ✅ Autorisation
        $this->authorize('create', Enseignant::class);
        
        try {
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|email|unique:enseignants,email',
                'mot_de_passe' => 'required|string|min:8', // 🆕 AJOUTÉ
                'date_naissance' => 'required|date',
                'specialite' => 'nullable|string|max:255', // 🆕 MODIFIÉ (nullable au lieu de required)
                'statut' => 'nullable|in:actif,retraite,suspendu'
            ]);

            // 🆕 1. Créer l'utilisateur d'abord (pour la connexion)
            $utilisateur = \App\Models\Utilisateur::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'mot_de_passe' => bcrypt($data['mot_de_passe']),
                'role' => 'enseignant',
            ]);

            // 🆕 2. Créer l'enseignant lié
            $enseignant = Enseignant::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'matricule' => 'ENS' . str_pad($utilisateur->id_utilisateur, 6, '0', STR_PAD_LEFT),
                'date_naissance' => $data['date_naissance'],
                'specialite' => $data['specialite'] ?? null,
                'statut' => $data['statut'] ?? 'actif',
            ]);

            return $this->successResponse($enseignant, "Enseignant créé avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        } catch (\Exception $e) {
            // 🆕 Gestion d'erreurs améliorée
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Enseignant $enseignant)
    {
        // ✅ Autorisation
        $this->authorize('view', $enseignant);
        
        return $this->successResponse($enseignant->load('cours'), "Enseignant récupéré avec succès");
    }

    public function update(Request $request, Enseignant $enseignant)
    {
        // ✅ Autorisation
        $this->authorize('update', $enseignant);
        
        try {
            $data = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:enseignants,email,' . $enseignant->id_enseignant . ',id_enseignant',
                'mot_de_passe' => 'sometimes|nullable|string|min:8', // 🆕 AJOUTÉ
                'date_naissance' => 'sometimes|date',
                'specialite' => 'sometimes|nullable|string|max:255', // 🆕 MODIFIÉ (nullable)
                'statut' => 'sometimes|in:actif,retraite,suspendu'
            ]);

            // 🆕 Mettre à jour l'utilisateur si mot de passe fourni
            if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
                $utilisateur = \App\Models\Utilisateur::where('id_utilisateur', $enseignant->id_utilisateur)->first();
                if ($utilisateur) {
                    $utilisateur->update([
                        'mot_de_passe' => bcrypt($data['mot_de_passe']),
                    ]);
                }
                unset($data['mot_de_passe']); // Ne pas enregistrer dans la table enseignants
            }

            $enseignant->update($data);
            return $this->successResponse($enseignant, "Enseignant mis à jour avec succès");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        } catch (\Exception $e) {
            // 🆕 Gestion d'erreurs améliorée
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Enseignant $enseignant)
    {
        // ✅ Autorisation
        $this->authorize('delete', $enseignant);
        
        $enseignant->delete();
        return $this->successResponse(null, "Enseignant supprimé avec succès", 204);
    }

    /**
     * Récupérer tous les cours d'un enseignant
     */
    public function cours(Enseignant $enseignant)
    {
        // ✅ Autorisation
        $this->authorize('view', $enseignant);
        
        try {
            // Charger les cours de l'enseignant
            $cours = $enseignant->cours()->get();

            return response()->json([
                'success' => true,
                'message' => 'Cours de l\'enseignant récupérés avec succès',
                'data' => [
                    'enseignant' => [
                        'id' => $enseignant->id_enseignant,
                        'nom' => $enseignant->nom,
                        'prenom' => $enseignant->prenom,
                        'matricule' => $enseignant->matricule,
                        'specialite' => $enseignant->specialite
                    ],
                    'cours' => $cours
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des cours.'
            ], 500);
        }
    }

    /**
     * 🆕 Récupérer la liste des étudiants accessibles à l'enseignant
     */
    public function mesEtudiants()
    {
        try {
            $utilisateur = auth()->user();
            
            // Vérifier que c'est bien un enseignant
            $enseignant = Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                ], 403);
            }
            
            // Retourner tous les étudiants (pour l'instant)
            $etudiants = \App\Models\Etudiant::orderBy('nom')->get();
            
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
     * 🆕 Récupérer enseignants groupés par spécialité
     */
    public function indexGrouped()
    {
        $this->authorize('viewAny', Enseignant::class);
        
        $enseignants = Enseignant::withCount('cours')
                                ->orderBy('specialite')
                                ->orderBy('nom')
                                ->get();
        
        // Grouper par spécialité
        $grouped = $enseignants->groupBy(function($ens) {
            return $ens->specialite ?: 'Non spécifiée';
        })->map(function ($specialiteEns, $specialite) {
            return [
                'specialite' => $specialite,
                'total' => $specialiteEns->count(),
                'enseignants' => $specialiteEns->values()
            ];
        })->sortBy('specialite')->values();
        
        return response()->json([
            'success' => true,
            'message' => 'Enseignants groupés récupérés avec succès',
            'data' => $grouped,
            'total' => $enseignants->count()
        ], 200);
    }
}