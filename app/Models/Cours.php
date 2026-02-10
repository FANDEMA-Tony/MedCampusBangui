<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cours extends Model
{
    protected $table = 'cours';
    protected $primaryKey = 'id_cours';

    protected $fillable = [
        'code',
        'titre',
        'description',
        'id_enseignant'
    ];

    // Relation : Un cours appartient à un enseignant
    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    // Relation : Un cours a plusieurs notes
    public function notes()
    {
        return $this->hasMany(Note::class, 'id_cours', 'id_cours');
    }
}