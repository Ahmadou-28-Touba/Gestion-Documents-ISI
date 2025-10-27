<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use App\Models\ModeleDocument;
use App\Models\Document;
use App\Models\Absence;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des utilisateurs de test pour chaque rôle
        
        // Étudiant
        $etudiantUser = Utilisateur::create([
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'etudiant@isi.com',
            'password' => Hash::make('password123'),
            'role' => 'etudiant'
        ]);
        
        Etudiant::create([
            'utilisateur_id' => $etudiantUser->id,
            'numero_etudiant' => 'ETU2024001',
            'filiere' => 'Informatique',
            'annee' => '2024',
            'date_inscription' => '2024-09-01'
        ]);
        
        // Enseignant
        $enseignantUser = Utilisateur::create([
            'nom' => 'Martin',
            'prenom' => 'Pierre',
            'email' => 'enseignant@isi.com',
            'password' => Hash::make('password123'),
            'role' => 'enseignant'
        ]);
        
        Enseignant::create([
            'utilisateur_id' => $enseignantUser->id,
            'matricule' => 'ENS2024001',
            'departement' => 'Informatique',
            'bureau' => 'Bureau 101',
            'matieres_enseignees' => ['Programmation', 'Base de données']
        ]);
        
        // Administrateur
        $adminUser = Utilisateur::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@isi.com',
            'password' => Hash::make('password123'),
            'role' => 'administrateur'
        ]);
        
        Administrateur::create([
            'utilisateur_id' => $adminUser->id,
            'departement' => 'Administration',
            'date_embauche' => '2020-01-01'
        ]);
        
        // Directeur
        $directeurUser = Utilisateur::create([
            'nom' => 'Directeur',
            'prenom' => 'ISI',
            'email' => 'directeur@isi.com',
            'password' => Hash::make('password123'),
            'role' => 'directeur'
        ]);
        
        Directeur::create([
            'utilisateur_id' => $directeurUser->id,
            'signature' => 'Directeur de l\'Institut Supérieur d\'Informatique'
        ]);
        
        // Créer des modèles de documents
        ModeleDocument::create([
            'nom' => 'Attestation de scolarité',
            'type_document' => 'attestation_scolarite',
            'chemin_modele' => 'templates/attestation_scolarite.docx',
            'champs_requis' => ['nom', 'prenom', 'filiere', 'annee'],
            'description' => 'Modèle d\'attestation de scolarité',
            'est_actif' => true
        ]);
        
        ModeleDocument::create([
            'nom' => 'Attestation de réussite',
            'type_document' => 'attestation_reussite',
            'chemin_modele' => 'templates/attestation_reussite.docx',
            'champs_requis' => ['nom', 'prenom', 'filiere', 'moyenne'],
            'description' => 'Modèle d\'attestation de réussite',
            'est_actif' => true
        ]);
        
        // Créer quelques documents de test
        Document::create([
            'utilisateur_id' => $etudiantUser->id,
            'modele_document_id' => 1,
            'type' => 'attestation_scolarite',
            'nom' => 'Attestation de scolarité - Jean Dupont',
            'chemin_fichier' => 'documents/attestation_scolarite/2024/1/attestation_jean_dupont.pdf',
            'donnees' => [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'filiere' => 'Informatique',
                'annee' => '2024'
            ],
            'est_public' => true,
            'date_generation' => now()
        ]);
        
        // Créer quelques absences de test
        Absence::create([
            'etudiant_id' => 1,
            'enseignant_id' => 1,
            'date_debut' => '2024-01-15',
            'date_fin' => '2024-01-16',
            'motif' => 'Rendez-vous médical',
            'statut' => 'en_attente',
            'date_declaration' => now()
        ]);
        
        Absence::create([
            'etudiant_id' => 1,
            'enseignant_id' => 1,
            'date_debut' => '2024-01-20',
            'date_fin' => '2024-01-21',
            'motif' => 'Problème de transport',
            'statut' => 'validee',
            'date_declaration' => now()->subDays(5),
            'date_traitement' => now()->subDays(3),
            'commentaire_enseignant' => 'Absence justifiée'
        ]);
        
        $this->command->info('Données de test créées avec succès !');
        $this->command->info('Comptes de test :');
        $this->command->info('- Étudiant: etudiant@isi.com / password123');
        $this->command->info('- Enseignant: enseignant@isi.com / password123');
        $this->command->info('- Administrateur: admin@isi.com / password123');
        $this->command->info('- Directeur: directeur@isi.com / password123');
    }
}