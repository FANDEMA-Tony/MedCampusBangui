<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    use HasFactory;

    protected $table = 'examens';
    protected $primaryKey = 'id_examen';

    protected $fillable = [
        'id_cours',
        'titre',
        'date',
        'heure_debut',
        'heure_fin',
        'salle',
        'duree_minutes',
        'type_session',
        'filiere',
        'niveau',
        'semestre',
        'instructions',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ─── Relations ────────────────────────────────────────────
    public function cours()
    {
        return $this->belongsTo(Cours::class, 'id_cours', 'id_cours');
    }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeAVenir($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    public function scopePourFiliere($query, string $filiere, string $niveau)
    {
        return $query->where('filiere', $filiere)->where('niveau', $niveau);
    }
}
