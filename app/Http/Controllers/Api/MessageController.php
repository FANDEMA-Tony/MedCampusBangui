<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\MessageLike;
use App\Models\ReponseMessage;
use App\Mail\NouveauMessage;
use Illuminate\Support\Facades\Mail;

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
            ->each(function ($message) {
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
        $utilisateur = Auth::user();
        $role = $utilisateur->role;
        $type = $request->type ?? 'prive';

        // ✅ VALIDATION DYNAMIQUE SELON LE TYPE
        $rules = [
            'type' => 'required|in:prive,annonce,forum',
            'contenu' => 'required|string',
        ];

        // ✅ Validation spécifique MESSAGE PRIVÉ
        if ($type === 'prive') {
            $rules['destinataire_id'] = 'required|exists:utilisateurs,id_utilisateur';
            $rules['sujet'] = 'nullable|string|max:255';
        }

        // ✅ Validation spécifique ANNONCE
        if ($type === 'annonce') {
            $rules['visibilite'] = 'required|in:tous,enseignants,etudiants,cours';
            $rules['sujet'] = 'required|string|max:255';
            $rules['id_cours'] = 'nullable|exists:cours,id_cours';

            if (!in_array($role, ['admin', 'enseignant'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les administrateurs et enseignants peuvent créer des annonces.'
                ], 403);
            }
        }

        // ✅ Validation spécifique FORUM
        if ($type === 'forum') {
            $rules['sujet'] = 'required|string|max:255';
        }

        $messages = [
            'type.required' => 'Le type de message est obligatoire.',
            'type.in' => 'Le type de message doit être : prive, annonce ou forum.',
            'destinataire_id.required' => 'Le destinataire est obligatoire pour les messages privés.',
            'destinataire_id.exists' => 'Le destinataire sélectionné n\'existe pas.',
            'visibilite.required' => 'La visibilité est obligatoire pour les annonces.',
            'visibilite.in' => 'La visibilité doit être : tous, enseignants, etudiants ou cours.',
            'contenu.required' => 'Le contenu du message est obligatoire.',
            'sujet.required' => 'Le sujet est obligatoire.',
            'id_cours.exists' => 'Le cours sélectionné n\'existe pas.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // 🆕 VÉRIFICATION HIÉRARCHIQUE (Messages privés uniquement)
        if ($type === 'prive') {
            $destinataire = \App\Models\Utilisateur::find($request->destinataire_id);

            if (!$destinataire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Destinataire introuvable.'
                ], 404);
            }

            // ✅ CORRECTION FINALE : Vérification manuelle de la policy
            $policy = app(\App\Policies\MessagePolicy::class);
            $autorise = $policy->sendMessageTo($utilisateur, $destinataire);

            if (!$autorise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas envoyer de message à cet utilisateur. Les étudiants peuvent uniquement envoyer des messages aux enseignants.'
                ], 403);
            }
        }

        try {
            // ✅ Créer le message
            $message = Message::create([
                'expediteur_id' => $utilisateur->id_utilisateur,
                'destinataire_id' => $type === 'prive' ? $request->destinataire_id : null,
                'type' => $type,
                'visibilite' => $type === 'annonce' ? $request->visibilite : null,
                'id_cours' => ($type === 'annonce' && $request->visibilite === 'cours') ? $request->id_cours : null,
                'sujet' => $request->sujet,
                'contenu' => $request->contenu,
                'est_lu' => false,
            ]);

            $message->load('expediteur', 'destinataire', 'cours');

            return response()->json([
                'success' => true,
                'message' => $type === 'prive' ? 'Message envoyé avec succès' : ($type === 'annonce' ? 'Annonce publiée avec succès' :
                        'Message posté avec succès'),
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du message.',
                'error' => $e->getMessage()
            ], 500);
        }

        try {
            $destinataire = $message->destinataire;

            Mail::to($destinataire->email)->send(new NouveauMessage(
                nomDestinataire: $destinataire->prenom . ' ' . $destinataire->nom,
                nomExpediteur: $message->expediteur->prenom . ' ' . $message->expediteur->nom,
                sujet: $message->sujet ?? 'Nouveau message',
                apercu: substr($message->contenu, 0, 100),
            ));
        } catch (\Exception $e) {
            \Log::warning('Email message non envoyé : ' . $e->getMessage());
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
    /**
     * 🆕 Liker/Unliker un message (Forum/Annonce)
     */
    public function like(Message $message)
    {
        $this->authorize('view', $message);

        if (!$message->estPublic()) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les messages publics peuvent être likés.'
            ], 400);
        }

        $userId = Auth::id();

        // ✅ Vérifier si l'utilisateur a déjà liké
        $existingLike = MessageLike::where('id_message', $message->id_message)
            ->where('id_utilisateur', $userId)
            ->first();

        if ($existingLike) {
            // ✅ UNLIKER (retirer le like)
            $existingLike->delete();
            $message->decrement('nombre_likes');
            $liked = false;
        } else {
            // ✅ LIKER
            MessageLike::create([
                'id_message' => $message->id_message,
                'id_utilisateur' => $userId,
            ]);
            $message->increment('nombre_likes');
            $liked = true;
        }

        return response()->json([
            'success' => true,
            'message' => $liked ? 'Like ajouté !' : 'Like retiré !',
            'data' => [
                'nombre_likes' => $message->nombre_likes,
                'liked' => $liked
            ]
        ], 200);
    }

    /**
     * 🆕 Liste des réponses d'un message
     */
    public function reponses(Message $message)
    {
        $this->authorize('view', $message);

        $reponses = $message->reponses()->with('utilisateur')->get();

        return response()->json([
            'success' => true,
            'message' => 'Réponses récupérées avec succès',
            'data' => $reponses
        ], 200);
    }

    /**
     * 🆕 Ajouter une réponse à un message
     */
    public function repondre(Request $request, Message $message)
    {
        $this->authorize('view', $message);

        $validator = Validator::make($request->all(), [
            'contenu' => 'required|string',
        ], [
            'contenu.required' => 'Le contenu de la réponse est obligatoire.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reponse = ReponseMessage::create([
                'id_message' => $message->id_message,
                'id_utilisateur' => Auth::id(),
                'contenu' => $request->contenu,
            ]);

            $reponse->load('utilisateur');

            return response()->json([
                'success' => true,
                'message' => 'Réponse ajoutée avec succès',
                'data' => $reponse
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
