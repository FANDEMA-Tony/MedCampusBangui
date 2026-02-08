<?php

namespace App\Observers;

use App\Models\Etudiant;
use Illuminate\Support\Str;

class EtudiantObserver
{
    /**
     * Génère automatiquement le matricule avant la création
     */
    public function creating(Etudiant $etudiant)
    {
        // Générer le matricule de base
        $baseMatricule = $this->generateMatricule(
            $etudiant->nom,
            $etudiant->prenom,
            $etudiant->filiere,
            $etudiant->date_naissance
        );

        $matricule = $baseMatricule;
        $counter = 1;

        // Gérer les collisions (si matricule existe déjà)
        while (Etudiant::where('matricule', $matricule)->exists()) {
            $matricule = $baseMatricule . $counter;
            $counter++;
        }

        $etudiant->matricule = $matricule;
    }

    /**
     * Génère un matricule au format : [NOM3][PRENOM3][FILIERE3][YYYYMMDD]
     * Exemple : DUPJEANMED19950315
     */
    private function generateMatricule($nom, $prenom, $filiere, $dateNaissance)
    {
        // Extraire 3 caractères de chaque champ (avec padding si nécessaire)
        $nom = $this->formatCode($nom);
        $prenom = $this->formatCode($prenom);
        $filiere = $this->formatCode($filiere);
        
        // Formater la date au format YYYYMMDD
        $date = $dateNaissance 
            ? date('Ymd', strtotime($dateNaissance)) 
            : '00000000';

        return $nom . $prenom . $filiere . $date;
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