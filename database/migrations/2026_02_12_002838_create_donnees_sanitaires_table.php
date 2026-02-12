<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up(): void
    {
        Schema::create('donnees_sanitaires', function (Blueprint $table) {
            $table->bigIncrements('id_donnee');
            
            // Informations anonymisées du patient
            $table->string('code_patient')->unique(); // Code anonyme généré automatiquement
            $table->enum('sexe', ['M', 'F', 'Autre'])->nullable();
            $table->integer('age')->nullable(); // Âge au lieu de date de naissance
            $table->string('tranche_age')->nullable(); // 0-5, 6-12, 13-18, 19-35, 36-60, 60+
            
            // Localisation
            $table->string('quartier')->nullable();
            $table->string('commune')->nullable();
            $table->string('ville')->default('Bangui');
            $table->string('coordonnees_gps')->nullable(); // Format: "latitude,longitude"
            
            // Informations médicales
            $table->string('pathologie'); // Maladie ou symptôme principal
            $table->text('symptomes')->nullable(); // Liste des symptômes
            $table->enum('gravite', ['leger', 'modere', 'grave', 'critique'])->default('modere');
            $table->date('date_debut_symptomes')->nullable();
            $table->date('date_consultation');
            
            // Diagnostic et traitement
            $table->text('diagnostic')->nullable();
            $table->text('traitement_prescrit')->nullable();
            $table->enum('statut', ['en_cours', 'guerison', 'decede', 'suivi_perdu'])->default('en_cours');
            
            // Facteurs de risque
            $table->boolean('antecedents_medicaux')->default(false);
            $table->text('antecedents_details')->nullable();
            $table->boolean('vaccination_a_jour')->nullable();
            
            // Métadonnées
            $table->text('notes')->nullable(); // Observations complémentaires
            $table->boolean('est_anonyme')->default(true); // Toujours true pour respecter confidentialité
            
            // Qui a collecté la donnée
            $table->unsignedBigInteger('collecte_par');
            $table->foreign('collecte_par')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');
            
            $table->timestamps();
            
            // Index pour recherche et statistiques rapides
            $table->index('pathologie');
            $table->index('ville');
            $table->index('commune');
            $table->index('tranche_age');
            $table->index('sexe');
            $table->index('gravite');
            $table->index('date_consultation');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::dropIfExists('donnees_sanitaires');
    }
};