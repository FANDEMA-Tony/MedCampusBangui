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
        'matricule',
        'statut',
        'id_utilisateur', // 🔹 AJOUT
    ];

    public function notes()
    {
        return $this->hasMany(Note::class, 'id_etudiant');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}
