<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RessourceMedicale extends Model
{
    protected $table = 'ressources_medicales';
    protected $primaryKey = 'id_ressource';

    protected $fillable = [
        'titre',
        'description',
        'auteur',
        'type',
        'categorie',
        'niveau',
        'nom_fichier',
        'chemin_fichier',
        'type_fichier',
        'taille_fichier',
        'nombre_telechargements',
        'nombre_vues',
        'est_public',
        'ajoute_par'
    ];

    protected $casts = [
        'est_public' => 'boolean',
        'taille_fichier' => 'integer',
        'nombre_telechargements' => 'integer',
        'nombre_vues' => 'integer',
    ];

    /**
     * Relation : Une ressource est ajoutée par un utilisateur
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'ajoute_par', 'id_utilisateur');
    }

    /**
     * 🆕 Relation : Une ressource a plusieurs likes
     */
    public function likes()
    {
        return $this->hasMany(RessourceLike::class, 'ressource_id', 'id_ressource');
    }

    /**
     * 🆕 Vérifier si un utilisateur a liké cette ressource
     */
    public function estLikePar($utilisateurId)
    {
        return $this->likes()->where('utilisateur_id', $utilisateurId)->exists();
    }

    /**
     * 🆕 Compter le nombre total de likes
     */
    public function getNombreLikesAttribute()
    {
        return $this->likes()->count();
    }

    /**
     * Incrémenter le nombre de téléchargements
     */
    public function incrementerTelechargements()
    {
        $this->increment('nombre_telechargements');
    }

    /**
     * Incrémenter le nombre de vues
     */
    public function incrementerVues()
    {
        $this->increment('nombre_vues');
    }

    /**
     * Obtenir la taille du fichier formatée (en Ko, Mo, Go)
     */
    public function getTailleFormateeAttribute()
    {
        $taille = $this->taille_fichier;
        
        if ($taille < 1024) {
            return $taille . ' octets';
        } elseif ($taille < 1048576) {
            return round($taille / 1024, 2) . ' Ko';
        } elseif ($taille < 1073741824) {
            return round($taille / 1048576, 2) . ' Mo';
        } else {
            return round($taille / 1073741824, 2) . ' Go';
        }
    }

    /**
     * Scope : Ressources publiques uniquement
     */
    public function scopePubliques($query)
    {
        return $query->where('est_public', true);
    }

    /**
     * Scope : Filtrer par type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope : Filtrer par catégorie
     */
    public function scopeCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    /**
     * Scope : Recherche par titre ou description
     */
    public function scopeRecherche($query, $recherche)
    {
        return $query->where(function($q) use ($recherche) {
            $q->where('titre', 'like', '%' . $recherche . '%')
              ->orWhere('description', 'like', '%' . $recherche . '%')
              ->orWhere('auteur', 'like', '%' . $recherche . '%');
        });
    }
}