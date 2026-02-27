<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizTentative;
use App\Mail\QuizPublie;
use App\Models\Etudiant;
use Illuminate\Support\Facades\Mail;

class QuizController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // LISTE DES QUIZ
    // GET /api/quiz
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        $query = Quiz::withCount('questions')
            ->with('createur:id_utilisateur,nom,prenom');

        // Filtres
        if ($request->filled('filiere')) {
            $query->where('filiere', $request->filiere);
        }
        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        // Étudiant → seulement les quiz publiés
        if ($role === 'etudiant') {
            $query->where('est_publie', true);

            $quiz = $query->orderByDesc('created_at')->get();

            // Ajouter info tentative pour chaque quiz
            $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)->first();
            if ($etudiant) {
                $quiz = $quiz->map(function ($q) use ($etudiant) {
                    $tentative = QuizTentative::where('id_quiz', $q->id_quiz)
                        ->where('id_etudiant', $etudiant->id_etudiant)
                        ->latest('created_at')
                        ->first();
                    $q->ma_tentative  = $tentative;
                    $q->deja_passe    = $tentative !== null;
                    return $q;
                });
            }

            return response()->json(['success' => true, 'data' => $quiz]);
        }

        // Admin/Enseignant → tous leurs quiz
        if ($role === 'enseignant') {
            $query->where('id_createur', $user->id_utilisateur);
        }

        $quiz = $query->orderByDesc('created_at')->get();

        return response()->json(['success' => true, 'data' => $quiz]);
    }

    // ─────────────────────────────────────────────────────────────
    // DÉTAIL D'UN QUIZ (avec questions)
    // GET /api/quiz/{id}
    // ─────────────────────────────────────────────────────────────
    public function show($id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        $quiz = Quiz::with(['questions', 'createur:id_utilisateur,nom,prenom'])
            ->withCount('questions')
            ->findOrFail($id);

        // Étudiant → quiz doit être publié
        if ($role === 'etudiant' && !$quiz->est_publie) {
            return response()->json(['success' => false, 'message' => 'Quiz non disponible'], 403);
        }

        // Pour étudiant → cacher les réponses correctes
        if ($role === 'etudiant') {
            $quiz->questions->each(function ($q) {
                unset($q->reponse_correcte);
            });
        }

        return response()->json(['success' => true, 'data' => $quiz]);
    }

    // ─────────────────────────────────────────────────────────────
    // CRÉER UN QUIZ
    // POST /api/quiz
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if (!in_array($role, ['admin', 'enseignant'])) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'titre'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'filiere'        => 'nullable|string|max:100',
            'niveau'         => 'nullable|string|max:50',
            'duree_minutes'  => 'nullable|integer|min:1|max:180',
            'note_passage'   => 'nullable|numeric|min:0|max:20',
            'est_publie'     => 'nullable|boolean',
            'questions'      => 'nullable|array',
            'questions.*.question'        => 'required|string',
            'questions.*.type'            => 'required|in:qcm,vrai_faux,libre',
            'questions.*.options'         => 'nullable|array',
            'questions.*.reponse_correcte' => 'required|string',
            'questions.*.points'          => 'nullable|integer|min:1',
            'questions.*.ordre'           => 'nullable|integer',
        ]);

        $quiz = Quiz::create([
            'titre'         => $request->titre,
            'description'   => $request->description,
            'filiere'       => $request->filiere,
            'niveau'        => $request->niveau,
            'duree_minutes' => $request->duree_minutes ?? 30,
            'note_passage'  => $request->note_passage ?? 10,
            'est_publie'    => $request->est_publie ?? false,
            'id_createur'   => $user->id_utilisateur,
        ]);

        // Créer les questions si fournies
        if ($request->filled('questions')) {
            foreach ($request->questions as $index => $q) {
                QuizQuestion::create([
                    'id_quiz'          => $quiz->id_quiz,
                    'question'         => $q['question'],
                    'type'             => $q['type'],
                    'options'          => $q['options'] ?? null,
                    'reponse_correcte' => $q['reponse_correcte'],
                    'points'           => $q['points'] ?? 1,
                    'ordre'            => $q['ordre'] ?? $index,
                ]);
            }
        }

        $quiz->load('questions');
        $quiz->loadCount('questions');

        return response()->json(['success' => true, 'data' => $quiz, 'message' => 'Quiz créé avec succès'], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // MODIFIER UN QUIZ
    // PUT /api/quiz/{id}
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        $quiz = Quiz::findOrFail($id);

        // Seul le créateur ou admin peut modifier
        if ($role === 'enseignant' && $quiz->id_createur !== $user->id_utilisateur) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'titre'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'filiere'       => 'nullable|string|max:100',
            'niveau'        => 'nullable|string|max:50',
            'duree_minutes' => 'nullable|integer|min:1|max:180',
            'note_passage'  => 'nullable|numeric|min:0|max:20',
            'est_publie'    => 'nullable|boolean',
        ]);

        $quiz->update($request->only([
            'titre',
            'description',
            'filiere',
            'niveau',
            'duree_minutes',
            'note_passage',
            'est_publie',
        ]));

        $quiz->load('questions');
        $quiz->loadCount('questions');

        return response()->json(['success' => true, 'data' => $quiz, 'message' => 'Quiz mis à jour']);
    }

    // ─────────────────────────────────────────────────────────────
    // SUPPRIMER UN QUIZ
    // DELETE /api/quiz/{id}
    // ─────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        $quiz = Quiz::findOrFail($id);

        if ($role === 'enseignant' && $quiz->id_createur !== $user->id_utilisateur) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $quiz->delete();

        return response()->json(['success' => true, 'message' => 'Quiz supprimé']);
    }

    // ─────────────────────────────────────────────────────────────
    // GESTION DES QUESTIONS
    // POST /api/quiz/{id}/questions
    // ─────────────────────────────────────────────────────────────
    public function storeQuestion(Request $request, $id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();

        $quiz = Quiz::findOrFail($id);

        $request->validate([
            'question'         => 'required|string',
            'type'             => 'required|in:qcm,vrai_faux,libre',
            'options'          => 'nullable|array',
            'reponse_correcte' => 'required|string',
            'points'           => 'nullable|integer|min:1',
            'ordre'            => 'nullable|integer',
        ]);

        $question = QuizQuestion::create([
            'id_quiz'          => $quiz->id_quiz,
            'question'         => $request->question,
            'type'             => $request->type,
            'options'          => $request->options,
            'reponse_correcte' => $request->reponse_correcte,
            'points'           => $request->points ?? 1,
            'ordre'            => $request->ordre ?? $quiz->questions()->count(),
        ]);

        return response()->json(['success' => true, 'data' => $question, 'message' => 'Question ajoutée'], 201);
    }

    // PUT /api/quiz/questions/{idQuestion}
    public function updateQuestion(Request $request, $idQuestion)
    {
        $question = QuizQuestion::findOrFail($idQuestion);

        $request->validate([
            'question'         => 'sometimes|string',
            'type'             => 'sometimes|in:qcm,vrai_faux,libre',
            'options'          => 'nullable|array',
            'reponse_correcte' => 'sometimes|string',
            'points'           => 'nullable|integer|min:1',
            'ordre'            => 'nullable|integer',
        ]);

        $question->update($request->only([
            'question',
            'type',
            'options',
            'reponse_correcte',
            'points',
            'ordre',
        ]));

        return response()->json(['success' => true, 'data' => $question, 'message' => 'Question mise à jour']);
    }

    // DELETE /api/quiz/questions/{idQuestion}
    public function destroyQuestion($idQuestion)
    {
        $question = QuizQuestion::findOrFail($idQuestion);
        $question->delete();

        return response()->json(['success' => true, 'message' => 'Question supprimée']);
    }

    // ─────────────────────────────────────────────────────────────
    // SOUMETTRE UN QUIZ (étudiant)
    // POST /api/quiz/{id}/soumettre
    // ─────────────────────────────────────────────────────────────
    public function soumettre(Request $request, $id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();

        $quiz = Quiz::with('questions')->findOrFail($id);

        if (!$quiz->est_publie) {
            return response()->json(['success' => false, 'message' => 'Quiz non disponible'], 403);
        }

        $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)->first();
        if (!$etudiant) {
            return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
        }

        $request->validate([
            'reponses'   => 'required|array',
            'temps_pris' => 'nullable|integer',
        ]);

        // ── Correction automatique ──────────────────────────────
        $reponses       = $request->reponses;
        $scoreTotal     = 0;
        $pointsMax      = 0;
        $detail         = [];

        foreach ($quiz->questions as $question) {
            $pointsMax += $question->points;
            $reponseEtudiant = $reponses[$question->id_question] ?? null;
            $correct = false;

            if ($question->type === 'libre') {
                // Réponse libre → toujours compter comme correct (correction manuelle)
                $correct = true;
            } else {
                $correct = strtolower(trim((string)$reponseEtudiant))
                    === strtolower(trim((string)$question->reponse_correcte));
            }

            if ($correct) $scoreTotal += $question->points;

            $detail[$question->id_question] = [
                'reponse_etudiant' => $reponseEtudiant,
                'reponse_correcte' => $question->reponse_correcte,
                'correct'          => $correct,
                'points_obtenus'   => $correct ? $question->points : 0,
                'points_max'       => $question->points,
            ];
        }

        // Calcul note sur 20
        $noteSur20  = $pointsMax > 0
            ? round(($scoreTotal / $pointsMax) * 20, 2)
            : 0;
        $estReussi  = $noteSur20 >= $quiz->note_passage;

        $tentative = QuizTentative::create([
            'id_quiz'     => $quiz->id_quiz,
            'id_etudiant' => $etudiant->id_etudiant,
            'reponses'    => $detail,
            'score'       => $scoreTotal,
            'note_sur_20' => $noteSur20,
            'est_reussi'  => $estReussi,
            'temps_pris'  => $request->temps_pris ?? 0,
            'created_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'tentative'   => $tentative,
                'note_sur_20' => $noteSur20,
                'score'       => $scoreTotal,
                'points_max'  => $pointsMax,
                'est_reussi'  => $estReussi,
                'detail'      => $detail,
                'quiz'        => [
                    'titre'        => $quiz->titre,
                    'note_passage' => $quiz->note_passage,
                ],
            ],
            'message' => 'Quiz soumis avec succès',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // STATISTIQUES D'UN QUIZ (admin/enseignant)
    // GET /api/quiz/{id}/stats
    // ─────────────────────────────────────────────────────────────
    public function stats($id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        if (!in_array($role, ['admin', 'enseignant'])) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $quiz = Quiz::with('questions')->findOrFail($id);

        $tentatives = QuizTentative::where('id_quiz', $id)
            ->with('etudiant:id_etudiant,nom,prenom,matricule')
            ->orderByDesc('created_at')
            ->get();

        $nbTentatives = $tentatives->count();
        $nbReussis    = $tentatives->where('est_reussi', true)->count();
        $moyenneNote  = $nbTentatives > 0
            ? round($tentatives->avg('note_sur_20'), 2)
            : null;
        $tauxReussite = $nbTentatives > 0
            ? round(($nbReussis / $nbTentatives) * 100)
            : 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'quiz'          => $quiz,
                'tentatives'    => $tentatives,
                'nb_tentatives' => $nbTentatives,
                'nb_reussis'    => $nbReussis,
                'moyenne_note'  => $moyenneNote,
                'taux_reussite' => $tauxReussite,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // MES TENTATIVES (étudiant)
    // GET /api/quiz/{id}/mes-tentatives
    // ─────────────────────────────────────────────────────────────
    public function mesTentatives($id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();

        $etudiant = Etudiant::where('id_utilisateur', $user->id_utilisateur)->first();
        if (!$etudiant) {
            return response()->json(['success' => false, 'message' => 'Étudiant non trouvé'], 404);
        }

        $tentatives = QuizTentative::where('id_quiz', $id)
            ->where('id_etudiant', $etudiant->id_etudiant)
            ->with('quiz:id_quiz,titre,note_passage,duree_minutes')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $tentatives]);
    }

    // ─────────────────────────────────────────────────────────────
    // PUBLIER / DÉPUBLIER UN QUIZ
    // POST /api/quiz/{id}/toggle-publie
    // ─────────────────────────────────────────────────────────────
    public function togglePublie($id)
    {
        /** @var \App\Models\Utilisateur $user */
        $user = auth()->user();
        $role = (string) $user->role;

        $quiz = Quiz::findOrFail($id);

        if ($role === 'enseignant' && $quiz->id_createur !== $user->id_utilisateur) {
            return response()->json(['success' => false, 'message' => 'Accès refusé'], 403);
        }

        $quiz->update(['est_publie' => !$quiz->est_publie]);

        return response()->json([
            'success' => true,
            'data'    => ['est_publie' => $quiz->est_publie],
            'message' => $quiz->est_publie ? 'Quiz publié' : 'Quiz dépublié',
        ]);
        if ($quiz->est_publie) {
            try {
                // Récupérer les étudiants de la filière du quiz
                $etudiants = Etudiant::with('utilisateur')
                    ->whereHas('utilisateur')
                    ->get();

                foreach ($etudiants as $etudiant) {
                    if (!$etudiant->utilisateur) continue;
                    // Filtrer par filière si définie
                    if ($quiz->filiere && $etudiant->filiere !== $quiz->filiere) continue;

                    Mail::to($etudiant->utilisateur->email)->send(new QuizPublie(
                        nomEtudiant: $etudiant->prenom . ' ' . $etudiant->nom,
                        titreQuiz: $quiz->titre,
                        filiere: $quiz->filiere ?? 'Toutes filières',
                        dureeMinutes: $quiz->duree_minutes,
                    ));
                }
            } catch (\Exception $e) {
                \Log::warning('Email quiz non envoyé : ' . $e->getMessage());
            }
        }
    }
}