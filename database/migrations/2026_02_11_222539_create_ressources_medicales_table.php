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
        Schema::create('ressources_medicales', function (Blueprint $table) {
            $table->bigIncrements('id_ressource');
            
            // Informations de base
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('auteur')->nullable();
            
            // Type de ressource
            $table->enum('type', ['cours', 'livre', 'video', 'article', 'autre'])->default('cours');
            
            // Catégorie/matière
            $table->string('categorie')->nullable();
            
            // Niveau d'étude
            $table->enum('niveau', ['L1', 'L2', 'L3', 'M1', 'M2', 'doctorat', 'formation_continue'])->nullable();
            
            // Fichier
            $table->string('nom_fichier'); // Nom original du fichier
            $table->string('chemin_fichier'); // Chemin de stockage
            $table->string('type_fichier'); // Extension (pdf, mp4, etc.)
            $table->unsignedBigInteger('taille_fichier'); // Taille en octets
            
            // Métadonnées
            $table->integer('nombre_telechargements')->default(0);
            $table->boolean('est_public')->default(true); // Public ou restreint
            
            // Qui a ajouté la ressource
            $table->unsignedBigInteger('ajoute_par')->nullable();
            $table->foreign('ajoute_par')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('set null');
            
            $table->timestamps();
            
            // Index pour recherche rapide
            $table->index('type');
            $table->index('categorie');
            $table->index('niveau');
            $table->index('est_public');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::dropIfExists('ressources_medicales');
    }
};