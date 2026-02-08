<?php

namespace App\Observers;

use App\Models\Enseignant;
use Illuminate\Support\Str;

class EnseignantObserver
{
    /**
     * Génère automatiquement le matricule avant la création
     */
    public function creating(Enseignant $enseignant)
    {
        // Générer le matricule de base
        $baseMatricule = $this->generateMatricule(
            $enseignant->nom,
            $enseignant->prenom,
            $enseignant->specialite,
            $enseignant->date_naissance
        );

        $matricule = $baseMatricule;
        $counter = 1;

        // Gérer les collisions (si matricule existe déjà)
        while (Enseignant::where('matricule', $matricule)->exists()) {
            $matricule = $baseMatricule . $counter;
            $counter++;
        }

        $enseignant->matricule = $matricule;
    }

    /**
     * Génère un matricule au format : [NOM3][PRENOM3][SPECIALITE3][YYYYMMDD]
     * Exemple : DUPJEANCHI19750810
     */
    private function generateMatricule($nom, $prenom, $specialite, $dateNaissance)
    {
        // Extraire 3 caractères de chaque champ (avec padding si nécessaire)
        $nom = $this->formatCode($nom);
        $prenom = $this->formatCode($prenom);
        $specialite = $this->formatCode($specialite);
        
        // Formater la date au format YYYYMMDD
        $date = $dateNaissance 
            ? date('Ymd', strtotime($dateNaissance)) 
            : '00000000';

        return $nom . $prenom . $specialite . $date;
    }

    /**
     * Formate un texte en 3 caractères majuscules sans accent
     */
    private function formatCode($text)
    {
        // Valeur par défaut si vide
        if (empty($text)) {
            return 'XXX';
        }

        // Supprimer les accents et convertir en majuscules
        $clean = Str::upper(Str::ascii($text));
        
        // Prendre les 3 premiers caractères
        $code = substr($clean, 0, 3);
        
        // Compléter avec 'X' si moins de 3 caractères
        return str_pad($code, 3, 'X', STR_PAD_RIGHT);
    }
}