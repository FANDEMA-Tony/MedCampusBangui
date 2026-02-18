<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReponseMessage extends Model
{
    protected $table = 'reponses_messages';
    protected $primaryKey = 'id_reponse';

    protected $fillable = [
        'id_message',
        'id_utilisateur',
        'contenu',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class, 'id_message', 'id_message');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}