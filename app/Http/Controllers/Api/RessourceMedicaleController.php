<?php

namespace App\Http\Controllers\Api;

use App\Models\RessourceMedicale;
use App\Models\RessourceLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RessourceMedicaleController extends BaseApiController
{
    /**
     * Liste de toutes les ressources (avec filtres et recherche)
     */
    public function index(Request $request)
    {
        // Autorisation
        $this->authorize('viewAny', RessourceMedicale::class);
        
        $query = RessourceMedicale::with(['utilisateur', 'likes']);

        // Filtre par type
        if ($request->has('type')) {
            $query->type($request->type);
        }

        // Filtre par catégorie
        if ($request->has('categorie')) {
            $query->categorie($request->categorie);
        }

        // Filtre par niveau
        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        // Recherche
        if ($request->has('recherche')) {
            $query->recherche($request->recherche);
        }

        // Si l'utilisateur n'est pas admin, montrer seulement les ressources publiques
        if (Auth::user()->role !== 'admin') {
            $query->publiques();
        }

        $ressources = $query->orderBy('created_at', 'desc')->paginate(15);

        // 🆕 Ajouter le statut "liké par l'utilisateur actuel" à chaque ressource
        $ressources->getCollection()->transform(function ($ressource) {
            $ressource->est_like_par_moi = $ressource->estLikePar(Auth::id());
            $ressource->nombre_likes = $ressource->nombre_likes;
            return $ressource;
        });

        return response()->json([
            'success' => true,
            'message' => 'Liste des ressources récupérée avec succès',
            'data' => $ressources->items(),
            'current_page' => $ressources->currentPage(),
            'total' => $ressources->total()
        ], 200);
    }

    /**
     * Afficher une ressource spécifique
     */
    public function show(RessourceMedicale $ressourceMedicale)
    {
        // Autorisation
        $this->authorize('view', $ressourceMedicale);
        
        // ✅ INCRÉMENTER LES VUES
        $ressourceMedicale->incrementerVues();
        
        $ressourceMedicale->load(['utilisateur', 'likes']);

        // 🆕 Ajouter infos likes
        $ressourceMedicale->est_like_par_moi = $ressourceMedicale->estLikePar(Auth::id());
        $ressourceMedicale->nombre_likes = $ressourceMedicale->nombre_likes;

        return response()->json([
            'success' => true,
            'message' => 'Ressource récupérée avec succès',
            'data' => $ressourceMedicale
        ], 200);
    }

    /**
     * Créer une nouvelle ressource (avec upload de fichier)
     */
    public function store(Request $request)
    {
        // Autorisation
        $this->authorize('create', RessourceMedicale::class);
        
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'auteur' => 'nullable|string|max:255',
            'type' => 'required|in:cours,livre,video,article,autre',
            'categorie' => 'nullable|string|max:255',
            'niveau' => 'nullable|in:L1,L2,L3,M1,M2,doctorat,formation_continue',
            'fichier' => 'required|file|max:102400', // Max 100Mo
            'est_public' => 'nullable|boolean'
        ], [
            'titre.required' => 'Le titre est obligatoire.',
            'type.required' => 'Le type de ressource est obligatoire.',
            'type.in' => 'Le type doit être : cours, livre, video, article ou autre.',
            'fichier.required' => 'Le fichier est obligatoire.',
            'fichier.file' => 'Le fichier doit être un fichier valide.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 100 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Upload du fichier
            $fichier = $request->file('fichier');
            $nomOriginal = $fichier->getClientOriginalName();
            $extension = $fichier->getClientOriginalExtension();
            $taille = $fichier->getSize();
            
            // Générer un nom unique pour le fichier
            $nomUnique = Str::uuid() . '.' . $extension;
            
            // Stocker le fichier dans storage/app/public/ressources
            $chemin = $fichier->storeAs('ressources', $nomUnique, 'public');

            // Créer la ressource en base de données
            $ressource = RessourceMedicale::create([
                'titre' => $request->titre,
                'description' => $request->description,
                'auteur' => $request->auteur,
                'type' => $request->type,
                'categorie' => $request->categorie,
                'niveau' => $request->niveau,
                'nom_fichier' => $nomOriginal,
                'chemin_fichier' => $chemin,
                'type_fichier' => $extension,
                'taille_fichier' => $taille,
                'est_public' => $request->est_public ?? true,
                'ajoute_par' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ressource créée avec succès',
                'data' => $ressource
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la ressource.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une ressource
     */
    public function update(Request $request, RessourceMedicale $ressourceMedicale)
    {
        // Autorisation
        $this->authorize('update', $ressourceMedicale);
        
        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'auteur' => 'nullable|string|max:255',
            'type' => 'sometimes|in:cours,livre,video,article,autre',
            'categorie' => 'nullable|string|max:255',
            'niveau' => 'nullable|in:L1,L2,L3,M1,M2,doctorat,formation_continue',
            'est_public' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ressourceMedicale->update($request->only([
                'titre', 'description', 'auteur', 'type', 
                'categorie', 'niveau', 'est_public'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Ressource mise à jour avec succès',
                'data' => $ressourceMedicale
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de la ressource.'
            ], 500);
        }
    }

    /**
     * Supprimer une ressource
     */
    public function destroy(RessourceMedicale $ressourceMedicale)
    {
        // Autorisation
        $this->authorize('delete', $ressourceMedicale);
        
        try {
            // Supprimer le fichier physique
            Storage::disk('public')->delete($ressourceMedicale->chemin_fichier);
            
            // Supprimer l'enregistrement en base
            $ressourceMedicale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ressource supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression de la ressource.'
            ], 500);
        }
    }

    /**
     * 📥 Télécharger une ressource
     */
    public function telecharger(RessourceMedicale $ressourceMedicale)
    {
        try {
            // ✅ CORRECTION : 'nombre_telechargements' et non 'nb_telechargements'
            // C'était la cause du 404 — l'exception SQL faisait retourner une 404
            $ressourceMedicale->increment('nombre_telechargements');
            
            // ✅ Vérifier que le fichier existe
            if (!Storage::disk('public')->exists($ressourceMedicale->chemin_fichier)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier introuvable sur le serveur.'
                ], 404);
            }
            
            // ✅ Utiliser response()->download() avec le chemin complet
            $cheminComplet = storage_path('app/public/' . $ressourceMedicale->chemin_fichier);
            
            return response()->download(
                $cheminComplet,
                $ressourceMedicale->nom_fichier
            );
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 Liker/Unliker une ressource
     */
    public function like(RessourceMedicale $ressourceMedicale)
    {
        // Autorisation (tous les utilisateurs authentifiés peuvent liker)
        $this->authorize('view', $ressourceMedicale);
        
        try {
            $utilisateurId = Auth::id();
            
            // Vérifier si l'utilisateur a déjà liké
            $like = RessourceLike::where('ressource_id', $ressourceMedicale->id_ressource)
                                 ->where('utilisateur_id', $utilisateurId)
                                 ->first();
            
            if ($like) {
                // Unliker
                $like->delete();
                $message = 'Like retiré';
                $liked = false;
            } else {
                // Liker
                RessourceLike::create([
                    'ressource_id' => $ressourceMedicale->id_ressource,
                    'utilisateur_id' => $utilisateurId,
                ]);
                $message = 'Ressource likée';
                $liked = true;
            }

            // Recharger les likes
            $ressourceMedicale->load('likes');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'liked' => $liked,
                    'nombre_likes' => $ressourceMedicale->nombre_likes,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 Prévisualiser une ressource (streaming vidéo/PDF)
     */
    public function previsualiser(RessourceMedicale $ressourceMedicale)
    {
        // Autorisation
        $this->authorize('view', $ressourceMedicale);
        
        try {
            $chemin = storage_path('app/public/' . $ressourceMedicale->chemin_fichier);
            
            if (!file_exists($chemin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier introuvable'
                ], 404);
            }

            // Déterminer le type MIME
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ];

            $extension = strtolower($ressourceMedicale->type_fichier);
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            return response()->file($chemin, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $ressourceMedicale->nom_fichier . '"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation'
            ], 500);
        }
    }
}