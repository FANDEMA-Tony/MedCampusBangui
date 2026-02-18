<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\DonneeSanitaire;

class DonneeSanitairePolicy
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
     * Voir la liste des données sanitaires
     */
    public function viewAny(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin, enseignant et étudiant peuvent voir la liste
        return in_array($utilisateur->role, ['admin', 'enseignant', 'etudiant']);
    }

    /**
     * Voir une donnée sanitaire spécifique
     */
    public function view(?Utilisateur $utilisateur, DonneeSanitaire $donneeSanitaire): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Tous les utilisateurs authentifiés peuvent voir les données anonymisées
        return true;
    }

    /**
     * Créer une donnée sanitaire
     */
    public function create(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin, enseignant et étudiant peuvent collecter des données
        return in_array($utilisateur->role, ['admin', 'enseignant', 'etudiant']);
    }

    /**
 * Modifier une donnée sanitaire
 */
public function update(?Utilisateur $utilisateur, DonneeSanitaire $donneeSanitaire): bool
{
    if (!$utilisateur) {
        return false;
    }
    
    // ✅ CORRECTION : user.id au lieu de user.id_utilisateur
    return $donneeSanitaire->collecte_par === $utilisateur->id_utilisateur;
}

/**
 * Supprimer une donnée sanitaire
 */
public function delete(?Utilisateur $utilisateur, DonneeSanitaire $donneeSanitaire): bool
{
    if (!$utilisateur) {
        return false;
    }
    
    // ✅ CORRECTION : user.id au lieu de user.id_utilisateur
     return $donneeSanitaire->collecte_par === $utilisateur->id_utilisateur;
}

    /**
     * Voir les statistiques
     */
    public function viewStatistiques(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Admin, enseignant et étudiant peuvent voir les statistiques
        return in_array($utilisateur->role, ['admin', 'enseignant', 'etudiant']);
    }
}