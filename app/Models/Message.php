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
        'sujet',
        'contenu',
        'est_lu',
        'lu_a'
    ];

    protected $casts = [
        'est_lu' => 'boolean',
        'lu_a' => 'datetime',
    ];

    /**
     * Relation : Un message a un expéditeur
     */
    public function expediteur()
    {
        return $this->belongsTo(Utilisateur::class, 'expediteur_id', 'id_utilisateur');
    }

    /**
     * Relation : Un message a un destinataire
     */
    public function destinataire()
    {
        return $this->belongsTo(Utilisateur::class, 'destinataire_id', 'id_utilisateur');
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
     * Scope : Messages envoyés par un utilisateur
     */
    public function scopeEnvoyesPar($query, $utilisateurId)
    {
        return $query->where('expediteur_id', $utilisateurId);
    }

    /**
     * Scope : Messages reçus par un utilisateur
     */
    public function scopeRecusPar($query, $utilisateurId)
    {
        return $query->where('destinataire_id', $utilisateurId);
    }

    /**
     * Scope : Messages non lus
     */
    public function scopeNonLus($query)
    {
        return $query->where('est_lu', false);
    }

    /**
     * Scope : Conversation entre deux utilisateurs
     */
    public function scopeConversation($query, $utilisateur1, $utilisateur2)
    {
        return $query->where(function($q) use ($utilisateur1, $utilisateur2) {
            $q->where('expediteur_id', $utilisateur1)
              ->where('destinataire_id', $utilisateur2);
        })->orWhere(function($q) use ($utilisateur1, $utilisateur2) {
            $q->where('expediteur_id', $utilisateur2)
              ->where('destinataire_id', $utilisateur1);
        });
    }
}