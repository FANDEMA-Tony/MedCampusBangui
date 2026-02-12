<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

// 👉 Modèles et Observers
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Cours;
use App\Models\Note;
use App\Observers\EtudiantObserver;
use App\Observers\EnseignantObserver;
use App\Models\RessourceMedicale;
use App\Policies\RessourceMedicalePolicy;
use App\Models\DonneeSanitaire;
use App\Policies\DonneeSanitairePolicy;
use App\Models\Message;
use App\Policies\MessagePolicy;

// 👉 Policies
use App\Policies\EtudiantPolicy;
use App\Policies\EnseignantPolicy;
use App\Policies\CoursPolicy;
use App\Policies\NotePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🔹 Enregistrement des observers
        Etudiant::observe(EtudiantObserver::class);
        Enseignant::observe(EnseignantObserver::class);

        // 🔹 Enregistrement des policies
        Gate::policy(Etudiant::class, EtudiantPolicy::class);
        Gate::policy(Enseignant::class, EnseignantPolicy::class);
        Gate::policy(Cours::class, CoursPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(RessourceMedicale::class, RessourceMedicalePolicy::class);  // 🔹 AJOUT DE CETTE LIGNE
        Gate::policy(DonneeSanitaire::class, DonneeSanitairePolicy::class);  // 🔹 AJOUT DE CETTE LIGNE
        Gate::policy(Message::class, MessagePolicy::class);  // 🔹 AJOUT DE CETTE LIGNE
    }
}