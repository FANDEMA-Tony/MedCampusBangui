<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Enseignant;

class EnseignantPolicy
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
     * Voir la liste des enseignants
     */
    public function viewAny(Utilisateur $utilisateur): bool
    {
        // Admin seulement (géré par before)
        return false;
    }

    /**
     * Voir un enseignant spécifique
     */
    public function view(Utilisateur $utilisateur, Enseignant $enseignant): bool
    {
        // Un enseignant peut voir ses propres infos
        if ($utilisateur->role === 'enseignant') {
            return $utilisateur->email === $enseignant->email;
        }

        return false;
    }

    /**
     * Créer un enseignant
     */
    public function create(Utilisateur $utilisateur): bool
    {
        // Admin seulement (géré par before)
        return false;
    }

    /**
     * Modifier un enseignant
     */
    public function update(Utilisateur $utilisateur, Enseignant $enseignant): bool
    {
        // Admin seulement (géré par before)
        return false;
    }

    /**
     * Supprimer un enseignant
     */
    public function delete(Utilisateur $utilisateur, Enseignant $enseignant): bool
    {
        // Admin seulement (géré par before)
        return false;
    }
}