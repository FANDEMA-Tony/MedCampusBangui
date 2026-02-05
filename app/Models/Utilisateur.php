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
        'email',
        'mot_de_passe',
        'role',
        'statut'
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }

}
