<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploiDuTemps extends Model
{
    use HasFactory;

    protected $table = 'emploi_du_temps';
    protected $primaryKey = 'id_emploi';

    protected $fillable = [
        'id_cours',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'salle',
        'filiere',
        'niveau',
        'semestre',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────
    public function cours()
    {
        return $this->belongsTo(Cours::class, 'id_cours', 'id_cours');
    }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopePourFiliere($query, string $filiere, string $niveau)
    {
        return $query->where('filiere', $filiere)->where('niveau', $niveau);
    }
}
