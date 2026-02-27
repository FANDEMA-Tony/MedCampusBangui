<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendrier_evenements', function (Blueprint $table) {
            $table->id('id_evenement');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('type', ['cours', 'examen', 'evenement', 'conge', 'reunion'])->default('evenement');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->string('lieu')->nullable();
            $table->string('couleur', 7)->default('#0066CC'); // Hex color
            $table->enum('visibilite', ['tous', 'filiere', 'niveau'])->default('tous');
            $table->string('filiere')->nullable();
            $table->string('niveau')->nullable();
            $table->boolean('est_important')->default(false);
            $table->unsignedBigInteger('id_createur'); // id_utilisateur
            $table->enum('role_createur', ['admin', 'enseignant']);
            $table->timestamps();

            $table->foreign('id_createur')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendrier_evenements');
    }
};
