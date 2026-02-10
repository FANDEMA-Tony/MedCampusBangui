<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CoursController extends Controller
{
    /**
     * Liste de tous les cours
     */
    public function index()
    {
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
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:cours,code|max:50',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'id_enseignant' => 'required|exists:enseignants,id_enseignant'
        ], [
            'code.required' => 'Le code du cours est obligatoire.',
            'code.unique' => 'Ce code de cours existe déjà.',
            'code.max' => 'Le code ne doit pas dépasser 50 caractères.',
            'titre.required' => 'Le titre du cours est obligatoire.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'id_enseignant.required' => 'L\'enseignant responsable est obligatoire.',
            'id_enseignant.exists' => 'Cet enseignant n\'existe pas.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cours = Cours::create([
                'code' => $request->code,
                'titre' => $request->titre,
                'description' => $request->description,
                'id_enseignant' => $request->id_enseignant
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cours créé avec succès',
                'data' => $cours
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du cours.'
            ], 500);
        }
    }

    /**
     * Afficher un cours spécifique
     */
    public function show(Cours $cour)
    {
        // Note: Laravel utilise "cour" au singulier dans la route
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
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:cours,code,' . $cour->id_cours . ',id_cours|max:50',
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'id_enseignant' => 'sometimes|exists:enseignants,id_enseignant'
        ], [
            'code.unique' => 'Ce code de cours existe déjà.',
            'code.max' => 'Le code ne doit pas dépasser 50 caractères.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'id_enseignant.exists' => 'Cet enseignant n\'existe pas.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cour->update($request->only(['code', 'titre', 'description', 'id_enseignant']));

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
}