<?php

require __DIR__.'/vendor/autoload.php';

use App\Models\Administrateur;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;

// Démarrer l'application Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Vérifier si un administrateur existe déjà
    $admin = Administrateur::first();
    
    if ($admin) {
        echo "Un administrateur existe déjà avec l'ID : " . $admin->id . "\n";
        exit(0);
    }

    // Créer un nouvel utilisateur administrateur
    $user = Utilisateur::create([
        'nom' => 'Admin',
        'prenom' => 'Système',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
        'role' => 'administrateur',
        'email_verified_at' => now(),
    ]);

    // Créer l'administrateur
    $admin = Administrateur::create([
        'utilisateur_id' => $user->id,
        'poste' => 'Administrateur système',
    ]);

    echo "Administrateur créé avec succès !\n";
    echo "ID : " . $admin->id . "\n";
    echo "Email : " . $user->email . "\n";
    echo "Mot de passe : password\n\n";
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}
