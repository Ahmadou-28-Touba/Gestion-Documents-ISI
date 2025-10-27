<?php

/**
 * Script de création d'utilisateurs de test pour l'application
 * Gestion Documents ISI - Version corrigée
 */

require_once 'vendor/autoload.php';

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use App\Models\ModeleDocument;
use Illuminate\Support\Facades\Hash;

echo "👥 CRÉATION DES UTILISATEURS DE TEST\n";
echo "====================================\n\n";

try {
    // Créer un utilisateur étudiant
    echo "1. Création de l'étudiant de test...\n";
    
    $etudiantUser = Utilisateur::create([
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'email' => 'etudiant@isi.com',
        'password' => Hash::make('password123'),
        'role' => 'etudiant',
    ]);
    
    $etudiant = Etudiant::create([
        'utilisateur_id' => $etudiantUser->id,
        'numero_etudiant' => 'ETU001',
        'filiere' => 'Informatique',
        'annee' => '2024',
        'date_inscription' => now(),
    ]);
    
    echo "   ✅ Étudiant créé: {$etudiantUser->email}\n";
    
    // Créer un utilisateur enseignant
    echo "\n2. Création de l'enseignant de test...\n";
    
    $enseignantUser = Utilisateur::create([
        'nom' => 'Martin',
        'prenom' => 'Pierre',
        'email' => 'enseignant@isi.com',
        'password' => Hash::make('password123'),
        'role' => 'enseignant',
    ]);
    
    $enseignant = Enseignant::create([
        'utilisateur_id' => $enseignantUser->id,
        'matricule' => 'ENS001',
        'matieres_enseignees' => ['Programmation', 'Base de données'],
        'bureau' => 'Bureau 201',
        'departement' => 'Informatique',
    ]);
    
    echo "   ✅ Enseignant créé: {$enseignantUser->email}\n";
    
    // Créer un utilisateur administrateur
    echo "\n3. Création de l'administrateur de test...\n";
    
    $adminUser = Utilisateur::create([
        'nom' => 'Admin',
        'prenom' => 'Admin',
        'email' => 'admin@isi.com',
        'password' => Hash::make('password123'),
        'role' => 'administrateur',
    ]);
    
    $admin = Administrateur::create([
        'utilisateur_id' => $adminUser->id,
        'departement' => 'Administration',
        'date_embauche' => now(),
    ]);
    
    echo "   ✅ Administrateur créé: {$adminUser->email}\n";
    
    // Créer un utilisateur directeur
    echo "\n4. Création du directeur de test...\n";
    
    $directeurUser = Utilisateur::create([
        'nom' => 'Directeur',
        'prenom' => 'Directeur',
        'email' => 'directeur@isi.com',
        'password' => Hash::make('password123'),
        'role' => 'directeur',
    ]);
    
    $directeur = Directeur::create([
        'utilisateur_id' => $directeurUser->id,
        'signature' => 'Signature du Directeur',
    ]);
    
    echo "   ✅ Directeur créé: {$directeurUser->email}\n";
    
    // Créer des modèles de documents
    echo "\n5. Création des modèles de documents...\n";
    
    $modeles = [
        [
            'nom' => 'Attestation de scolarité',
            'type_document' => 'attestation_scolarite',
            'contenu_template' => '<h1>Attestation de Scolarité</h1><p>Je soussigné(e), certifie que {{nom}} {{prenom}} est bien inscrit(e) en {{filiere}} pour l\'année {{annee}}.</p>',
            'est_actif' => true,
        ],
        [
            'nom' => 'Bulletin de notes',
            'type_document' => 'bulletin_notes',
            'contenu_template' => '<h1>Bulletin de Notes</h1><p>Étudiant: {{nom}} {{prenom}}</p><p>Filière: {{filiere}}</p><p>Année: {{annee}}</p>',
            'est_actif' => true,
        ],
        [
            'nom' => 'Certificat de scolarité',
            'type_document' => 'certificat_scolarite',
            'contenu_template' => '<h1>Certificat de Scolarité</h1><p>Je certifie que {{nom}} {{prenom}} est bien scolarisé(e) dans notre établissement.</p>',
            'est_actif' => true,
        ],
    ];
    
    foreach ($modeles as $modeleData) {
        $modele = ModeleDocument::create($modeleData);
        echo "   ✅ Modèle créé: {$modele->nom}\n";
    }
    
    echo "\n🎉 CRÉATION TERMINÉE AVEC SUCCÈS!\n";
    echo "==================================\n";
    echo "Comptes de test créés:\n";
    echo "- Étudiant: etudiant@isi.com / password123\n";
    echo "- Enseignant: enseignant@isi.com / password123\n";
    echo "- Administrateur: admin@isi.com / password123\n";
    echo "- Directeur: directeur@isi.com / password123\n";
    echo "\nVous pouvez maintenant tester l'application!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR LORS DE LA CRÉATION:\n";
    echo "=============================\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
    echo "\nVérifiez que:\n";
    echo "1. La base de données est configurée\n";
    echo "2. Les migrations ont été exécutées\n";
    echo "3. Le fichier .env est correctement configuré\n";
}
