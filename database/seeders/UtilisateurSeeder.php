<?php

namespace Database\Seeders;

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use App\Models\ModeleDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UtilisateurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur
        $admin = Utilisateur::create([
            'nom' => 'Admin',
            'prenom' => 'Système',
            'email' => 'admin@isi.com',
            'password' => Hash::make('password'),
            'role' => 'administrateur',
        ]);

        Administrateur::create([
            'utilisateur_id' => $admin->id,
            'departement' => 'Informatique',
            'date_embauche' => now()->subYear(),
        ]);

        // Créer un directeur
        $directeur = Utilisateur::create([
            'nom' => 'Directeur',
            'prenom' => 'ISI',
            'email' => 'directeur@isi.com',
            'password' => Hash::make('password'),
            'role' => 'directeur',
        ]);

        Directeur::create([
            'utilisateur_id' => $directeur->id,
            'signature' => 'Directeur ISI',
        ]);

        // Créer des enseignants
        $enseignants = [
            ['nom' => 'Dupont', 'prenom' => 'Jean', 'email' => 'jean.dupont@isi.com', 'matricule' => 'ENS001', 'departement' => 'Informatique', 'matieres' => ['Programmation', 'Base de données']],
            ['nom' => 'Martin', 'prenom' => 'Marie', 'email' => 'marie.martin@isi.com', 'matricule' => 'ENS002', 'departement' => 'Mathématiques', 'matieres' => ['Algèbre', 'Statistiques']],
            ['nom' => 'Bernard', 'prenom' => 'Pierre', 'email' => 'pierre.bernard@isi.com', 'matricule' => 'ENS003', 'departement' => 'Informatique', 'matieres' => ['Réseaux', 'Sécurité']],
        ];

        foreach ($enseignants as $ens) {
            $enseignant = Utilisateur::create([
                'nom' => $ens['nom'],
                'prenom' => $ens['prenom'],
                'email' => $ens['email'],
                'password' => Hash::make('password'),
                'role' => 'enseignant',
            ]);

            Enseignant::create([
                'utilisateur_id' => $enseignant->id,
                'matricule' => $ens['matricule'],
                'matieres_enseignees' => $ens['matieres'],
                'departement' => $ens['departement'],
                'bureau' => 'Bureau ' . $ens['matricule'],
            ]);
        }

        // Créer des étudiants
        $etudiants = [
            ['nom' => 'Student', 'prenom' => 'Alice', 'email' => 'alice.student@isi.com', 'numero' => 'ETU001', 'filiere' => 'Informatique', 'annee' => '2024'],
            ['nom' => 'Student', 'prenom' => 'Bob', 'email' => 'bob.student@isi.com', 'numero' => 'ETU002', 'filiere' => 'Informatique', 'annee' => '2024'],
            ['nom' => 'Student', 'prenom' => 'Charlie', 'email' => 'charlie.student@isi.com', 'numero' => 'ETU003', 'filiere' => 'Mathématiques', 'annee' => '2024'],
            ['nom' => 'Student', 'prenom' => 'Diana', 'email' => 'diana.student@isi.com', 'numero' => 'ETU004', 'filiere' => 'Informatique', 'annee' => '2023'],
        ];

        foreach ($etudiants as $etu) {
            $etudiant = Utilisateur::create([
                'nom' => $etu['nom'],
                'prenom' => $etu['prenom'],
                'email' => $etu['email'],
                'password' => Hash::make('password'),
                'role' => 'etudiant',
            ]);

            Etudiant::create([
                'utilisateur_id' => $etudiant->id,
                'numero_etudiant' => $etu['numero'],
                'filiere' => $etu['filiere'],
                'annee' => $etu['annee'],
                'date_inscription' => now()->subMonths(rand(1, 12)),
            ]);
        }

        // Créer des modèles de documents
        $modeles = [
            [
                'nom' => 'Attestation de scolarité',
                'type_document' => 'attestation_scolarite',
                'chemin_modele' => 'templates/attestation_scolarite.html',
                'champs_requis' => ['nom_etudiant', 'prenom_etudiant', 'numero_etudiant', 'filiere', 'annee', 'date_emission'],
                'description' => 'Modèle pour les attestations de scolarité',
            ],
            [
                'nom' => 'Bulletin de notes',
                'type_document' => 'bulletin_notes',
                'chemin_modele' => 'templates/bulletin_notes.html',
                'champs_requis' => ['nom_etudiant', 'prenom_etudiant', 'numero_etudiant', 'filiere', 'annee', 'notes', 'moyenne'],
                'description' => 'Modèle pour les bulletins de notes',
            ],
            [
                'nom' => 'Attestation de réussite',
                'type_document' => 'attestation_reussite',
                'chemin_modele' => 'templates/attestation_reussite.html',
                'champs_requis' => ['nom_etudiant', 'prenom_etudiant', 'numero_etudiant', 'filiere', 'annee', 'mention'],
                'description' => 'Modèle pour les attestations de réussite',
            ],
            [
                'nom' => 'Emploi du temps',
                'type_document' => 'emploi_temps',
                'chemin_modele' => 'templates/emploi_temps.html',
                'champs_requis' => ['filiere', 'annee', 'semestre', 'cours', 'horaires'],
                'description' => 'Modèle pour les emplois du temps',
            ],
        ];

        foreach ($modeles as $modele) {
            ModeleDocument::create($modele);
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('Comptes de test :');
        $this->command->info('- Admin: admin@isi.com / password');
        $this->command->info('- Directeur: directeur@isi.com / password');
        $this->command->info('- Enseignants: jean.dupont@isi.com, marie.martin@isi.com, pierre.bernard@isi.com / password');
        $this->command->info('- Étudiants: alice.student@isi.com, bob.student@isi.com, charlie.student@isi.com, diana.student@isi.com / password');
    }
}
