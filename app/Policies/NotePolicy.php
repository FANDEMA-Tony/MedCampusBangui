<?php

namespace App\Policies;

use App\Models\Utilisateur;
use App\Models\Note;

class NotePolicy
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
     * Voir la liste des notes
     */
    public function viewAny(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Enseignant et admin peuvent voir la liste (admin géré par before)
        return $utilisateur->role === 'enseignant';
    }

    /**
     * Voir une note spécifique
     */
    public function view(?Utilisateur $utilisateur, Note $note): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Un étudiant peut voir seulement ses propres notes
        if ($utilisateur->role === 'etudiant') {
            $etudiant = \App\Models\Etudiant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if ($etudiant) {
                return $note->id_etudiant === $etudiant->id_etudiant;
            }
        }

        // Un enseignant peut voir les notes de ses cours
        if ($utilisateur->role === 'enseignant') {
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if ($enseignant && $note->cours) {
                return $note->cours->id_enseignant === $enseignant->id_enseignant;
            }
        }

        return false;
    }

    /**
     * Créer une note
     */
    public function create(?Utilisateur $utilisateur): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Enseignant seulement (admin géré par before)
        return $utilisateur->role === 'enseignant';
    }

    /**
     * Modifier une note
     */
    public function update(?Utilisateur $utilisateur, Note $note): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Un enseignant peut modifier seulement les notes de ses cours
        if ($utilisateur->role === 'enseignant') {
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if ($enseignant && $note->cours) {
                return $note->cours->id_enseignant === $enseignant->id_enseignant;
            }
        }

        return false;
    }

    /**
     * Supprimer une note
     */
    public function delete(?Utilisateur $utilisateur, Note $note): bool
    {
        if (!$utilisateur) {
            return false;
        }
        
        // Un enseignant peut supprimer seulement les notes de ses cours
        if ($utilisateur->role === 'enseignant') {
            $enseignant = \App\Models\Enseignant::where('id_utilisateur', $utilisateur->id_utilisateur)->first();
            
            if ($enseignant && $note->cours) {
                return $note->cours->id_enseignant === $enseignant->id_enseignant;
            }
        }

        return false;
    }
}