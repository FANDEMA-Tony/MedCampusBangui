<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Message;

class MessagePolicy
{
    /**
     * L'admin peut tout faire
     */
    public function before(Utilisateur $utilisateur, string $ability): bool|null
    {
        if ($utilisateur->role === 'admin') {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des messages
     */
    public function viewAny(Utilisateur $utilisateur): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir leurs messages
        return true;
    }

    /**
     * Voir un message spécifique
     */
    public function view(Utilisateur $utilisateur, Message $message): bool
    {
        // L'utilisateur peut voir un message s'il est l'expéditeur ou le destinataire
        return $message->expediteur_id === $utilisateur->id_utilisateur
            || $message->destinataire_id === $utilisateur->id_utilisateur;
    }

    /**
     * Créer un message
     */
    public function create(Utilisateur $utilisateur): bool
    {
        // Tous les utilisateurs authentifiés peuvent envoyer des messages
        return true;
    }

    /**
     * Supprimer un message
     */
    public function delete(Utilisateur $utilisateur, Message $message): bool
    {
        // L'utilisateur peut supprimer un message s'il est l'expéditeur ou le destinataire
        return $message->expediteur_id === $utilisateur->id_utilisateur
            || $message->destinataire_id === $utilisateur->id_utilisateur;
    }
}