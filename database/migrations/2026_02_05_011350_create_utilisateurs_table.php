<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id('id_utilisateur');
            $table->string('nom');
            $table->string('email')->unique();
            $table->string('mot_de_passe');
            $table->enum('role', ['admin', 'enseignant', 'etudiant', 'invite']);
            $table->enum('statut', ['actif', 'suspendu', 'supprime'])->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilisateurs');
    }
};
