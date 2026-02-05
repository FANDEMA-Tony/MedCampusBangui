<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->bigIncrements('id_etudiant');
            $table->string('matricule')->unique(); // généré automatiquement
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('filiere');
            $table->date('date_naissance')->nullable();
            $table->enum('statut', ['actif', 'suspendu', 'diplome'])->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
