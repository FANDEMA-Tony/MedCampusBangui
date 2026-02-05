<?php

namespace App\Http\Controllers\Api;

use App\Models\Cours;
use Illuminate\Http\Request;

class CoursController extends BaseApiController
{
    public function index()
    {
        $cours = Cours::with('enseignant')->paginate(10);
        return $this->successResponse($cours, "Liste des cours récupérée avec succès");
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string|unique:cours,code',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string',
                'id_enseignant' => 'required|exists:enseignants,id_enseignant'
            ]);

            $cours = Cours::create($data);
            return $this->successResponse($cours, "Cours créé avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function show(Cours $cours)
    {
        return $this->successResponse($cours->load(['enseignant', 'notes']), "Cours récupéré avec succès");
    }

    public function update(Request $request, Cours $cours)
    {
        try {
            $data = $request->validate([
                'code' => 'sometimes|string|unique:cours,code,' . $cours->id_cours . ',id_cours',
                'titre' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'id_enseignant' => 'sometimes|exists:enseignants,id_enseignant'
            ]);

            $cours->update($data);
            return $this->successResponse($cours, "Cours mis à jour avec succès");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function destroy(Cours $cours)
    {
        $cours->delete();
        return $this->successResponse(null, "Cours supprimé avec succès", 204);
    }
}
