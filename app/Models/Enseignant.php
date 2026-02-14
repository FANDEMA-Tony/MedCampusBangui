<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enseignant extends Model
{
    protected $table = 'enseignants';
    protected $primaryKey = 'id_enseignant';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'date_naissance',
        'specialite',
        'matricule',
        'statut',
        'id_utilisateur', // 🔹 Ajout
    ];

    public function cours()
    {
        return $this->hasMany(Cours::class, 'id_enseignant');
    }
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}
