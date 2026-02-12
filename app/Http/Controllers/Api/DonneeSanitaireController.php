<?php

namespace App\Http\Controllers\Api;

use App\Models\DonneeSanitaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonneeSanitaireController extends BaseApiController
{
    /**
     * Liste de toutes les données sanitaires (avec filtres)
     */
    public function index(Request $request)
    {
        // Autorisation
        $this->authorize('viewAny', DonneeSanitaire::class);
        
        $query = DonneeSanitaire::with('collecteur');

        // Filtre par pathologie
        if ($request->has('pathologie')) {
            $query->pathologie($request->pathologie);
        }

        // Filtre par ville
        if ($request->has('ville')) {
            $query->ville($request->ville);
        }

        // Filtre par commune
        if ($request->has('commune')) {
            $query->commune($request->commune);
        }

        // Filtre par gravité
        if ($request->has('gravite')) {
            $query->gravite($request->gravite);
        }

        // Filtre par tranche d'âge
        if ($request->has('tranche_age')) {
            $query->trancheAge($request->tranche_age);
        }

        // Filtre par sexe
        if ($request->has('sexe')) {
            $query->sexe($request->sexe);
        }

        // Filtre par période
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->periode($request->date_debut, $request->date_fin);
        }

        // Cas graves uniquement
        if ($request->has('graves') && $request->graves == 'true') {
            $query->casGraves();
        }

        // Cas en cours uniquement
        if ($request->has('en_cours') && $request->en_cours == 'true') {
            $query->enCours();
        }

        $donnees = $query->orderBy('date_consultation', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Liste des données sanitaires récupérée avec succès',
            'data' => $donnees->items(),
            'current_page' => $donnees->currentPage(),
            'total' => $donnees->total()
        ], 200);
    }

    /**
     * Afficher une donnée sanitaire spécifique
     */
    public function show(DonneeSanitaire $donneeSanitaire)
    {
        // Autorisation
        $this->authorize('view', $donneeSanitaire);
        
        $donneeSanitaire->load('collecteur');

        return response()->json([
            'success' => true,
            'message' => 'Donnée sanitaire récupérée avec succès',
            'data' => $donneeSanitaire
        ], 200);
    }

    /**
     * Créer une nouvelle donnée sanitaire
     */
    public function store(Request $request)
    {
        // Autorisation
        $this->authorize('create', DonneeSanitaire::class);
        
        $validator = Validator::make($request->all(), [
            'sexe' => 'nullable|in:M,F,Autre',
            'age' => 'nullable|integer|min:0|max:150',
            'quartier' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'coordonnees_gps' => 'nullable|string',
            'pathologie' => 'required|string|max:255',
            'symptomes' => 'nullable|string',
            'gravite' => 'required|in:leger,modere,grave,critique',
            'date_debut_symptomes' => 'nullable|date',
            'date_consultation' => 'required|date',
            'diagnostic' => 'nullable|string',
            'traitement_prescrit' => 'nullable|string',
            'statut' => 'nullable|in:en_cours,guerison,decede,suivi_perdu',
            'antecedents_medicaux' => 'nullable|boolean',
            'antecedents_details' => 'nullable|string',
            'vaccination_a_jour' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ], [
            'pathologie.required' => 'La pathologie est obligatoire.',
            'gravite.required' => 'Le niveau de gravité est obligatoire.',
            'gravite.in' => 'La gravité doit être : leger, modere, grave ou critique.',
            'date_consultation.required' => 'La date de consultation est obligatoire.',
            'date_consultation.date' => 'La date de consultation doit être une date valide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $donnee = DonneeSanitaire::create([
                'sexe' => $request->sexe,
                'age' => $request->age,
                'quartier' => $request->quartier,
                'commune' => $request->commune,
                'ville' => $request->ville ?? 'Bangui',
                'coordonnees_gps' => $request->coordonnees_gps,
                'pathologie' => $request->pathologie,
                'symptomes' => $request->symptomes,
                'gravite' => $request->gravite,
                'date_debut_symptomes' => $request->date_debut_symptomes,
                'date_consultation' => $request->date_consultation,
                'diagnostic' => $request->diagnostic,
                'traitement_prescrit' => $request->traitement_prescrit,
                'statut' => $request->statut ?? 'en_cours',
                'antecedents_medicaux' => $request->antecedents_medicaux ?? false,
                'antecedents_details' => $request->antecedents_details,
                'vaccination_a_jour' => $request->vaccination_a_jour,
                'notes' => $request->notes,
                'collecte_par' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Donnée sanitaire enregistrée avec succès',
                'data' => $donnee
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une donnée sanitaire
     */
    public function update(Request $request, DonneeSanitaire $donneeSanitaire)
    {
        // Autorisation
        $this->authorize('update', $donneeSanitaire);
        
        $validator = Validator::make($request->all(), [
            'sexe' => 'nullable|in:M,F,Autre',
            'age' => 'nullable|integer|min:0|max:150',
            'quartier' => 'nullable|string|max:255',
            'commune' => 'nullable|string|max:255',
            'ville' => 'nullable|string|max:255',
            'pathologie' => 'sometimes|string|max:255',
            'symptomes' => 'nullable|string',
            'gravite' => 'sometimes|in:leger,modere,grave,critique',
            'date_debut_symptomes' => 'nullable|date',
            'date_consultation' => 'sometimes|date',
            'diagnostic' => 'nullable|string',
            'traitement_prescrit' => 'nullable|string',
            'statut' => 'nullable|in:en_cours,guerison,decede,suivi_perdu',
            'antecedents_medicaux' => 'nullable|boolean',
            'antecedents_details' => 'nullable|string',
            'vaccination_a_jour' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $donneeSanitaire->update($request->only([
                'sexe', 'age', 'quartier', 'commune', 'ville',
                'pathologie', 'symptomes', 'gravite',
                'date_debut_symptomes', 'date_consultation',
                'diagnostic', 'traitement_prescrit', 'statut',
                'antecedents_medicaux', 'antecedents_details',
                'vaccination_a_jour', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Donnée sanitaire mise à jour avec succès',
                'data' => $donneeSanitaire
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour.'
            ], 500);
        }
    }

    /**
     * Supprimer une donnée sanitaire
     */
    public function destroy(DonneeSanitaire $donneeSanitaire)
    {
        // Autorisation
        $this->authorize('delete', $donneeSanitaire);
        
        try {
            $donneeSanitaire->delete();

            return response()->json([
                'success' => true,
                'message' => 'Donnée sanitaire supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression.'
            ], 500);
        }
    }

    /**
     * Statistiques globales
     */
    public function statistiques(Request $request)
    {
        // Autorisation
        $this->authorize('viewStatistiques', DonneeSanitaire::class);
        
        try {
            $stats = [
                'total_cas' => DonneeSanitaire::count(),
                'cas_en_cours' => DonneeSanitaire::where('statut', 'en_cours')->count(),
                'cas_gueris' => DonneeSanitaire::where('statut', 'guerison')->count(),
                'cas_graves' => DonneeSanitaire::whereIn('gravite', ['grave', 'critique'])->count(),
                
                // Par gravité
                'par_gravite' => DonneeSanitaire::select('gravite', DB::raw('count(*) as total'))
                    ->groupBy('gravite')
                    ->get(),
                
                // Top 10 pathologies
                'top_pathologies' => DonneeSanitaire::select('pathologie', DB::raw('count(*) as total'))
                    ->groupBy('pathologie')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get(),
                
                // Par tranche d'âge
                'par_tranche_age' => DonneeSanitaire::select('tranche_age', DB::raw('count(*) as total'))
                    ->whereNotNull('tranche_age')
                    ->groupBy('tranche_age')
                    ->get(),
                
                // Par sexe
                'par_sexe' => DonneeSanitaire::select('sexe', DB::raw('count(*) as total'))
                    ->whereNotNull('sexe')
                    ->groupBy('sexe')
                    ->get(),
                
                // Par commune (Top 5)
                'par_commune' => DonneeSanitaire::select('commune', DB::raw('count(*) as total'))
                    ->whereNotNull('commune')
                    ->groupBy('commune')
                    ->orderBy('total', 'desc')
                    ->limit(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistiques récupérées avec succès',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la génération des statistiques.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}