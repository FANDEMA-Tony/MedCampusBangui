<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Utilisateur extends Authenticatable implements JWTSubject
{
    protected $table = 'utilisateurs';
    protected $primaryKey = 'id_utilisateur';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'role',
        'statut'
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    // 🔹 Pour JWT - Identifiant unique
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // 🔹 Pour JWT - Ajouter le rôle dans le token
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // Important pour le middleware
            'nom' => $this->nom,
        ];
    }

    // 🔹 Pour que Laravel sache quel champ utiliser comme mot de passe
    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

    // 🔹 AJOUTE CES RELATIONS
    public function enseignant()
    {
        return $this->hasOne(Enseignant::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function etudiant()
    {
        return $this->hasOne(Etudiant::class, 'id_utilisateur', 'id_utilisateur');
    }
}