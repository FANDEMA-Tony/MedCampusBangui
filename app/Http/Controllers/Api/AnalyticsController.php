<?php

namespace App\Http\Controllers\Api;

use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Note;
use App\Models\Cours;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;  // ✅ AJOUTER CETTE LIGNE

class AnalyticsController extends BaseApiController
{
    /**
     * 📊 ADMIN : Statistiques globales
     */
    public function statsAdmin()
    {
        $this->authorize('viewAny', Etudiant::class);
        
        try {
            // 1. Évolution inscriptions (6 derniers mois)
            $inscriptions = Etudiant::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as mois,
                COUNT(*) as total
            ')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();
            
            // 2. Répartition par filière
            $parFiliere = Etudiant::selectRaw('
                COALESCE(filiere, "Non spécifiée") as filiere,
                COUNT(*) as total
            ')
            ->groupBy('filiere')
            ->get();
            
            // 3. Taux de réussite par niveau
            $tauxReussite = DB::table('notes')
                ->join('etudiants', 'notes.id_etudiant', '=', 'etudiants.id_etudiant')
                ->selectRaw('
                    COALESCE(etudiants.niveau, "Non spécifié") as niveau,
                    COUNT(*) as total_notes,
                    SUM(CASE WHEN notes.valeur >= 10 THEN 1 ELSE 0 END) as notes_validees,
                    ROUND(SUM(CASE WHEN notes.valeur >= 10 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as taux
                ')
                ->groupBy('etudiants.niveau')
                ->get();
            
            // 4. Moyennes par cours (top 10)
            $moyennesCours = DB::table('notes')
                ->join('cours', 'notes.id_cours', '=', 'cours.id_cours')
                ->selectRaw('
                    cours.titre,
                    cours.code,
                    COUNT(*) as nb_notes,
                    ROUND(AVG(notes.valeur), 2) as moyenne
                ')
                ->groupBy('cours.id_cours', 'cours.titre', 'cours.code')
                ->orderBy('moyenne', 'DESC')
                ->limit(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'inscriptions' => $inscriptions,
                    'par_filiere' => $parFiliere,
                    'taux_reussite' => $tauxReussite,
                    'moyennes_cours' => $moyennesCours,
                    'totaux' => [
                        'etudiants' => Etudiant::count(),
                        'enseignants' => Enseignant::count(),
                        'cours' => Cours::count(),
                        'notes' => Note::count(),
                    ]
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 📊 ÉTUDIANT : Mes statistiques personnelles
     */
    public function statsEtudiant()
    {
        try {
            $utilisateur = Auth::user();
            
            // Vérifier que l'utilisateur est bien un étudiant
            $etudiant = Etudiant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$etudiant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme étudiant.'
                ], 403);
            }
            
            // 1. Évolution de mes notes par semestre
            // ✅ CLÉ CORRIGÉE : "evolution_notes" au lieu de "evolution"
            $evolutionNotes = DB::table('notes')
                ->where('notes.id_etudiant', $etudiant->id_etudiant)
                ->selectRaw('
                    COALESCE(notes.semestre, "S1") as semestre,
                    COUNT(*) as nb_notes,
                    ROUND(AVG(notes.valeur), 2) as moyenne,
                    MAX(notes.valeur) as meilleure,
                    MIN(notes.valeur) as moins_bonne
                ')
                ->groupBy('notes.semestre')
                ->orderBy('notes.semestre')
                ->get();
            
            // 2. Comparaison mes notes vs moyenne classe
            // ✅ CLÉ CORRIGÉE : "comparaison_classe" au lieu de "comparaison"
            $mesNotes = DB::table('notes')
                ->join('cours', 'notes.id_cours', '=', 'cours.id_cours')
                ->where('notes.id_etudiant', $etudiant->id_etudiant)
                ->select('cours.id_cours', 'cours.code', 'cours.titre', 'notes.valeur')
                ->get();
            
            $comparaisonClasse = [];
            
            foreach ($mesNotes as $note) {
                // Calculer la moyenne de la classe pour ce cours (même filière + niveau)
                $moyenneClasse = DB::table('notes')
                    ->join('etudiants', 'notes.id_etudiant', '=', 'etudiants.id_etudiant')
                    ->where('notes.id_cours', $note->id_cours)
                    ->where('etudiants.filiere', $etudiant->filiere)
                    ->where('etudiants.niveau', $etudiant->niveau)
                    ->avg('notes.valeur');
                
                $comparaisonClasse[] = [
                    'cours' => $note->titre,
                    'code' => $note->code,
                    'ma_note' => number_format((float)$note->valeur, 2, '.', ''),
                    'moyenne_classe' => number_format((float)$moyenneClasse, 2, '.', '')
                ];
            }
            
            // 3. Statistiques générales personnelles
            $statsGenerales = [
                'total_notes' => $mesNotes->count(),
                'moyenne_generale' => $mesNotes->count() > 0 
                    ? number_format($mesNotes->avg('valeur'), 2, '.', '') 
                    : '0.00',
                'meilleure_note' => $mesNotes->count() > 0 
                    ? number_format($mesNotes->max('valeur'), 2, '.', '') 
                    : '0.00',
                'notes_validees' => $mesNotes->where('valeur', '>=', 10)->count(),
                'taux_reussite' => $mesNotes->count() > 0
                    ? round(($mesNotes->where('valeur', '>=', 10)->count() / $mesNotes->count()) * 100, 2)
                    : 0
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'evolution_notes' => $evolutionNotes,      // ✅ CLÉ CORRECTE
                    'comparaison_classe' => $comparaisonClasse, // ✅ CLÉ CORRECTE
                    'stats_generales' => $statsGenerales
                ]
            ], 200);
            
        } catch (\Exception $e) {
             Log::error('Erreur statsEtudiant', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 📊 ENSEIGNANT : Statistiques de mes cours
     */
    public function statsEnseignant()
    {
        try {
            $utilisateur = Auth::user();
            $enseignant = Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas enregistré comme enseignant.'
                ], 403);
            }
            
            $mesCours = Cours::where('id_enseignant', $enseignant->id_enseignant)->pluck('id_cours');
            
            // 1. Performance par cours
            $performanceCours = DB::table('notes')
                ->join('cours', 'notes.id_cours', '=', 'cours.id_cours')
                ->whereIn('notes.id_cours', $mesCours)
                ->selectRaw('
                    cours.titre,
                    cours.code,
                    COUNT(*) as nb_notes,
                    ROUND(AVG(notes.valeur), 2) as moyenne,
                    SUM(CASE WHEN notes.valeur >= 10 THEN 1 ELSE 0 END) as reussites,
                    ROUND(SUM(CASE WHEN notes.valeur >= 10 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as taux_reussite
                ')
                ->groupBy('cours.id_cours', 'cours.titre', 'cours.code')
                ->orderBy('moyenne', 'DESC')
                ->get();
            
            // 2. Distribution des notes (tous mes cours confondus)
            $distributionNotes = DB::table('notes')
                ->whereIn('notes.id_cours', $mesCours)
                ->selectRaw('
                    CASE 
                        WHEN valeur >= 16 THEN "Excellent (16-20)"
                        WHEN valeur >= 14 THEN "Très bien (14-16)"
                        WHEN valeur >= 12 THEN "Bien (12-14)"
                        WHEN valeur >= 10 THEN "Assez bien (10-12)"
                        ELSE "Insuffisant (<10)"
                    END as tranche,
                    COUNT(*) as nb_notes
                ')
                ->groupBy('tranche')
                ->get();
            
            // 3. Statistiques générales enseignant
            $statsGenerales = [
                'nb_cours' => $mesCours->count(),
                'nb_etudiants_total' => DB::table('notes')
                    ->whereIn('id_cours', $mesCours)
                    ->distinct('id_etudiant')
                    ->count('id_etudiant'),
                'nb_notes_total' => DB::table('notes')
                    ->whereIn('id_cours', $mesCours)
                    ->count(),
                'moyenne_generale' => round(DB::table('notes')
                    ->whereIn('id_cours', $mesCours)
                    ->avg('valeur'), 2)
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'performance_cours' => $performanceCours,
                    'distribution_notes' => $distributionNotes,
                    'stats_generales' => $statsGenerales
                ]
            ], 200);
            
        } catch (\Exception $e) {
             Log::error('Erreur statsEnseignant', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}