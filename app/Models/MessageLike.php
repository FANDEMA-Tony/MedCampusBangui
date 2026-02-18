<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLike extends Model
{
    protected $fillable = [
        'id_message',
        'id_utilisateur',
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