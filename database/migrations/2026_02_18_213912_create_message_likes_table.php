<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_message');
            $table->unsignedBigInteger('id_utilisateur');
            $table->timestamps();

            $table->foreign('id_message')
                  ->references('id_message')
                  ->on('messages')
                  ->onDelete('cascade');

            $table->foreign('id_utilisateur')
                  ->references('id_utilisateur')
                  ->on('utilisateurs')
                  ->onDelete('cascade');

            // ✅ Empêcher qu'un utilisateur like 2 fois le même message
            $table->unique(['id_message', 'id_utilisateur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_likes');
    }
};