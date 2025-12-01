<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained('etudiants');
            $table->foreignId('enseignant_id')->constrained('enseignants');
            $table->foreignId('classe_id')->nullable()->constrained('classes');
            $table->string('matiere', 100)->nullable();
            $table->decimal('valeur', 5, 2);
            $table->string('type_controle', 100);
            $table->date('date');
            $table->text('commentaire')->nullable();
            $table->boolean('est_valide')->default(false);
            $table->string('periode', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
