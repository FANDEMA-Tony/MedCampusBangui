<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DonneeSanitaire extends Model
{
    protected $table = 'donnees_sanitaires';
    protected $primaryKey = 'id_donnee';

    protected $fillable = [
        'code_patient',
        'nom_patient',        // 🆕 AJOUTÉ
        'prenom_patient',     // 🆕 AJOUTÉ
        'telephone_patient',  // 🆕 AJOUTÉ
        'sexe',
        'age',
        'tranche_age',
        'quartier',
        'commune',
        'ville',
        'coordonnees_gps',
        'pathologie',
        'symptomes',
        'gravite',
        'date_debut_symptomes',
        'date_consultation',
        'diagnostic',
        'traitement_prescrit',
        'statut',
        'antecedents_medicaux',
        'antecedents_details',
        'vaccination_a_jour',
        'notes',
        'est_anonyme',
        'collecte_par'
    ];

    protected $casts = [
        'date_debut_symptomes' => 'date',
        'date_consultation' => 'date',
        'antecedents_medicaux' => 'boolean',
        'vaccination_a_jour' => 'boolean',
        'est_anonyme' => 'boolean',
        'age' => 'integer',
    ];

    /**
     * Boot du modèle - Générer automatiquement le code patient
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($donnee) {
            // Générer code patient unique
            if (empty($donnee->code_patient)) {
                $donnee->code_patient = 'PAT-' . strtoupper(Str::random(10));
            }

            // Calculer tranche âge
            if ($donnee->age && empty($donnee->tranche_age)) {
                $donnee->tranche_age = self::calculerTrancheAge($donnee->age);
            }

            // ✅ LOGIQUE ANONYMISATION
            // Si nom/prénom fournis → NON anonyme
            // Si vides → Anonyme
            if (empty($donnee->nom_patient) && empty($donnee->prenom_patient)) {
                $donnee->est_anonyme = true;
            } else {
                $donnee->est_anonyme = false;
            }
        });
    }

    /**
     * Relation : Une donnée est collectée par un utilisateur
     */
    public function collecteur()
    {
        return $this->belongsTo(Utilisateur::class, 'collecte_par', 'id_utilisateur');
    }

    /**
     * Calculer la tranche d'âge à partir de l'âge
     */
    public static function calculerTrancheAge($age)
    {
        if ($age < 6) return '0-5';
        if ($age < 13) return '6-12';
        if ($age < 19) return '13-18';
        if ($age < 36) return '19-35';
        if ($age < 61) return '36-60';
        return '60+';
    }

    /**
     * Scope : Filtrer par pathologie
     */
    public function scopePathologie($query, $pathologie)
    {
        return $query->where('pathologie', 'like', '%' . $pathologie . '%');
    }

    /**
     * Scope : Filtrer par ville
     */
    public function scopeVille($query, $ville)
    {
        return $query->where('ville', $ville);
    }

    /**
     * Scope : Filtrer par commune
     */
    public function scopeCommune($query, $commune)
    {
        return $query->where('commune', $commune);
    }

    /**
     * Scope : Filtrer par gravité
     */
    public function scopeGravite($query, $gravite)
    {
        return $query->where('gravite', $gravite);
    }

    /**
     * Scope : Filtrer par période
     */
    public function scopePeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_consultation', [$dateDebut, $dateFin]);
    }

    /**
     * Scope : Filtrer par tranche d'âge
     */
    public function scopeTrancheAge($query, $tranche)
    {
        return $query->where('tranche_age', $tranche);
    }

    /**
     * Scope : Filtrer par sexe
     */
    public function scopeSexe($query, $sexe)
    {
        return $query->where('sexe', $sexe);
    }

    /**
     * Scope : Cas graves uniquement
     */
    public function scopeCasGraves($query)
    {
        return $query->whereIn('gravite', ['grave', 'critique']);
    }

    /**
     * Scope : Cas en cours de traitement
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    /**
     * Obtenir les coordonnées GPS sous forme de tableau
     */
    public function getCoordonnees()
    {
        if (empty($this->coordonnees_gps)) {
            return null;
        }

        $coords = explode(',', $this->coordonnees_gps);
        
        if (count($coords) === 2) {
            return [
                'latitude' => trim($coords[0]),
                'longitude' => trim($coords[1])
            ];
        }

        return null;
    }

    /**
     * 🆕 Accessor - Nom complet patient
     */
    public function getNomCompletAttribute()
    {
        if ($this->est_anonyme) {
            return "Patient Anonyme ({$this->code_patient})";
        }
        
        return trim("{$this->prenom_patient} {$this->nom_patient}") ?: $this->code_patient;
    }
}