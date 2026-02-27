<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvenementCalendrier extends Model
{
    use HasFactory;

    protected $table = 'calendrier_evenements';
    protected $primaryKey = 'id_evenement';

    protected $fillable = [
        'titre',
        'description',
        'type',
        'date_debut',
        'date_fin',
        'lieu',
        'couleur',
        'visibilite',
        'filiere',
        'niveau',
        'est_important',
        'id_createur',
        'role_createur',
    ];

    protected $casts = [
        'date_debut'    => 'datetime',
        'date_fin'      => 'datetime',
        'est_important' => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────
    public function createur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_createur', 'id_utilisateur');
    }

    // ─── Scopes ───────────────────────────────────────────────

    /**
     * Événements visibles pour un étudiant (filière + niveau)
     */
    public function scopeVisiblePour($query, string $filiere, string $niveau)
    {
        return $query->where(function ($q) use ($filiere, $niveau) {
            $q->where('visibilite', 'tous')
              ->orWhere(function ($q2) use ($filiere) {
                  $q2->where('visibilite', 'filiere')
                     ->where('filiere', $filiere);
              })
              ->orWhere(function ($q2) use ($filiere, $niveau) {
                  $q2->where('visibilite', 'niveau')
                     ->where('filiere', $filiere)
                     ->where('niveau', $niveau);
              });
        });
    }

    /**
     * Événements d'un mois donné
     */
    public function scopeDuMois($query, int $annee, int $mois)
    {
        return $query->whereYear('date_debut', $annee)
                     ->whereMonth('date_debut', $mois);
    }

    /**
     * Événements à venir
     */
    public function scopeAVenir($query)
    {
        return $query->where('date_debut', '>=', now());
    }
}
