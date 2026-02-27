<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizTentative extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'id_tentative';

    protected $fillable = [
        'id_quiz', 'id_etudiant', 'reponses',
        'score', 'note_sur_20', 'est_reussi', 'temps_pris', 'created_at',
    ];

    protected $casts = [
        'reponses'   => 'array',
        'score'      => 'float',
        'note_sur_20'=> 'float',
        'est_reussi' => 'boolean',
        'temps_pris' => 'integer',
        'created_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'id_quiz');
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'id_etudiant');
    }
}