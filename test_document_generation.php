<?php

require __DIR__.'/vendor/autoload.php';

use App\Models\Etudiant;
use App\Models\Document;
use App\Models\ModeleDocument;
use Illuminate\Support\Facades\DB;

// Démarrer l'application Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Configuration du mail pour la journalisation
config([
    'mail.default' => 'log',
    'mail.mailers.log.channel' => 'single',
]);

try {
    // Récupérer un étudiant de test
    $etudiant = Etudiant::with('utilisateur')->first();
    
    if (!$etudiant) {
        throw new Exception("Aucun étudiant trouvé dans la base de données");
    }

    echo "Génération d'une attestation pour : " . $etudiant->utilisateur->prenom . ' ' . $etudiant->utilisateur->nom . "\n";

    // Récupérer le modèle d'attestation
    $modele = ModeleDocument::where('type_document', 'attestation_scolarite')->first();
    
    if (!$modele) {
        throw new Exception("Modèle d'attestation de scolarité non trouvé");
    }

    // Données du document
    $donnees = [
        'motif' => 'Test de génération de document',
    ];

    // Récupérer un administrateur existant
    $admin = \App\Models\Administrateur::first();
    
    if (!$admin) {
        throw new Exception("Aucun administrateur trouvé dans la base de données");
    }

    // Créer le document avec l'administrateur
    $document = DB::transaction(function () use ($etudiant, $modele, $donnees, $admin) {
        return Document::create([
            'modele_document_id' => $modele->id,
            'etudiant_id' => $etudiant->id,
            'administrateur_id' => $admin->id,
            'type' => 'attestation_scolarite',
            'nom' => 'Attestation de scolarité - ' . $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
            'chemin_fichier' => 'documents/attestations/' . now()->format('Y/m/') . uniqid() . '.pdf',
            'donnees_document' => $donnees,
            'est_public' => true,
            'date_generation' => now()
        ]);
    });

    echo "Document généré avec succès !\n";
    echo "ID du document : " . $document->id . "\n";
    
    // Essayer d'envoyer un email de test
    try {
        \Illuminate\Support\Facades\Mail::raw('Ceci est un email de test', function($message) use ($etudiant) {
            $message->to($etudiant->utilisateur->email)
                   ->subject('Test d\'envoi d\'email');
        });
        echo "Email de test envoyé à : " . $etudiant->utilisateur->email . "\n";
    } catch (\Exception $e) {
        echo "Erreur lors de l'envoi de l'email : " . $e->getMessage() . "\n";
    }
    
    echo "\nVérifiez le fichier de log : storage/logs/laravel.log\n";
    
} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}
