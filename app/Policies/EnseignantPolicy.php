<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Enseignant;

class EnseignantPolicy
{
    /**
     * L'admin peut tout faire
     */
    public function before(?Utilisateur $utilisateur, string $ability): bool|null
    {
        // ✅ VÉRIFICATION : Si pas d'utilisateur, refuser
        if (!$utilisateur) {
            return false;
        }
        
        if ($utilisateur->role === 'admin') {
            return true;
        }

        return null;
    }

    /**
     * Voir la liste des enseignants
     * ✅ TOUT LE MONDE peut voir (nécessaire pour messagerie)
     */
    public function viewAny(?Utilisateur $utilisateur): bool
    {
        // ✅ VÉRIFICATION DÉFENSIVE
        if (!$utilisateur) {
            return false;
        }
        
        // ✅ Tous les utilisateurs authentifiés peuvent voir
        return true;
    }

    /**
     * Voir un enseignant spécifique
     */
    public function view(?Utilisateur $utilisateur, Enseignant $enseignant): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        return true;
    }

    /**
     * Créer un enseignant
     */
    public function create(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        return $utilisateur->role === 'admin';
    }

    /**
     * Modifier un enseignant
     */
    public function update(?Utilisateur $utilisateur, Enseignant $enseignant): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin peut modifier n'importe quel enseignant (géré par before)
        
        // Un enseignant peut modifier ses propres données
        if ($utilisateur->role === 'enseignant') {
            return $enseignant->id_utilisateur === $utilisateur->id_utilisateur;
        }

        return false;
    }

    /**
     * Supprimer un enseignant
     */
    public function delete(?Utilisateur $utilisateur, Enseignant $enseignant): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Seul l'admin peut supprimer (géré par before)
        return false;
    }
}