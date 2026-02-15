<?php

namespace App\Http\Controllers\Api;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends BaseApiController
{
    public function index()
    {
        // ✅ Autorisation
        $this->authorize('viewAny', Note::class);
        
        $notes = Note::with(['etudiant', 'cours'])->paginate(10);
        return $this->successResponse($notes, "Liste des notes récupérée avec succès");
    }

    public function store(Request $request)
    {
        // ✅ Autorisation
        $this->authorize('create', Note::class);
        
        try {
            $data = $request->validate([
                'id_etudiant' => 'required|exists:etudiants,id_etudiant',
                'id_cours' => 'required|exists:cours,id_cours',
                'valeur' => 'required|numeric|min:0|max:20',
                'date_attribution' => 'nullable|date'  // ✅
            ]);

            $note = Note::create($data);
            return $this->successResponse($note, "Note attribuée avec succès", 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors());
        }
    }

    public function show(Note $note)
    {
        // ✅ Autorisation
        $this->authorize('view', $note);
        
        return $this->successResponse($note->load(['etudiant', 'cours']), "Note récupérée avec succès");
    }

    public function update(Request $request, Note $note)
    {
        // ✅ Autorisation
        $this->authorize('update', $note);
        
        try {
            $data = $request->validate([
                'valeur' => 'sometimes|numeric|min:0|max:20',
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
        // ✅ Autorisation
        $this->authorize('delete', $note);
        
        $note->delete();
        return $this->successResponse(null, "Note supprimée avec succès", 204);
    }
    /**
 * Mes notes (notes de l'étudiant connecté)
 */
public function mesNotes()
{
    try {
        $utilisateur = auth()->user();
        
        // Vérifier que c'est bien un étudiant
        $etudiant = \App\Models\Etudiant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
        
        if (!$etudiant) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas enregistré comme étudiant.'
            ], 403);
        }
        
        // ✅ SIMPLIFIÉ - Récupérer notes avec seulement 'cours' (pas 'cours.enseignant')
        $notes = \App\Models\Note::where('id_etudiant', $etudiant->id_etudiant)
                                ->with('cours') // ✅ UNE SEULE RELATION
                                ->orderBy( 'date_attribution', 'desc')
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
}