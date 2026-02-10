<?php

namespace App\Http\Controllers\Api;

use App\Models\Enseignant;
use Illuminate\Http\Request;

class EnseignantController extends BaseApiController
{
    // 🔹 Autorisation automatique via Policy
    public function __construct()
    {
        $this->authorizeResource(Enseignant::class, 'enseignant');
    }

    public function index()
    {
        $enseignants = Enseignant::paginate(10);
        return $this->successResponse($enseignants, "Liste des enseignants récupérée avec succès");
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|email|unique:enseignants,email',
                'date_naissance' => 'required|date',
                'specialite' => 'required|string|max:255',
                'statut' => 'nullable|in:actif,retraite,suspendu'
            ]);

            $enseignant = Enseignant::create($data);
            return $this->successResponse($enseignant, "Enseignant créé avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function show(Enseignant $enseignant)
    {
        return $this->successResponse($enseignant->load('cours'), "Enseignant récupéré avec succès");
    }

    public function update(Request $request, Enseignant $enseignant)
    {
        try {
            $data = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:enseignants,email,' . $enseignant->id_enseignant . ',id_enseignant',
                'date_naissance' => 'sometimes|date',
                'specialite' => 'sometimes|string|max:255',
                'statut' => 'sometimes|in:actif,retraite,suspendu'
            ]);

            $enseignant->update($data);
            return $this->successResponse($enseignant, "Enseignant mis à jour avec succès");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function destroy(Enseignant $enseignant)
    {
        $enseignant->delete();
        return $this->successResponse(null, "Enseignant supprimé avec succès", 204);
    }

    /**
     * Récupérer tous les cours d'un enseignant
     */
    public function cours(Enseignant $enseignant)
    {
        // 🔹 Autorisation via Policy
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
}