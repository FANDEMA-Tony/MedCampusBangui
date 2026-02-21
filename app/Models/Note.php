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
        'date_evaluation', // ✅ Renommé (était date_attribution)
        'semestre', // 🆕
        'session', // 🆕
        'est_rattrape', // 🆕
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'est_rattrape' => 'boolean',
        'date_evaluation' => 'date',
    ];

    // ========== RELATIONS ==========

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'id_etudiant');
    }

    public function cours()
    {
        return $this->belongsTo(Cours::class, 'id_cours', 'id_cours');
    }

    // ========== SCOPES ==========

    /**
     * Notes en session normale
     */
    public function scopeSessionNormale($query)
    {
        return $query->where('session', 'normale');
    }

    /**
     * Notes en session de rattrapage
     */
    public function scopeSessionRattrapage($query)
    {
        return $query->where('session', 'rattrapage');
    }

    /**
     * Notes rattrapées (validées au rattrapage)
     */
    public function scopeRattrapees($query)
    {
        return $query->where('est_rattrape', true);
    }

    /**
     * Notes par semestre
     */
    public function scopeBySemestre($query, $semestre)
    {
        return $query->where('semestre', $semestre);
    }

    /**
     * Notes par filière (via étudiant)
     */
    public function scopeByFiliere($query, $filiere)
    {
        return $query->whereHas('etudiant', function($q) use ($filiere) {
            $q->where('filiere', $filiere);
        });
    }

    /**
     * Notes par niveau (via étudiant)
     */
    public function scopeByNiveau($query, $niveau)
    {
        return $query->whereHas('etudiant', function($q) use ($niveau) {
            $q->where('niveau', $niveau);
        });
    }

    // ========== MÉTHODES MÉTIER ==========

    /**
     * Déterminer automatiquement la session selon la note
     */
    public function determinerSession()
    {
        return $this->valeur >= 10 ? 'normale' : 'rattrapage';
    }

    /**
     * Vérifier si la note est validée
     */
    public function estValidee()
    {
        return $this->valeur >= 10;
    }

    /**
     * Récupérer le label du semestre
     */
    public function getSemestreLabel()
    {
        $labels = [
            'S1' => 'Semestre 1',
            'S2' => 'Semestre 2',
            'S3' => 'Semestre 3',
            'S4' => 'Semestre 4',
            'S5' => 'Semestre 5',
            'S6' => 'Semestre 6',
        ];
        
        return $labels[$this->semestre] ?? $this->semestre;
    }

    // ========== ÉVÉNEMENTS (OBSERVERS AUTOMATIQUES) ==========

    /**
     * Logique automatique lors de la sauvegarde
     */
    protected static function boot()
    {
        parent::boot();

        // Avant création
        static::creating(function ($note) {
            // Déterminer la session automatiquement si non définie
            if (empty($note->session)) {
                $note->session = $note->determinerSession();
            }
        });

        // Avant mise à jour
        static::updating(function ($note) {
            // Si note modifiée >= 10 et était en rattrapage
            if ($note->isDirty('valeur') && $note->valeur >= 10 && $note->session === 'rattrapage') {
                $note->session = 'normale';
                $note->est_rattrape = true; // Marquer comme rattrapé
            }
            
            // Si note modifiée < 10 et était en normale (sans être rattrapée)
            if ($note->isDirty('valeur') && $note->valeur < 10 && $note->session === 'normale' && !$note->est_rattrape) {
                $note->session = 'rattrapage';
            }
        });
    }
}