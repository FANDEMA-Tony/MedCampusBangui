<?php

namespace App\Observers;

use App\Models\Etudiant;
use Illuminate\Support\Str;

class EtudiantObserver
{
    public function creating(Etudiant $etudiant)
    {
        $baseMatricule = $this->generateMatricule(
            $etudiant->nom,
            $etudiant->prenom,
            $etudiant->filiere,
            $etudiant->date_naissance
        );

        $matricule = $baseMatricule;
        $i = 1;

        // Vérifier collisions
        while (Etudiant::where('matricule', $matricule)->exists()) {
            $matricule = $baseMatricule . $i;
            $i++;
        }

        $etudiant->matricule = $matricule;
    }

    private function generateMatricule($nom, $prenom, $filiere, $dateNaissance)
    {
        $nom      = Str::upper(Str::ascii(substr($nom ?: "XXX", 0, 3)));
        $prenom   = Str::upper(Str::ascii(substr($prenom ?: "XXX", 0, 3)));
        $filiere  = Str::upper(Str::ascii(substr($filiere ?: "XXX", 0, 3)));
        $date     = $dateNaissance ? date('Ymd', strtotime($dateNaissance)) : "00000000";

        return $nom.$prenom.$filiere.$date;
    }
}
