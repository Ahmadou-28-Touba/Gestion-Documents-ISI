<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModeleDocument;

class AddBasicTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Attestation (HTML)
        ModeleDocument::updateOrCreate(
            ['type_document' => 'attestation_scolarite'],
            [
                'nom' => 'Attestation de scolarité',
                'chemin_modele' => 'templates/attestation.html',
                'champs_requis' => ['etudiant.nom','etudiant.prenom','etudiant.numero_etudiant','etudiant.filiere','etudiant.annee','date_du_jour'],
                'description' => 'Modèle HTML rapide pour attestation',
                'est_actif' => true,
            ]
        );

        // Certificat (HTML) - utilisera une structure similaire
        ModeleDocument::updateOrCreate(
            ['type_document' => 'certificat_scolarite'],
            [
                'nom' => 'Certificat de scolarité',
                'chemin_modele' => 'templates/certificat.html',
                'champs_requis' => ['etudiant.nom','etudiant.prenom','etudiant.numero_etudiant','etudiant.filiere','etudiant.annee','date_du_jour'],
                'description' => 'Modèle HTML rapide pour certificat',
                'est_actif' => true,
            ]
        );
    }
}
