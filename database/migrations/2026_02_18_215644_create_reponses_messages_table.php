<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reponses_messages', function (Blueprint $table) {
            $table->id('id_reponse');
            $table->unsignedBigInteger('id_message'); // Message parent
            $table->unsignedBigInteger('id_utilisateur'); // Qui répond
            $table->text('contenu'); // Texte de la réponse
            $table->timestamps();

            $table->foreign('id_message')
                  ->references('id_message')
                  ->on('messages')
                  ->onDelete('cascade');

            $table->foreign('id_utilisateur')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_messages');
    }
};