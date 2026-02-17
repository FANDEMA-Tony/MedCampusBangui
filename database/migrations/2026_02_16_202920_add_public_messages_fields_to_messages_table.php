<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Rendre destinataire_id nullable (pour messages publics)
            $table->unsignedBigInteger('destinataire_id')->nullable()->change();
            
            // Type de message
            $table->enum('type', ['prive', 'annonce', 'forum'])->default('prive')->after('destinataire_id');
            
            // Visibilité pour annonces
            $table->enum('visibilite', ['tous', 'enseignants', 'etudiants', 'cours'])->nullable()->after('type');
            
            // Cours concerné (pour annonces de cours)
            $table->unsignedBigInteger('id_cours')->nullable()->after('visibilite');
            $table->foreign('id_cours')->references('id_cours')->on('cours')->onDelete('cascade');
            
            // Épinglé (pour mettre en avant certaines annonces)
            $table->boolean('est_epingle')->default(false)->after('est_lu');
            
            // Nombre de vues (pour annonces/forum)
            $table->integer('nombre_vues')->default(0)->after('est_epingle');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['id_cours']);
            $table->dropColumn(['type', 'visibilite', 'id_cours', 'est_epingle', 'nombre_vues']);
            $table->unsignedBigInteger('destinataire_id')->nullable(false)->change();
        });
    }
};