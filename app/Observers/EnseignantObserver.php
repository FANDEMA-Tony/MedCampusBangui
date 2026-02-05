<?php

namespace App\Observers;

use App\Models\Enseignant;
use Illuminate\Support\Str;

class EnseignantObserver
{
    public function creating(Enseignant $enseignant)
    {
        $baseMatricule = $this->generateMatricule(
            $enseignant->nom,
            $enseignant->prenom,
            $enseignant->specialite,
            $enseignant->date_naissance
        );

        $matricule = $baseMatricule;
        $i = 1;

        // Vérifier collisions
        while (Enseignant::where('matricule', $matricule)->exists()) {
            $matricule = $baseMatricule . $i;
            $i++;
        }

        $enseignant->matricule = $matricule;
    }

    private function generateMatricule($nom, $prenom, $specialite, $dateNaissance)
    {
        $nom        = Str::upper(Str::ascii(substr($nom ?: "XXX", 0, 3)));
        $prenom     = Str::upper(Str::ascii(substr($prenom ?: "XXX", 0, 3)));
        $specialite = Str::upper(Str::ascii(substr($specialite ?: "XXX", 0, 3)));
        $date       = $dateNaissance ? date('Ymd', strtotime($dateNaissance)) : "00000000";

        return $nom.$prenom.$specialite.$date;
    }
}
