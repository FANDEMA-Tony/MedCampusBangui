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
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id_message');
            
            // Expéditeur et destinataire
            $table->unsignedBigInteger('expediteur_id');
            $table->unsignedBigInteger('destinataire_id');
            
            $table->foreign('expediteur_id')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');
                  
            $table->foreign('destinataire_id')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');
            
            // Contenu du message
            $table->string('sujet')->nullable();
            $table->text('contenu');
            
            // Statut
            $table->boolean('est_lu')->default(false);
            $table->timestamp('lu_a')->nullable();
            
            $table->timestamps();
            
            // Index pour recherche rapide
            $table->index('expediteur_id');
            $table->index('destinataire_id');
            $table->index('est_lu');
            $table->index('created_at');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};