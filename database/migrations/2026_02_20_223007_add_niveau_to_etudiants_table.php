<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->enum('niveau', ['L1', 'L2', 'L3', 'M1', 'M2', 'Doctorat'])
                  ->default('L1')
                  ->after('filiere'); // Ajouter après la colonne filiere
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->dropColumn('niveau');
        });
    }
};