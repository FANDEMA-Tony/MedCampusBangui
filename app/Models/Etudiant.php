<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model
{
    protected $table = 'etudiants';
    protected $primaryKey = 'id_etudiant';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'date_naissance',
        'filiere',
        'statut'
    ];

    public function notes()
    {
        return $this->hasMany(Note::class, 'id_etudiant');
    }
}
