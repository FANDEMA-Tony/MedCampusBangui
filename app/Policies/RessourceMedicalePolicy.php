<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\RessourceMedicale;

class RessourceMedicalePolicy
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
     * Voir la liste des ressources
     */
    public function viewAny(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Tous les utilisateurs authentifiés peuvent voir la liste
        return true;
    }

    /**
     * Voir une ressource spécifique
     */
    public function view(?Utilisateur $utilisateur, RessourceMedicale $ressourceMedicale): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Si la ressource est publique, tout le monde peut la voir
        if ($ressourceMedicale->est_public) {
            return true;
        }

        // Sinon, seul l'admin ou le créateur peuvent voir les ressources privées
        return $utilisateur->role === 'admin' || $ressourceMedicale->ajoute_par === $utilisateur->id_utilisateur;
    }

    /**
     * Créer une ressource
     */
    public function create(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin et enseignant peuvent ajouter des ressources
        return in_array($utilisateur->role, ['admin', 'enseignant']);
    }

    /**
     * Modifier une ressource
     */
    public function update(?Utilisateur $utilisateur, RessourceMedicale $ressourceMedicale): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin peut modifier n'importe quelle ressource (géré par before)
        
        // Un enseignant peut modifier seulement ses propres ressources
        if ($utilisateur->role === 'enseignant') {
            return $ressourceMedicale->ajoute_par === $utilisateur->id_utilisateur;
        }

        return false;
    }

    /**
     * Supprimer une ressource
     */
    public function delete(?Utilisateur $utilisateur, RessourceMedicale $ressourceMedicale): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin peut supprimer n'importe quelle ressource (géré par before)
        
        // Un enseignant peut supprimer seulement ses propres ressources
        if ($utilisateur->role === 'enseignant') {
            return $ressourceMedicale->ajoute_par === $utilisateur->id_utilisateur;
        }

        return false;
    }
}