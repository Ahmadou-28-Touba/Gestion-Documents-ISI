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
        Schema::create('modele_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('type_document', ['attestation_scolarite', 'attestation_reussite', 'bulletin_notes', 'emploi_temps', 'certificat_scolarite', 'document_administratif']);
            $table->string('chemin_modele');
            $table->boolean('est_actif')->default(true);
            $table->json('champs_requis')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modele_documents');
    }
};
