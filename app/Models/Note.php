<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $table = 'notes';
    protected $primaryKey = 'id_note';

    protected $fillable = [
        'id_etudiant',
        'id_cours',
        'valeur',
        'date_attribution'
    ];

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'id_etudiant');
    }

    public function cours()
    {
        return $this->belongsTo(Cours::class, 'id_cours');
    }
}
