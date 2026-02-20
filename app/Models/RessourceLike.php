<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RessourceLike extends Model
{
    protected $table = 'ressource_likes';

    protected $fillable = [
        'ressource_id',
        'utilisateur_id',
    ];

    /**
     * Relation : Un like appartient à une ressource
     */
    public function ressource()
    {
        return $this->belongsTo(RessourceMedicale::class, 'ressource_id', 'id_ressource');
    }

    /**
     * Relation : Un like appartient à un utilisateur
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id', 'id_utilisateur');
    }
}