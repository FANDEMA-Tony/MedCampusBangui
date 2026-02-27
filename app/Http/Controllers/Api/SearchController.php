<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Cours;
use App\Models\Note;
use App\Models\Utilisateur;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Recherche globale multi-entités
     * GET /api/search?q=terme&type=tous
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $type  = $request->get('type', 'tous');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'etudiants'   => [],
                    'enseignants' => [],
                    'cours'       => [],
                    'notes'       => [],
                    'total'       => 0,
                ],
            ]);
        }

        // ── Récupérer le rôle sans passer par auth()->user()->role directement
        /** @var Utilisateur $authUser */
        $authUser = auth()->user();
        $userRole = '';
        if ($authUser !== null) {
            $userRole = (string) $authUser->role;
        }

        $results = [
            'etudiants'   => [],
            'enseignants' => [],
            'cours'       => [],
            'notes'       => [],
        ];

        // ── Recherche Étudiants ───────────────────────────────
        if ($type === 'tous' || $type === 'etudiants') {
            $results['etudiants'] = Etudiant::where('nom',       'LIKE', "%{$query}%")
                ->orWhere('prenom',    'LIKE', "%{$query}%")
                ->orWhere('matricule', 'LIKE', "%{$query}%")
                ->orWhere('email',     'LIKE', "%{$query}%")
                ->orWhere('filiere',   'LIKE', "%{$query}%")
                ->orWhere('niveau',    'LIKE', "%{$query}%")
                ->limit(10)
                ->get(['id_etudiant', 'nom', 'prenom', 'matricule', 'email', 'filiere', 'niveau'])
                ->map(fn($e) => [
                    'id'         => $e->id_etudiant,
                    'type'       => 'etudiant',
                    'icon'       => '👨‍🎓',
                    'titre'      => "{$e->prenom} {$e->nom}",
                    'sous_titre' => "{$e->matricule} — {$e->filiere} {$e->niveau}",
                    'detail'     => $e->email,
                    'url'        => null,
                    'raw'        => $e,
                ]);
        }

        // ── Recherche Enseignants ─────────────────────────────
        if ($type === 'tous' || $type === 'enseignants') {
            $results['enseignants'] = Enseignant::where('nom',        'LIKE', "%{$query}%")
                ->orWhere('prenom',     'LIKE', "%{$query}%")
                ->orWhere('matricule',  'LIKE', "%{$query}%")
                ->orWhere('email',      'LIKE', "%{$query}%")
                ->orWhere('specialite', 'LIKE', "%{$query}%")
                ->limit(10)
                ->get(['id_enseignant', 'nom', 'prenom', 'matricule', 'email', 'specialite'])
                ->map(fn($e) => [
                    'id'         => $e->id_enseignant,
                    'type'       => 'enseignant',
                    'icon'       => '👨‍🏫',
                    'titre'      => "{$e->prenom} {$e->nom}",
                    'sous_titre' => $e->specialite ?? 'Aucune spécialité',
                    'detail'     => $e->email,
                    'url'        => null,
                    'raw'        => $e,
                ]);
        }

        // ── Recherche Cours ───────────────────────────────────
        if ($type === 'tous' || $type === 'cours') {
            $results['cours'] = Cours::where('titre',       'LIKE', "%{$query}%")
                ->orWhere('code',        'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->orWhere('filiere',     'LIKE', "%{$query}%")
                ->orWhere('niveau',      'LIKE', "%{$query}%")
                ->with('enseignant:id_enseignant,nom,prenom')
                ->limit(10)
                ->get(['id_cours', 'code', 'titre', 'description', 'filiere', 'niveau', 'id_enseignant'])
                ->map(fn($c) => [
                    'id'         => $c->id_cours,
                    'type'       => 'cours',
                    'icon'       => '📚',
                    'titre'      => "{$c->code} — {$c->titre}",
                    'sous_titre' => "{$c->filiere} {$c->niveau}",
                    'detail'     => $c->enseignant
                                    ? "👨‍🏫 {$c->enseignant->prenom} {$c->enseignant->nom}"
                                    : 'Aucun enseignant',
                    'url'        => null,
                    'raw'        => $c,
                ]);
        }

        // ── Recherche Notes (admin/enseignant uniquement) ─────
        if (($type === 'tous' || $type === 'notes') && in_array($userRole, ['admin', 'enseignant'])) {
            $results['notes'] = Note::whereHas('etudiant', function ($q) use ($query) {
                    $q->where('nom',       'LIKE', "%{$query}%")
                      ->orWhere('prenom',    'LIKE', "%{$query}%")
                      ->orWhere('matricule', 'LIKE', "%{$query}%");
                })
                ->orWhereHas('cours', function ($q) use ($query) {
                    $q->where('titre', 'LIKE', "%{$query}%")
                      ->orWhere('code', 'LIKE', "%{$query}%");
                })
                ->with([
                    'etudiant:id_etudiant,nom,prenom,matricule',
                    'cours:id_cours,code,titre',
                ])
                ->limit(10)
                ->get()
                ->map(fn($n) => [
                    'id'         => $n->id_note,
                    'type'       => 'note',
                    'icon'       => '📝',
                    'titre'      => "{$n->etudiant?->prenom} {$n->etudiant?->nom} — {$n->valeur}/20",
                    'sous_titre' => "{$n->cours?->code} {$n->cours?->titre}",
                    'detail'     => "Semestre {$n->semestre}",
                    'url'        => null,
                    'raw'        => $n,
                ]);
        }

        $total = collect($results)->flatten(1)->count();

        return response()->json([
            'success' => true,
            'data'    => [
                ...$results,
                'total' => $total,
                'query' => $query,
            ],
        ]);
    }
}