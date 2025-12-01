<?php

$host = '127.0.0.1';
$dbname = 'gestion-documents-isi';
$username = 'postgres';
$password = ''; // Mettez votre mot de PostgreSQL ici si nécessaire

$dsn = "pgsql:host=$host;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie !\n\n";
    
    // Récupérer la liste des tables
    $query = "SELECT table_name 
              FROM information_schema.tables 
              WHERE table_schema = 'public' 
              ORDER BY table_name";
    
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables disponibles dans la base de données :\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Vérifier la table des utilisateurs
    if (in_array('utilisateurs', $tables)) {
        echo "\nContenu de la table 'utilisateurs' :\n";
        $stmt = $pdo->query("SELECT * FROM utilisateurs LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($users);
    } else {
        echo "\nLa table 'utilisateurs' n'existe pas.\n";
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "\n");
}
