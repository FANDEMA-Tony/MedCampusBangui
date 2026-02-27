<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvenementCalendrier;
use App\Models\EmploiDuTemps;
use App\Models\Examen;
use App\Models\Cours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CalendrierController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    //  ÉVÉNEMENTS CALENDRIER
    // ═══════════════════════════════════════════════════════════

    /**
     * Lister tous les événements (admin)
     * GET /api/calendrier/evenements
     */
    public function indexEvenements(Request $request)
    {
        try {
            $query = EvenementCalendrier::with('createur:id_utilisateur,nom,prenom');

            // Filtres optionnels
            if ($request->has('mois') && $request->has('annee')) {
                $query->duMois((int) $request->annee, (int) $request->mois);
            }
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            if ($request->has('filiere')) {
                $query->where('filiere', $request->filiere);
            }

            $evenements = $query->orderBy('date_debut')->get();

            return response()->json([
                'success' => true,
                'data'    => $evenements,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Événements visibles par un étudiant
     * GET /api/calendrier/evenements/etudiant
     */
    public function evenementsEtudiant(Request $request)
    {
        try {
            $user = Auth::user();

            $query = EvenementCalendrier::with('createur:id_utilisateur,nom,prenom')
                ->visiblePour($user->filiere ?? '', $user->niveau ?? '');

            if ($request->has('mois') && $request->has('annee')) {
                $query->duMois((int) $request->annee, (int) $request->mois);
            }

            $evenements = $query->orderBy('date_debut')->get();

            return response()->json(['success' => true, 'data' => $evenements]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Créer un événement (admin + enseignant)
     * POST /api/calendrier/evenements
     */
    public function storeEvenement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:cours,examen,evenement,conge,reunion',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'lieu'        => 'nullable|string|max:255',
            'couleur'     => 'nullable|string|max:7',
            'visibilite'  => 'required|in:tous,filiere,niveau',
            'filiere'     => 'nullable|string',
            'niveau'      => 'nullable|string',
            'est_important' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();

            $evenement = EvenementCalendrier::create([
                ...$request->only([
                    'titre', 'description', 'type', 'date_debut', 'date_fin',
                    'lieu', 'couleur', 'visibilite', 'filiere', 'niveau', 'est_important',
                ]),
                'id_createur'    => $user->id_utilisateur,
                'role_createur'  => $user->role,
                'couleur'        => $request->couleur ?? '#0066CC',
                'est_important'  => $request->est_important ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès !',
                'data'    => $evenement->load('createur:id_utilisateur,nom,prenom'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Modifier un événement
     * PUT /api/calendrier/evenements/{id}
     */
    public function updateEvenement(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'titre'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'required|in:cours,examen,evenement,conge,reunion',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'lieu'        => 'nullable|string|max:255',
            'couleur'     => 'nullable|string|max:7',
            'visibilite'  => 'required|in:tous,filiere,niveau',
            'filiere'     => 'nullable|string',
            'niveau'      => 'nullable|string',
            'est_important' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $evenement = EvenementCalendrier::findOrFail($id);
            $evenement->update($request->only([
                'titre', 'description', 'type', 'date_debut', 'date_fin',
                'lieu', 'couleur', 'visibilite', 'filiere', 'niveau', 'est_important',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Événement modifié avec succès !',
                'data'    => $evenement->load('createur:id_utilisateur,nom,prenom'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un événement
     * DELETE /api/calendrier/evenements/{id}
     */
    public function destroyEvenement(int $id)
    {
        try {
            $evenement = EvenementCalendrier::findOrFail($id);
            $evenement->delete();

            return response()->json(['success' => true, 'message' => 'Événement supprimé avec succès !']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EMPLOI DU TEMPS
    // ═══════════════════════════════════════════════════════════

    /**
     * Lister l'emploi du temps (filtré par filière/niveau)
     * GET /api/calendrier/emploi-du-temps
     */
    public function indexEmploi(Request $request)
    {
        try {
            $query = EmploiDuTemps::with([
                'cours:id_cours,code,titre,id_enseignant',
                'cours.enseignant:id_enseignant,nom,prenom',
            ])->actif();

            if ($request->has('filiere') && $request->has('niveau')) {
                $query->pourFiliere($request->filiere, $request->niveau);
            }
            if ($request->has('semestre')) {
                $query->where('semestre', $request->semestre);
            }

            $emplois = $query->orderByRaw("FIELD(jour_semaine, 'lundi','mardi','mercredi','jeudi','vendredi','samedi')")
                             ->orderBy('heure_debut')
                             ->get();

            // Grouper par jour
            $grouped = $emplois->groupBy('jour_semaine');

            return response()->json(['success' => true, 'data' => $grouped]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Créer un créneau d'emploi du temps
     * POST /api/calendrier/emploi-du-temps
     */
    public function storeEmploi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cours'    => 'required|exists:cours,id_cours',
            'jour_semaine'=> 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'salle'       => 'nullable|string|max:100',
            'filiere'     => 'required|string',
            'niveau'      => 'required|string',
            'semestre'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $emploi = EmploiDuTemps::create($request->only([
                'id_cours', 'jour_semaine', 'heure_debut', 'heure_fin',
                'salle', 'filiere', 'niveau', 'semestre',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Créneau ajouté avec succès !',
                'data'    => $emploi->load('cours', 'cours.enseignant'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Modifier un créneau
     * PUT /api/calendrier/emploi-du-temps/{id}
     */
    public function updateEmploi(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'id_cours'    => 'required|exists:cours,id_cours',
            'jour_semaine'=> 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'salle'       => 'nullable|string|max:100',
            'filiere'     => 'required|string',
            'niveau'      => 'required|string',
            'semestre'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $emploi = EmploiDuTemps::findOrFail($id);
            $emploi->update($request->only([
                'id_cours', 'jour_semaine', 'heure_debut', 'heure_fin',
                'salle', 'filiere', 'niveau', 'semestre',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Créneau modifié avec succès !',
                'data'    => $emploi->load('cours', 'cours.enseignant'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un créneau
     * DELETE /api/calendrier/emploi-du-temps/{id}
     */
    public function destroyEmploi(int $id)
    {
        try {
            EmploiDuTemps::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Créneau supprimé avec succès !']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EXAMENS
    // ═══════════════════════════════════════════════════════════

    /**
     * Lister les examens
     * GET /api/calendrier/examens
     */
    public function indexExamens(Request $request)
    {
        try {
            $query = Examen::with('cours:id_cours,code,titre');

            if ($request->has('filiere') && $request->has('niveau')) {
                $query->pourFiliere($request->filiere, $request->niveau);
            }
            if ($request->has('semestre')) {
                $query->where('semestre', $request->semestre);
            }
            if ($request->boolean('a_venir')) {
                $query->aVenir();
            }

            $examens = $query->orderBy('date')->orderBy('heure_debut')->get();

            return response()->json(['success' => true, 'data' => $examens]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Créer un examen
     * POST /api/calendrier/examens
     */
    public function storeExamen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cours'      => 'required|exists:cours,id_cours',
            'titre'         => 'required|string|max:255',
            'date'          => 'required|date',
            'heure_debut'   => 'required|date_format:H:i',
            'heure_fin'     => 'required|date_format:H:i|after:heure_debut',
            'salle'         => 'nullable|string|max:100',
            'duree_minutes' => 'required|integer|min:15',
            'type_session'  => 'required|in:normale,rattrapage',
            'filiere'       => 'required|string',
            'niveau'        => 'required|string',
            'semestre'      => 'required|string',
            'instructions'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $examen = Examen::create($request->only([
                'id_cours', 'titre', 'date', 'heure_debut', 'heure_fin',
                'salle', 'duree_minutes', 'type_session', 'filiere', 'niveau',
                'semestre', 'instructions',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Examen créé avec succès !',
                'data'    => $examen->load('cours'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Modifier un examen
     * PUT /api/calendrier/examens/{id}
     */
    public function updateExamen(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'id_cours'      => 'required|exists:cours,id_cours',
            'titre'         => 'required|string|max:255',
            'date'          => 'required|date',
            'heure_debut'   => 'required|date_format:H:i',
            'heure_fin'     => 'required|date_format:H:i|after:heure_debut',
            'salle'         => 'nullable|string|max:100',
            'duree_minutes' => 'required|integer|min:15',
            'type_session'  => 'required|in:normale,rattrapage',
            'filiere'       => 'required|string',
            'niveau'        => 'required|string',
            'semestre'      => 'required|string',
            'instructions'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $examen = Examen::findOrFail($id);
            $examen->update($request->only([
                'id_cours', 'titre', 'date', 'heure_debut', 'heure_fin',
                'salle', 'duree_minutes', 'type_session', 'filiere', 'niveau',
                'semestre', 'instructions',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Examen modifié avec succès !',
                'data'    => $examen->load('cours'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un examen
     * DELETE /api/calendrier/examens/{id}
     */
    public function destroyExamen(int $id)
    {
        try {
            Examen::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Examen supprimé avec succès !']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  DONNÉES COMBINÉES (pour le frontend calendrier)
    // ═══════════════════════════════════════════════════════════

    /**
     * Toutes les données calendrier d'un mois (événements + examens)
     * GET /api/calendrier/mois?annee=2024&mois=3&filiere=Médecine&niveau=L1
     */
    public function donneesCalendrier(Request $request)
    {
        try {
            $annee  = (int) ($request->annee ?? now()->year);
            $mois   = (int) ($request->mois  ?? now()->month);
            $filiere = $request->filiere ?? null;
            $niveau  = $request->niveau  ?? null;

            // Événements du mois
            $evQuery = EvenementCalendrier::with('createur:id_utilisateur,nom,prenom')
                ->duMois($annee, $mois);

            if ($filiere && $niveau) {
                $evQuery->visiblePour($filiere, $niveau);
            }

            $evenements = $evQuery->orderBy('date_debut')->get();

            // Examens du mois
            $exQuery = Examen::with('cours:id_cours,code,titre')
                ->whereYear('date', $annee)
                ->whereMonth('date', $mois);

            if ($filiere && $niveau) {
                $exQuery->pourFiliere($filiere, $niveau);
            }

            $examens = $exQuery->orderBy('date')->orderBy('heure_debut')->get();

            // Formater pour FullCalendar / React Big Calendar
            $events = collect();

            foreach ($evenements as $ev) {
                $events->push([
                    'id'          => 'ev_' . $ev->id_evenement,
                    'title'       => $ev->titre,
                    'start'       => $ev->date_debut->format('Y-m-d\TH:i:s'),
                    'end'         => $ev->date_fin->format('Y-m-d\TH:i:s'),
                    'color'       => $ev->couleur,
                    'type'        => $ev->type,
                    'description' => $ev->description,
                    'lieu'        => $ev->lieu,
                    'est_important' => $ev->est_important,
                    'source'      => 'evenement',
                    'raw'         => $ev,
                ]);
            }

            foreach ($examens as $ex) {
                $events->push([
                    'id'            => 'ex_' . $ex->id_examen,
                    'title'         => '📝 ' . $ex->titre,
                    'start'         => $ex->date->format('Y-m-d') . 'T' . $ex->heure_debut,
                    'end'           => $ex->date->format('Y-m-d') . 'T' . $ex->heure_fin,
                    'color'         => $ex->type_session === 'rattrapage' ? '#F97316' : '#DC143C',
                    'type'          => 'examen',
                    'salle'         => $ex->salle,
                    'duree_minutes' => $ex->duree_minutes,
                    'type_session'  => $ex->type_session,
                    'instructions'  => $ex->instructions,
                    'cours'         => $ex->cours,
                    'source'        => 'examen',
                    'raw'           => $ex,
                ]);
            }

            return response()->json([
                'success'    => true,
                'data'       => [
                    'events'    => $events->sortBy('start')->values(),
                    'evenements'=> $evenements,
                    'examens'   => $examens,
                    'mois'      => $mois,
                    'annee'     => $annee,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
