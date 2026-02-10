<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Etudiant;

class EtudiantPolicy
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
     * Voir la liste des étudiants
     */
    public function viewAny(Utilisateur $utilisateur): bool
    {
        // Admin seulement (géré par before)
        return false;
    }

    /**
     * Voir un étudiant spécifique
     */
    public function view(Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        // Un étudiant peut voir ses propres infos
        if ($utilisateur->role === 'etudiant') {
            return $utilisateur->email === $etudiant->email;
        }

        return false;
    }

    /**
     * Créer un étudiant
     */
    public function create(Utilisateur $utilisateur): bool
    {
        // Admin seulement (géré par before)
        return false;
    }

    /**
     * Modifier un étudiant
     */
    public function update(Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        // Admin seulement (géré par before)
        return false;
    }

    /**
     * Supprimer un étudiant
     */
    public function delete(Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        // Admin seulement (géré par before)
        return false;
    }
}