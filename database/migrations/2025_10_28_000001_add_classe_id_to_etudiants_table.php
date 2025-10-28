<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            if (!Schema::hasColumn('etudiants', 'classe_id')) {
                $table->foreignId('classe_id')->nullable()->after('annee')->constrained('classes')->nullOnDelete();
            }
            // Index utile pour les recherches/assignations
            if (!Schema::hasColumn('etudiants', 'groupe')) {
                // Si le champ groupe n'existe pas chez etudiants, on ne l'ajoute pas automatiquement.
            }
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            if (Schema::hasColumn('etudiants', 'classe_id')) {
                $table->dropForeign(['classe_id']);
                $table->dropColumn('classe_id');
            }
        });
    }
};
