<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            $table->string('filiere')->nullable()->after('description');
            $table->enum('niveau', ['L1', 'L2', 'L3', 'M1', 'M2', 'Doctorat'])->nullable()->after('filiere');
            
            // Index pour optimiser les requêtes groupées
            $table->index(['filiere', 'niveau']);
        });
    }

    public function down(): void
    {
        Schema::table('cours', function (Blueprint $table) {
            $table->dropIndex(['filiere', 'niveau']);
            $table->dropColumn(['filiere', 'niveau']);
        });
    }
};