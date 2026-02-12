<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends BaseApiController
{
    /**
     * Liste des messages reçus par l'utilisateur connecté
     */
    public function boiteReception()
    {
        // Autorisation
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
     * Liste des messages envoyés par l'utilisateur connecté
     */
    public function boiteEnvoi()
    {
        // Autorisation
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
     * Conversation avec un utilisateur spécifique
     */
    public function conversation($utilisateurId)
    {
        // Autorisation
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
        // Autorisation
        $this->authorize('view', $message);
        
        $message->load(['expediteur', 'destinataire']);

        // Marquer comme lu si c'est le destinataire qui lit
        if ($message->destinataire_id === Auth::id()) {
            $message->marquerCommeLu();
        }

        return response()->json([
            'success' => true,
            'message' => 'Message récupéré avec succès',
            'data' => $message
        ], 200);
    }

    /**
     * Envoyer un nouveau message
     */
    public function store(Request $request)
    {
        // Autorisation
        $this->authorize('create', Message::class);
        
        $validator = Validator::make($request->all(), [
            'destinataire_id' => 'required|exists:utilisateurs,id_utilisateur',
            'sujet' => 'nullable|string|max:255',
            'contenu' => 'required|string'
        ], [
            'destinataire_id.required' => 'Le destinataire est obligatoire.',
            'destinataire_id.exists' => 'Ce destinataire n\'existe pas.',
            'contenu.required' => 'Le contenu du message est obligatoire.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier qu'on ne s'envoie pas un message à soi-même
        if ($request->destinataire_id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous envoyer un message à vous-même.'
            ], 422);
        }

        try {
            $message = Message::create([
                'expediteur_id' => Auth::id(),
                'destinataire_id' => $request->destinataire_id,
                'sujet' => $request->sujet,
                'contenu' => $request->contenu
            ]);

            $message->load(['expediteur', 'destinataire']);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du message.'
            ], 500);
        }
    }

    /**
     * Supprimer un message
     */
    public function destroy(Message $message)
    {
        // Autorisation
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
     * Nombre de messages non lus
     */
    public function nonLus()
    {
        // Autorisation
        $this->authorize('viewAny', Message::class);
        
        $count = Message::recusPar(Auth::id())->nonLus()->count();

        return response()->json([
            'success' => true,
            'message' => 'Nombre de messages non lus récupéré',
            'data' => [
                'non_lus' => $count
            ]
        ], 200);
    }
}