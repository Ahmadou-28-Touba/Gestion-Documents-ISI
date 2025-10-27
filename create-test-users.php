<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use Illuminate\Support\Facades\Hash;

echo "🔧 Création des utilisateurs de test...\n";

// Supprimer les anciens utilisateurs de test
Utilisateur::whereIn('email', ['etudiant@isi.com', 'enseignant@isi.com', 'admin@isi.com', 'directeur@isi.com'])->delete();

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
echo "✅ Étudiant créé\n";

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
echo "✅ Enseignant créé\n";

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
echo "✅ Administrateur créé\n";

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
echo "✅ Directeur créé\n";

echo "\n🎉 Tous les utilisateurs de test créés !\n";
echo "Comptes de test :\n";
echo "- Étudiant: etudiant@isi.com / password123\n";
echo "- Enseignant: enseignant@isi.com / password123\n";
echo "- Administrateur: admin@isi.com / password123\n";
echo "- Directeur: directeur@isi.com / password123\n";
