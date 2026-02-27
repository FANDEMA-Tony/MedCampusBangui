<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examens', function (Blueprint $table) {
            $table->id('id_examen');
            $table->unsignedBigInteger('id_cours');
            $table->string('titre');
            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('salle')->nullable();
            $table->integer('duree_minutes');
            $table->enum('type_session', ['normale', 'rattrapage'])->default('normale');
            $table->string('filiere');
            $table->string('niveau');
            $table->string('semestre');
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->foreign('id_cours')
                  ->references('id_cours')
                  ->on('cours')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examens');
    }
};
