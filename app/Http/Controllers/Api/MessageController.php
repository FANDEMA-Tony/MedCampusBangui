<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends BaseApiController
{
    /**
     * Liste des messages privés reçus
     */
    public function boiteReception()
    {
        $this->authorize('viewAny', Message::class);
        
        $messages = Message::with('expediteur')
            ->recusPar(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Boîte de réception récupérée avec succès',
            'data' => $messages->items(),
            'current_page' => $messages->currentPage(),
            'total' => $messages->total(),
            'non_lus' => Message::recusPar(Auth::id())->nonLus()->count()
        ], 200);
    }

    /**
     * Liste des messages privés envoyés
     */
    public function boiteEnvoi()
    {
        $this->authorize('viewAny', Message::class);
        
        $messages = Message::with('destinataire')
            ->envoyesPar(Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Messages envoyés récupérés avec succès',
            'data' => $messages->items(),
            'current_page' => $messages->currentPage(),
            'total' => $messages->total()
        ], 200);
    }

    /**
     * 🆕 Liste des annonces visibles par l'utilisateur
     */
    public function annonces()
    {
        $this->authorize('viewAny', Message::class);
        
        $utilisateur = Auth::user();
        
        $annonces = Message::annoncesVisiblesPar($utilisateur)
            ->with(['expediteur', 'cours'])
            ->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Annonces récupérées avec succès',
            'data' => $annonces
        ], 200);
    }

    /**
     * 🆕 Liste des messages du forum
     */
    public function forum()
    {
        $this->authorize('viewAny', Message::class);
        
        $messages = Message::forum()
            ->with(['expediteur'])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Messages du forum récupérés avec succès',
            'data' => $messages->items(),
            'current_page' => $messages->currentPage(),
            'total' => $messages->total()
        ], 200);
    }

    /**
     * Conversation avec un utilisateur spécifique
     */
    public function conversation($utilisateurId)
    {
        $this->authorize('viewAny', Message::class);
        
        $messages = Message::with(['expediteur', 'destinataire'])
            ->conversation(Auth::id(), $utilisateurId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Marquer les messages reçus comme lus
        Message::recusPar(Auth::id())
            ->where('expediteur_id', $utilisateurId)
            ->nonLus()
            ->get()
            ->each(function($message) {
                $message->marquerCommeLu();
            });

        return response()->json([
            'success' => true,
            'message' => 'Conversation récupérée avec succès',
            'data' => $messages
        ], 200);
    }

    /**
     * Afficher un message spécifique
     */
    public function show(Message $message)
    {
        $this->authorize('view', $message);
        
        $message->load(['expediteur', 'destinataire', 'cours']);

        // Marquer comme lu si c'est le destinataire qui lit
        if ($message->destinataire_id === Auth::id() && !$message->est_lu) {
            $message->marquerCommeLu();
        }

        // 🆕 Incrémenter vues pour messages publics
        if ($message->estPublic()) {
            $message->incrementerVues();
        }

        return response()->json([
            'success' => true,
            'message' => 'Message récupéré avec succès',
            'data' => $message
        ], 200);
    }

    /**
     * Envoyer un nouveau message (privé, annonce ou forum)
     */
    public function store(Request $request)
    {
        $this->authorize('create', Message::class);
        
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:prive,annonce,forum',
            'destinataire_id' => 'required_if:type,prive|exists:utilisateurs,id_utilisateur',
            'visibilite' => 'required_if:type,annonce|in:tous,enseignants,etudiants,cours',
            'id_cours' => 'nullable|exists:cours,id_cours',
            'sujet' => 'nullable|string|max:255',
            'contenu' => 'required|string'
        ], [
            'type.required' => 'Le type de message est obligatoire.',
            'type.in' => 'Type de message invalide.',
            'destinataire_id.required_if' => 'Le destinataire est obligatoire pour un message privé.',
            'destinataire_id.exists' => 'Ce destinataire n\'existe pas.',
            'visibilite.required_if' => 'La visibilité est obligatoire pour une annonce.',
            'visibilite.in' => 'Visibilité invalide.',
            'id_cours.exists' => 'Ce cours n\'existe pas.',
            'contenu.required' => 'Le contenu du message est obligatoire.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifications supplémentaires
        $type = $request->type;
        
        // Pour messages privés : pas d'auto-message
        if ($type === 'prive' && $request->destinataire_id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous envoyer un message à vous-même.'
            ], 422);
        }

        // Pour annonces : vérifier rôle
        if ($type === 'annonce') {
            $utilisateur = Auth::user();
            if (!in_array($utilisateur->role, ['admin', 'enseignant'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les administrateurs et enseignants peuvent créer des annonces.'
                ], 403);
            }
        }

        try {
            $message = Message::create([
                'expediteur_id' => Auth::id(),
                'destinataire_id' => $request->destinataire_id ?? null,
                'type' => $type,
                'visibilite' => $request->visibilite ?? null,
                'id_cours' => $request->id_cours ?? null,
                'sujet' => $request->sujet,
                'contenu' => $request->contenu
            ]);

            $message->load(['expediteur', 'destinataire', 'cours']);

            return response()->json([
                'success' => true,
                'message' => 'Message créé avec succès',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un message
     */
    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);
        
        try {
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du message.'
            ], 500);
        }
    }

    /**
     * Nombre de messages non lus (privés uniquement)
     */
    public function nonLus()
    {
        $this->authorize('viewAny', Message::class);
        
        $count = Message::recusPar(Auth::id())->nonLus()->count();

        return response()->json([
            'success' => true,
            'message' => 'Nombre de messages non lus récupéré',
            'data' => [
                'count' => $count,
                'non_lus' => $count // ✅ Les 2 formats pour compatibilité
            ]
        ], 200);
    }

    /**
     * 🆕 Épingler/Désépingler une annonce (admin uniquement)
     */
    public function toggleEpingle(Message $message)
    {
        $utilisateur = Auth::user();
        
        if ($utilisateur->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'administrateur peut épingler des annonces.'
            ], 403);
        }
        
        if ($message->type !== 'annonce') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les annonces peuvent être épinglées.'
            ], 400);
        }
        
        $message->est_epingle = !$message->est_epingle;
        $message->save();

        return response()->json([
            'success' => true,
            'message' => $message->est_epingle ? 'Annonce épinglée avec succès' : 'Annonce désépinglée avec succès',
            'data' => $message
        ], 200);
    }
}