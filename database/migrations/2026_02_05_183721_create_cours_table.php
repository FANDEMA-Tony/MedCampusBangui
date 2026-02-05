<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cours', function (Blueprint $table) {
            $table->bigIncrements('id_cours');
            $table->string('code')->unique();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('id_enseignant');
            $table->timestamps();

            $table->foreign('id_enseignant')
                  ->references('id_enseignant')
                  ->on('enseignants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours');
    }
};
