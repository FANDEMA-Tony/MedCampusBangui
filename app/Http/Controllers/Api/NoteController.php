<?php

namespace App\Http\Controllers\Api;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends BaseApiController
{
    public function index()
    {
        $notes = Note::with(['etudiant', 'cours'])->paginate(10);
        return $this->successResponse($notes, "Liste des notes récupérée avec succès");
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'id_etudiant' => 'required|exists:etudiants,id_etudiant',
                'id_cours' => 'required|exists:cours,id_cours',
                'valeur' => 'required|numeric|min:0|max:100',
                'date_attribution' => 'nullable|date'
            ]);

            $note = Note::create($data);
            return $this->successResponse($note, "Note attribuée avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function show(Note $note)
    {
        return $this->successResponse($note->load(['etudiant', 'cours']), "Note récupérée avec succès");
    }

    public function update(Request $request, Note $note)
    {
        try {
            $data = $request->validate([
                'valeur' => 'sometimes|numeric|min:0|max:100',
                'date_attribution' => 'nullable|date'
            ]);

            $note->update($data);
            return $this->successResponse($note, "Note mise à jour avec succès");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function destroy(Note $note)
    {
        $note->delete();
        return $this->successResponse(null, "Note supprimée avec succès", 204);
    }
}
