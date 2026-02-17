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
        Schema::table('ressources_medicales', function (Blueprint $table) {
            // ✅ Ajouter la colonne nombre_vues après nombre_telechargements
            $table->integer('nombre_vues')->default(0)->after('nombre_telechargements');
            
            // ✅ Ajouter un index pour optimiser les requêtes
            $table->index('nombre_vues');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::table('ressources_medicales', function (Blueprint $table) {
            $table->dropIndex(['nombre_vues']);
            $table->dropColumn('nombre_vues');
        });
    }
};