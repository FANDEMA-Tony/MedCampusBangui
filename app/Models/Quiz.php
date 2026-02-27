<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $primaryKey = 'id_quiz';
    protected $table = 'quiz'; // ← AJOUTONS CETTE LIGNE

    protected $fillable = [
        'titre', 'description', 'filiere', 'niveau',
        'duree_minutes', 'note_passage', 'est_publie', 'id_createur',
    ];

    protected $casts = [
        'est_publie'    => 'boolean',
        'note_passage'  => 'float',
        'duree_minutes' => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class, 'id_quiz')
                    ->orderBy('ordre');
    }

    public function tentatives()
    {
        return $this->hasMany(QuizTentative::class, 'id_quiz');
    }

    public function createur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_createur');
    }
}