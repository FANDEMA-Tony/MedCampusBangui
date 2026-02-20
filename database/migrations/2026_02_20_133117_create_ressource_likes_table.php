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
        Schema::create('ressource_likes', function (Blueprint $table) {
            $table->id();
            
            // Ressource likée
            $table->unsignedBigInteger('ressource_id');
            $table->foreign('ressource_id')
                  ->references('id_ressource')
                  ->on('ressources_medicales')
                  ->onDelete('cascade');
            
            // Utilisateur qui a liké
            $table->unsignedBigInteger('utilisateur_id');
            $table->foreign('utilisateur_id')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');
            
            $table->timestamps();
            
            // Un utilisateur ne peut liker qu'une seule fois une ressource
            $table->unique(['ressource_id', 'utilisateur_id']);
            
            // Index pour performances
            $table->index('ressource_id');
            $table->index('utilisateur_id');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::dropIfExists('ressource_likes');
    }
};