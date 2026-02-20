<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donnees_sanitaires', function (Blueprint $table) {
            // ✅ IDENTITÉ PATIENT (Optionnel)
            $table->string('nom_patient')->nullable()->after('code_patient');
            $table->string('prenom_patient')->nullable()->after('nom_patient');
            $table->string('telephone_patient')->nullable()->after('prenom_patient');
            
            // ✅ INDEX pour recherche rapide
            $table->index('code_patient'); // Recherche par code
            $table->index(['nom_patient', 'prenom_patient']); // Recherche par nom
        });
    }

    public function down(): void
    {
        Schema::table('donnees_sanitaires', function (Blueprint $table) {
            $table->dropIndex(['donnees_sanitaires_code_patient_index']);
            $table->dropIndex(['donnees_sanitaires_nom_patient_prenom_patient_index']);
            $table->dropColumn(['nom_patient', 'prenom_patient', 'telephone_patient']);
        });
    }
};