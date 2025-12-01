<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$app->boot();

use Illuminate\Support\Facades\DB;

// Vérifier la connexion à la base de données
try {
    DB::connection()->getPdo();
    echo "Connexion à la base de données réussie.\n";
    
    // Compter les documents
    $documents = DB::table('documents')->count();
    echo "Nombre total de documents : $documents\n";
    
    // Compter les utilisateurs
    $utilisateurs = DB::table('utilisateurs')->count();
    echo "Nombre total d'utilisateurs : $utilisateurs\n";
    
    // Compter les absences en attente
    $absences = DB::table('absences')->where('statut', 'en_attente')->count();
    echo "Absences en attente : $absences\n";
    
    // Compter les modèles actifs (en utilisant 1 pour true)
    $modeles = DB::table('modele_documents')->where('est_actif', 1)->count();
    echo "Modèles actifs : $modeles\n";
    
} catch (\Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "\n");
}
