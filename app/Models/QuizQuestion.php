<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'id_question';

    protected $fillable = [
        'id_quiz', 'question', 'type',
        'options', 'reponse_correcte', 'points', 'ordre',
    ];

    protected $casts = [
        'options' => 'array',
        'points'  => 'integer',
        'ordre'   => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'id_quiz');
    }
}