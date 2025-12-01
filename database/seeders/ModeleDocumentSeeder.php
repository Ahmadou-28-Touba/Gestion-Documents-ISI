<?php

namespace Database\Seeders;

use App\Models\ModeleDocument;
use Illuminate\Database\Seeder;

class ModeleDocumentSeeder extends Seeder
{
    public function run()
    {
        // Vérifier si les modèles existent déjà
        if (ModeleDocument::where('type_document', 'attestation_scolarite')->doesntExist()) {
            ModeleDocument::create([
                'nom' => 'Attestation de scolarité',
                'type_document' => 'attestation_scolarite',
                'chemin_modele' => 'modeles/attestation_scolarite.html',
                'est_actif' => true,
                'champs_requis' => ['etudiant_nom', 'etudiant_prenom', 'etudiant_filiere', 'etudiant_annee', 'date_emission'],
                'description' => 'Modèle pour les attestations de scolarité',
                'form_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'motif' => [
                            'type' => 'string',
                            'title' => 'Motif de la demande',
                            'description' => 'Précisez le motif de votre demande d\'attestation'
                        ]
                    ]
                ]
            ]);
        }

        if (ModeleDocument::where('type_document', 'bulletin_notes')->doesntExist()) {
            ModeleDocument::create([
                'nom' => 'Bulletin de notes',
                'type_document' => 'bulletin_notes',
                'chemin_modele' => 'modeles/bulletin_notes.html',
                'est_actif' => true,
                'champs_requis' => ['etudiant_nom', 'etudiant_prenom', 'etudiant_filiere', 'etudiant_annee', 'etudiant_groupe', 'annee_scolaire'],
                'description' => 'Modèle pour les bulletins de notes',
                'form_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'annee_scolaire' => [
                            'type' => 'string',
                            'title' => 'Année scolaire',
                            'description' => 'Sélectionnez l\'année scolaire'
                        ]
                    ],
                    'required' => ['annee_scolaire']
                ]
            ]);
        }
    }
}
