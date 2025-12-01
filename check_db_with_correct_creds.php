<?php

$host = '127.0.0.1';
$dbname = 'gestion-documents-isi';
$username = 'postgres';
$password = 'Bamb@1218'; // Mot de passe du fichier .env

try {
    $pdo = new PDO("pgsql:host=$host;port=5432;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie !\n\n";
    
    // Récupérer la liste des tables
    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name";
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables disponibles dans la base de données :\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Vérifier les tables spécifiques
    $important_tables = ['users', 'utilisateurs', 'documents', 'absences', 'modele_documents'];
    echo "\nVérification des tables importantes :\n";
    
    foreach ($important_tables as $table) {
        if (in_array($table, $tables)) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
                echo "- $table : $count enregistrements\n";
                
                // Afficher quelques enregistrements pour les tables non vides
                if ($count > 0) {
                    $sample = $pdo->query("SELECT * FROM \"$table\" LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
                    echo "  Exemple d'enregistrement : " . json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                }
            } catch (PDOException $e) {
                echo "- $table : Erreur lors de la lecture - " . $e->getMessage() . "\n";
            }
        } else {
            echo "- $table : Table non trouvée\n";
        }
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "\n");
}
