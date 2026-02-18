<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id_message';

    protected $fillable = [
        'expediteur_id',
        'destinataire_id',
        'type',
        'visibilite',
        'id_cours',
        'sujet',
        'contenu',
        'est_lu',
        'lu_a',
        'est_epingle',
        'nombre_vues',
        'nombre_likes', // 🆕 AJOUTÉ
    ];

    protected $casts = [
        'est_lu' => 'boolean',
        'est_epingle' => 'boolean',
        'lu_a' => 'datetime',
        'nombre_vues' => 'integer',
    ];

    /**
     * Relation : Un message a un expéditeur
     */
    public function expediteur()
    {
        return $this->belongsTo(Utilisateur::class, 'expediteur_id', 'id_utilisateur');
    }

    /**
     * Relation : Un message a un destinataire (nullable pour messages publics)
     */
    public function destinataire()
    {
        return $this->belongsTo(Utilisateur::class, 'destinataire_id', 'id_utilisateur');
    }

    /**
     * Relation : Un message peut être lié à un cours
     */
    public function cours()
    {
        return $this->belongsTo(Cours::class, 'id_cours', 'id_cours');
    }

    /**
     * Marquer le message comme lu
     */
    public function marquerCommeLu()
    {
        if (!$this->est_lu) {
            $this->update([
                'est_lu' => true,
                'lu_a' => now()
            ]);
        }
    }

    /**
     * Incrémenter le nombre de vues
     */
    public function incrementerVues()
    {
        $this->increment('nombre_vues');
    }

    /**
     * Scope : Messages privés envoyés par un utilisateur
     */
    public function scopeEnvoyesPar($query, $utilisateurId)
    {
        return $query->where('expediteur_id', $utilisateurId)
                     ->where('type', 'prive');
    }

    /**
     * Scope : Messages privés reçus par un utilisateur
     */
    public function scopeRecusPar($query, $utilisateurId)
    {
        return $query->where('destinataire_id', $utilisateurId)
                     ->where('type', 'prive');
    }

    /**
     * Scope : Messages non lus
     */
    public function scopeNonLus($query)
    {
        return $query->where('est_lu', false);
    }

    /**
     * Scope : Annonces visibles par un utilisateur
     */
    public function scopeAnnoncesVisiblesPar($query, $utilisateur)
    {
        return $query->where('type', 'annonce')
                     ->where(function($q) use ($utilisateur) {
                         // Annonces pour tous
                         $q->where('visibilite', 'tous')
                           // Annonces pour son rôle
                           ->orWhere('visibilite', $utilisateur->role . 's')
                           // Annonces de cours auxquels il participe
                           ->orWhereHas('cours', function($subQ) use ($utilisateur) {
                               if ($utilisateur->role === 'enseignant') {
                                   $subQ->where('id_enseignant', $utilisateur->enseignant->id_enseignant ?? null);
                               } elseif ($utilisateur->role === 'etudiant') {
                                   // TODO: Ajouter relation cours-étudiants si nécessaire
                               }
                           });
                     })
                     ->orderBy('est_epingle', 'desc')
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope : Messages du forum
     */
    public function scopeForum($query)
    {
        return $query->where('type', 'forum')
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope : Conversation entre deux utilisateurs
     */
    public function scopeConversation($query, $utilisateur1, $utilisateur2)
    {
        return $query->where('type', 'prive')
                     ->where(function($q) use ($utilisateur1, $utilisateur2) {
                         $q->where('expediteur_id', $utilisateur1)
                           ->where('destinataire_id', $utilisateur2);
                     })->orWhere(function($q) use ($utilisateur1, $utilisateur2) {
                         $q->where('expediteur_id', $utilisateur2)
                           ->where('destinataire_id', $utilisateur1);
                     });
    }

    /**
     * Vérifier si le message est public
     */
    public function estPublic()
    {
        return in_array($this->type, ['annonce', 'forum']);
    }

    /**
     * Vérifier si le message est privé
     */
    public function estPrive()
    {
        return $this->type === 'prive';
    }

        /**
     * Incrémenter le nombre de likes
     */
    public function incrementerLikes()
    {
        $this->increment('nombre_likes');
    }

        /**
     * Relation : Les utilisateurs qui ont liké ce message
     */
    public function likes()
    {
        return $this->hasMany(MessageLike::class, 'id_message', 'id_message');
    }

    /**
     * Vérifier si un utilisateur a déjà liké ce message
     */
    public function isLikedBy($userId)
    {
        return $this->likes()->where('id_utilisateur', $userId)->exists();
    }

        /**
     * Relation : Les réponses à ce message
     */
    public function reponses()
    {
        return $this->hasMany(ReponseMessage::class, 'id_message', 'id_message')
                    ->with('utilisateur')
                    ->orderBy('created_at', 'asc');
    }
}