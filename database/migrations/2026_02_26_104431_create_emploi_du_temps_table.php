<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emploi_du_temps', function (Blueprint $table) {
            $table->id('id_emploi');
            $table->unsignedBigInteger('id_cours');
            $table->enum('jour_semaine', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('salle')->nullable();
            $table->string('filiere');
            $table->string('niveau');
            $table->string('semestre'); // S1, S2, etc.
            $table->boolean('est_actif')->default(true);
            $table->timestamps();

            $table->foreign('id_cours')
                  ->references('id_cours')
                  ->on('cours')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emploi_du_temps');
    }
};
