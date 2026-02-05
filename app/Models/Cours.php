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

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'id_cours');
    }
}
