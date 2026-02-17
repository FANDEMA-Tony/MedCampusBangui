<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Etudiant;

class EtudiantPolicy
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
     * Voir la liste des étudiants
     * ✅ Admin + Enseignant peuvent voir (nécessaire pour messagerie)
     */
    public function viewAny(?Utilisateur $utilisateur): bool
    {
        // ✅ VÉRIFICATION DÉFENSIVE
        if (!$utilisateur) {
            return false;
        }
        
        // ✅ Admin et enseignant peuvent voir la liste
        return in_array($utilisateur->role, ['admin', 'enseignant']);
    }

    /**
     * Voir un étudiant spécifique
     */
    public function view(?Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin et enseignant peuvent voir
        if (in_array($utilisateur->role, ['admin', 'enseignant'])) {
            return true;
        }
        
        // Un étudiant peut voir ses propres données
        if ($utilisateur->role === 'etudiant') {
            return $etudiant->id_utilisateur === $utilisateur->id_utilisateur;
        }

        return false;
    }

    /**
     * Créer un étudiant
     */
    public function create(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        return $utilisateur->role === 'admin';
    }

    /**
     * Modifier un étudiant
     */
    public function update(?Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin peut modifier n'importe quel étudiant (géré par before)
        
        // Un étudiant peut modifier ses propres données
        if ($utilisateur->role === 'etudiant') {
            return $etudiant->id_utilisateur === $utilisateur->id_utilisateur;
        }

        return false;
    }

    /**
     * Supprimer un étudiant
     */
    public function delete(?Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Seul l'admin peut supprimer (géré par before)
        return false;
    }
}