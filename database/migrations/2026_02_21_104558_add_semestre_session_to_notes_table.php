<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajouter les colonnes pour le système de semestres et sessions
     */
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // Semestre académique
            $table->enum('semestre', ['S1', 'S2', 'S3', 'S4', 'S5', 'S6'])
                  ->default('S1')
                  ->after('valeur')
                  ->comment('Semestre académique (S1-S6)');
            
            // Session d'évaluation
            $table->enum('session', ['normale', 'rattrapage'])
                  ->default('normale')
                  ->after('semestre')
                  ->comment('Session d\'évaluation');
            
            // Marqueur de rattrapage réussi
            $table->boolean('est_rattrape')
                  ->default(false)
                  ->after('session')
                  ->comment('Note validée au rattrapage');
            
            // Renommer date_attribution en date_evaluation pour cohérence
            $table->renameColumn('date_attribution', 'date_evaluation');
        });
    }

    /**
     * Rollback : Supprimer les colonnes ajoutées
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(['semestre', 'session', 'est_rattrape']);
            $table->renameColumn('date_evaluation', 'date_attribution');
        });
    }
};