<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// 👉 Ajoute les imports de tes modèles et observers
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Observers\EtudiantObserver;
use App\Observers\EnseignantObserver;

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
        // 👉 Enregistrement des observers
        Etudiant::observe(EtudiantObserver::class);
        Enseignant::observe(EnseignantObserver::class);
    }
}
