<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illware_Console_Kernel::class);

$app->boot();

try {
    // Vérifier la connexion à la base de données
    DB::connection()->getPdo();
    echo "Connexion à la base de données réussie!\n";
    
    // Récupérer la liste des tables
    $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    
    echo "\nTables disponibles dans la base de données :\n";
    foreach ($tables as $table) {
        echo "- " . $table->table_name . "\n";
    }
    
} catch (\Exception $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage() . "\n");
}
