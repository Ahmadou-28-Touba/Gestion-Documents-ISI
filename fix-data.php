<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;

echo "🔧 Correction des données...\n";

// Étudiant
$etudiantUser = Utilisateur::where('email', 'etudiant@isi.com')->first();
if ($etudiantUser && !$etudiantUser->etudiant) {
    Etudiant::create([
        'utilisateur_id' => $etudiantUser->id,
        'numero_etudiant' => 'ETU2024001',
        'filiere' => 'Informatique',
        'annee' => '2024',
        'date_inscription' => '2024-09-01'
    ]);
    echo "✅ Profil étudiant créé\n";
}

// Enseignant
$enseignantUser = Utilisateur::where('email', 'enseignant@isi.com')->first();
if ($enseignantUser && !$enseignantUser->enseignant) {
    Enseignant::create([
        'utilisateur_id' => $enseignantUser->id,
        'matricule' => 'ENS2024001',
        'departement' => 'Informatique',
        'bureau' => 'Bureau 101',
        'matieres_enseignees' => ['Programmation', 'Base de données']
    ]);
    echo "✅ Profil enseignant créé\n";
}

// Administrateur
$adminUser = Utilisateur::where('email', 'admin@isi.com')->first();
if ($adminUser && !$adminUser->administrateur) {
    Administrateur::create([
        'utilisateur_id' => $adminUser->id,
        'departement' => 'Administration',
        'date_embauche' => '2020-01-01'
    ]);
    echo "✅ Profil administrateur créé\n";
}

// Directeur
$directeurUser = Utilisateur::where('email', 'directeur@isi.com')->first();
if ($directeurUser && !$directeurUser->directeur) {
    Directeur::create([
        'utilisateur_id' => $directeurUser->id,
        'signature' => 'Directeur de l\'Institut Supérieur d\'Informatique'
    ]);
    echo "✅ Profil directeur créé\n";
}

echo "🎉 Correction terminée !\n";
