<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Cours;

class CoursPolicy
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
     * Voir la liste des cours
     */
    public function viewAny(Utilisateur $utilisateur): bool
    {
        // Admin et enseignant peuvent voir la liste
        return in_array($utilisateur->role, ['admin', 'enseignant', 'etudiant']);
    }

    /**
     * Voir un cours spécifique
     */
    public function view(Utilisateur $utilisateur, Cours $cours): bool
    {
        // Tous les rôles peuvent voir les détails d'un cours
        return true;
    }

    /**
     * Créer un cours
     */
    public function create(Utilisateur $utilisateur): bool
    {
        // Admin et enseignant peuvent créer (géré aussi par before pour admin)
        return $utilisateur->role === 'enseignant';
    }

    /**
     * Modifier un cours
     */
    public function update(Utilisateur $utilisateur, Cours $cours): bool
    {
        // L'enseignant peut modifier seulement ses propres cours
        if ($utilisateur->role === 'enseignant') {
            // Trouver l'enseignant correspondant à cet utilisateur
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if ($enseignant) {
                return $cours->id_enseignant === $enseignant->id_enseignant;
            }
        }

        return false;
    }

    /**
     * Supprimer un cours
     */
    public function delete(Utilisateur $utilisateur, Cours $cours): bool
    {
        // L'enseignant peut supprimer seulement ses propres cours
        if ($utilisateur->role === 'enseignant') {
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if ($enseignant) {
                return $cours->id_enseignant === $enseignant->id_enseignant;
            }
        }

        return false;
    }
}