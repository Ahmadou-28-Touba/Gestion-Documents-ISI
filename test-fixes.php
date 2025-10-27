<?php

/**
 * Script de test pour vérifier les corrections apportées à l'application
 * Gestion Documents ISI
 */

require_once 'vendor/autoload.php';

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use App\Models\Absence;
use App\Models\Document;
use App\Models\ModeleDocument;

echo "🧪 TEST DES CORRECTIONS - GESTION DOCUMENTS ISI\n";
echo "================================================\n\n";

// Test 1: Vérification des modèles
echo "1. ✅ Vérification des modèles...\n";

try {
    $utilisateur = new Utilisateur();
    echo "   - Modèle Utilisateur: OK\n";
    
    $etudiant = new Etudiant();
    echo "   - Modèle Etudiant: OK\n";
    
    $enseignant = new Enseignant();
    echo "   - Modèle Enseignant: OK\n";
    
    $admin = new Administrateur();
    echo "   - Modèle Administrateur: OK\n";
    
    $directeur = new Directeur();
    echo "   - Modèle Directeur: OK\n";
    
    $absence = new Absence();
    echo "   - Modèle Absence: OK\n";
    
    $document = new Document();
    echo "   - Modèle Document: OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur dans les modèles: " . $e->getMessage() . "\n";
}

// Test 2: Vérification des relations
echo "\n2. ✅ Vérification des relations...\n";

try {
    // Test relation Utilisateur -> Etudiant
    $utilisateur = new Utilisateur();
    $relation = $utilisateur->etudiant();
    echo "   - Relation Utilisateur->Etudiant: OK\n";
    
    // Test relation Etudiant -> Absences
    $etudiant = new Etudiant();
    $relation = $etudiant->absences();
    echo "   - Relation Etudiant->Absences: OK\n";
    
    // Test relation Etudiant -> Documents
    $relation = $etudiant->documents();
    echo "   - Relation Etudiant->Documents: OK\n";
    
    // Test relation Absence -> Etudiant
    $absence = new Absence();
    $relation = $absence->etudiant();
    echo "   - Relation Absence->Etudiant: OK\n";
    
    // Test relation Document -> Etudiant
    $document = new Document();
    $relation = $document->etudiant();
    echo "   - Relation Document->Etudiant: OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur dans les relations: " . $e->getMessage() . "\n";
}

// Test 3: Vérification des méthodes métier
echo "\n3. ✅ Vérification des méthodes métier...\n";

try {
    // Test méthode isEtudiant
    $utilisateur = new Utilisateur();
    $utilisateur->role = 'etudiant';
    $result = $utilisateur->isEtudiant();
    echo "   - Méthode isEtudiant(): " . ($result ? "OK" : "❌") . "\n";
    
    // Test méthode isEnseignant
    $utilisateur->role = 'enseignant';
    $result = $utilisateur->isEnseignant();
    echo "   - Méthode isEnseignant(): " . ($result ? "OK" : "❌") . "\n";
    
    // Test méthode isAdministrateur
    $utilisateur->role = 'administrateur';
    $result = $utilisateur->isAdministrateur();
    echo "   - Méthode isAdministrateur(): " . ($result ? "OK" : "❌") . "\n";
    
    // Test méthode isDirecteur
    $utilisateur->role = 'directeur';
    $result = $utilisateur->isDirecteur();
    echo "   - Méthode isDirecteur(): " . ($result ? "OK" : "❌") . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur dans les méthodes métier: " . $e->getMessage() . "\n";
}

// Test 4: Vérification des scopes
echo "\n4. ✅ Vérification des scopes...\n";

try {
    // Test scope enAttente
    $absence = new Absence();
    $query = $absence->enAttente();
    echo "   - Scope enAttente(): OK\n";
    
    // Test scope validees
    $query = $absence->validees();
    echo "   - Scope validees(): OK\n";
    
    // Test scope publics
    $document = new Document();
    $query = $document->publics();
    echo "   - Scope publics(): OK\n";
    
    // Test scope parType
    $query = $document->parType('attestation_scolarite');
    echo "   - Scope parType(): OK\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur dans les scopes: " . $e->getMessage() . "\n";
}

// Test 5: Vérification des attributs fillable
echo "\n5. ✅ Vérification des attributs fillable...\n";

try {
    $utilisateur = new Utilisateur();
    $fillable = $utilisateur->getFillable();
    $expected = ['nom', 'prenom', 'email', 'password', 'role'];
    $diff = array_diff($expected, $fillable);
    echo "   - Utilisateur fillable: " . (empty($diff) ? "OK" : "❌ Manque: " . implode(', ', $diff)) . "\n";
    
    $etudiant = new Etudiant();
    $fillable = $etudiant->getFillable();
    $expected = ['utilisateur_id', 'numero_etudiant', 'filiere', 'annee', 'date_inscription'];
    $diff = array_diff($expected, $fillable);
    echo "   - Etudiant fillable: " . (empty($diff) ? "OK" : "❌ Manque: " . implode(', ', $diff)) . "\n";
    
    $document = new Document();
    $fillable = $document->getFillable();
    $expected = ['modele_document_id', 'etudiant_id', 'administrateur_id', 'type', 'nom', 'chemin_fichier', 'date_generation', 'est_public', 'donnees_document'];
    $diff = array_diff($expected, $fillable);
    echo "   - Document fillable: " . (empty($diff) ? "OK" : "❌ Manque: " . implode(', ', $diff)) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur dans les attributs fillable: " . $e->getMessage() . "\n";
}

echo "\n🎉 TESTS TERMINÉS!\n";
echo "==================\n";
echo "Si tous les tests sont OK, l'application devrait fonctionner correctement.\n";
echo "N'oubliez pas de:\n";
echo "1. Configurer le fichier .env\n";
echo "2. Exécuter les migrations: php artisan migrate\n";
echo "3. Créer les utilisateurs de test\n";
echo "4. Démarrer le serveur: php artisan serve\n";
