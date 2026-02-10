<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;
use Illuminate\Http\Request;

class EtudiantController extends BaseApiController
{
    // 🔹 Autorisation automatique via Policy
    public function __construct()
    {
        $this->authorizeResource(Etudiant::class, 'etudiant');
    }

    public function index()
    {
        $etudiants = Etudiant::paginate(10);
        return $this->successResponse($etudiants, "Liste des étudiants récupérée avec succès");
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'email' => 'required|email|unique:etudiants,email',
                'date_naissance' => 'required|date',
                'filiere' => 'required|string|max:255',
                'statut' => 'nullable|in:actif,suspendu,diplome'
            ]);

            $etudiant = Etudiant::create($data);
            return $this->successResponse($etudiant, "Étudiant créé avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function show(Etudiant $etudiant)
    {
        return $this->successResponse($etudiant->load('notes'), "Étudiant récupéré avec succès");
    }

    public function update(Request $request, Etudiant $etudiant)
    {
        try {
            $data = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:etudiants,email,' . $etudiant->id_etudiant . ',id_etudiant',
                'date_naissance' => 'sometimes|date',
                'filiere' => 'sometimes|string|max:255',
                'statut' => 'sometimes|in:actif,suspendu,diplome'
            ]);

            $etudiant->update($data);
            return $this->successResponse($etudiant, "Étudiant mis à jour avec succès");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function destroy(Etudiant $etudiant)
    {
        $etudiant->delete();
        return $this->successResponse(null, "Étudiant supprimé avec succès", 204);
    }

    /**
     * Récupérer toutes les notes d'un étudiant
     */
    public function notes(Etudiant $etudiant)
    {
        // 🔹 Autorisation via Policy
        $this->authorize('view', $etudiant);
        
        try {
            // Charger les notes avec les informations du cours
            $notes = $etudiant->notes()->with('cours')->get();

            return response()->json([
                'success' => true,
                'message' => 'Notes de l\'étudiant récupérées avec succès',
                'data' => [
                    'etudiant' => [
                        'id' => $etudiant->id_etudiant,
                        'nom' => $etudiant->nom,
                        'prenom' => $etudiant->prenom,
                        'matricule' => $etudiant->matricule
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
}