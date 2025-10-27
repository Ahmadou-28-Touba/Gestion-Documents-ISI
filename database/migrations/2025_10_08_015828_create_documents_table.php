<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modele_document_id')->nullable()->constrained('modele_documents')->onDelete('set null');
            $table->foreignId('etudiant_id')->nullable()->constrained('etudiants')->onDelete('cascade');
            $table->foreignId('administrateur_id')->constrained('administrateurs')->onDelete('cascade');
            $table->enum('type', ['attestation_scolarite', 'attestation_reussite', 'bulletin_notes', 'emploi_temps', 'certificat_scolarite', 'document_administratif']);
            $table->string('nom');
            $table->string('chemin_fichier');
            $table->timestamp('date_generation')->useCurrent();
            $table->boolean('est_public')->default(false);
            $table->json('donnees_document')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
