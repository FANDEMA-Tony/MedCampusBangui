<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Message;

class MessagePolicy
{
    /**
     * L'admin peut tout faire
     */
    public function before(?Utilisateur $utilisateur, string $ability): bool|null
    {
        if (!$utilisateur) {
            return false;
        }
        
        if ($utilisateur->role === 'admin') {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des messages
     */
    public function viewAny(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        return true;
    }

    /**
     * ✅ CORRIGÉ : Voir un message spécifique
     */
    public function view(?Utilisateur $utilisateur, Message $message): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // ✅ Si c'est un message PUBLIC (forum ou annonce) → Tous peuvent voir
        if ($message->estPublic()) {
            return true;
        }
        
        // ✅ Si c'est un message PRIVÉ → Seulement expéditeur ou destinataire
        return $message->expediteur_id === $utilisateur->id_utilisateur
            || $message->destinataire_id === $utilisateur->id_utilisateur;
    }

    /**
     * Créer un message
     */
    public function create(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        return true;
    }

    /**
     * 🆕 AJOUT : Envoyer un message privé à un destinataire spécifique
     */
    public function sendMessageTo(?Utilisateur $expediteur, Utilisateur $destinataire): bool
    {
        if (!$expediteur) {
            return false;
        }

        // ✅ RÈGLES HIÉRARCHIQUES

        // Admin → Tous
        if ($expediteur->role === 'admin') {
            return true;
        }

        // Enseignant → Tous (enseignants + étudiants)
        if ($expediteur->role === 'enseignant') {
            return true;
        }

        // Étudiant → Enseignants UNIQUEMENT (PAS aux autres étudiants)
        if ($expediteur->role === 'etudiant') {
            return $destinataire->role === 'enseignant';
        }

        return false;
    }

    /**
     * ✅ CORRIGÉ : Supprimer un message
     */
    public function delete(?Utilisateur $utilisateur, Message $message): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // ✅ Messages publics : Seulement l'auteur peut supprimer
        if ($message->estPublic()) {
            return $message->expediteur_id === $utilisateur->id_utilisateur;
        }
        
        // ✅ Messages privés : Expéditeur ou destinataire peuvent supprimer
        return $message->expediteur_id === $utilisateur->id_utilisateur
            || $message->destinataire_id === $utilisateur->id_utilisateur;
    }
}