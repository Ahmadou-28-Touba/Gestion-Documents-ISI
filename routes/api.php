<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EtudiantController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\DirecteurController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Routes publiques (auth)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Auth protégé
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Étudiants
    Route::prefix('etudiant')->middleware('role:etudiant')->group(function () {
        Route::get('dashboard', [EtudiantController::class, 'dashboard']);
        Route::get('profil', [EtudiantController::class, 'profil']);
        Route::put('profil', [EtudiantController::class, 'modifierProfil']);
        Route::get('absences', [EtudiantController::class, 'index']);
        Route::post('absences', [EtudiantController::class, 'store']);
        Route::get('documents', [EtudiantController::class, 'consulterDocuments']);
        Route::get('documents/{id}/telecharger', [EtudiantController::class, 'telechargerDocument']);
    });

    // Enseignants
    Route::prefix('enseignant')->middleware('role:enseignant')->group(function () {
        Route::get('dashboard', [EnseignantController::class, 'dashboard']);
        Route::get('profil', [EnseignantController::class, 'profil']);
        Route::put('profil', [EnseignantController::class, 'modifierProfil']);
        Route::get('absences', [EnseignantController::class, 'consulterAbsences']);
        Route::get('absences/en-attente', [EnseignantController::class, 'absencesEnAttente']);
        Route::post('absences/{id}/valider', [EnseignantController::class, 'validerAbsence']);
        Route::post('absences/{id}/refuser', [EnseignantController::class, 'refuserAbsence']);
        // Classes (gestion)
        Route::get('classes', [EnseignantController::class, 'mesClasses']);
        Route::get('classes/suggestions', [EnseignantController::class, 'classesSuggestions']);
        Route::post('classes/attach', [EnseignantController::class, 'attachClasse']);
        Route::delete('classes/{id}/detach', [EnseignantController::class, 'detachClasse']);
        // Emploi du temps
        Route::get('emploi-du-temps', [EnseignantController::class, 'emploiDuTemps']);
        Route::get('emploi-du-temps/{id}/telecharger', [EnseignantController::class, 'telechargerEmploiDuTemps']);
    });

    // Administrateurs - Phase 1: modèles + génération/publication
    Route::prefix('admin')->middleware('role:administrateur')->group(function () {
        // Statistiques dashboard admin
        Route::get('statistiques', [AdministrateurController::class, 'statistiques']);
        // Modèles par type
        Route::get('modeles/types', [AdministrateurController::class, 'listeTypesModeles']);
        Route::get('modeles/type/{type}', [AdministrateurController::class, 'showModeleParType']);
        Route::get('modeles/type/{type}/download', [AdministrateurController::class, 'downloadModeleParType']);
        Route::get('modeles/type/{type}/new-docx', [AdministrateurController::class, 'newModeleDocxParType']);
        Route::post('modeles/type/{type}/upload', [AdministrateurController::class, 'uploadModeleParType']);
        Route::post('modeles/type/{type}/preview', [AdministrateurController::class, 'previewModeleType']);
        // Édition du contenu brut (HTML/TXT) du modèle actif par type
        Route::get('modeles/type/{type}/content', [AdministrateurController::class, 'getModeleContentByType']);
        Route::put('modeles/type/{type}/content', [AdministrateurController::class, 'updateModeleContentByType']);

        // Générer & Publier
        Route::post('documents/generer-preview', [AdministrateurController::class, 'genererPreviewDocuments']);
        Route::post('documents/publier', [AdministrateurController::class, 'publierDocuments']);
        Route::post('documents/publier-test', [AdministrateurController::class, 'publierDocumentTest']);
        Route::get('documents/recent', [AdministrateurController::class, 'listeDocumentsRecents']);
        Route::get('documents/{id}/download', [AdministrateurController::class, 'downloadDocumentById']);

        // Imports Emploi du Temps
        Route::post('imports/emploi-temps', [AdministrateurController::class, 'uploadEmploiTempsCsv']);

        // Gestion des classes (CRUD + affectations enseignants)
        Route::get('classes', [AdministrateurController::class, 'listeClasses']);
        Route::post('classes', [AdministrateurController::class, 'creerClasse']);
        Route::put('classes/{id}', [AdministrateurController::class, 'mettreAJourClasse']);
        Route::delete('classes/{id}', [AdministrateurController::class, 'supprimerClasse']);
        Route::post('classes/{id}/attach-enseignant', [AdministrateurController::class, 'attachEnseignantClasse']);
        Route::delete('classes/{id}/detach-enseignant/{enseignantId}', [AdministrateurController::class, 'detachEnseignantClasse']);
        Route::put('classes/{id}/enseignants/{enseignantId}/matiere', [AdministrateurController::class, 'updateMatiereClasseEnseignant']);

        // Recherche enseignants
        Route::get('enseignants/search', [AdministrateurController::class, 'rechercherEnseignants']);
        // Liste complète des enseignants
        Route::get('enseignants', [AdministrateurController::class, 'listeEnseignants']);
        // Gestion des matières enseignées par enseignant
        Route::get('enseignants/{id}/matieres', [AdministrateurController::class, 'getMatieresEnseignant']);
        Route::put('enseignants/{id}/matieres', [AdministrateurController::class, 'updateMatieresEnseignant']);

        // Utilisateurs (CRUD minimal)
        Route::get('utilisateurs', [UserController::class, 'index']);
        Route::post('utilisateurs', [UserController::class, 'store']);
        Route::get('utilisateurs/{id}', [UserController::class, 'show']);
        Route::put('utilisateurs/{id}', [UserController::class, 'update']);
        Route::post('utilisateurs/{id}/password', [UserController::class, 'updatePassword']);
        Route::delete('utilisateurs/{id}', [UserController::class, 'destroy']);
    });

    // Directeurs
    Route::prefix('directeur')->middleware('role:directeur')->group(function () {
        Route::get('dashboard', [DirecteurController::class, 'dashboard']);
        Route::get('profil', [DirecteurController::class, 'profil']);
        Route::put('profil', [DirecteurController::class, 'modifierProfil']);
        Route::get('statistiques', [DirecteurController::class, 'consulterStatistiques']);
        Route::get('rapport-annuel/{annee?}', [DirecteurController::class, 'genererRapportAnnuel']);
        Route::get('rapport-annuel/{annee}/pdf', [DirecteurController::class, 'genererRapportAnnuelPdf']);
        Route::get('export/absences', [DirecteurController::class, 'exportAbsences']);
        Route::get('export/documents', [DirecteurController::class, 'exportDocuments']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('statistiques', [NotificationController::class, 'statistiques']);
        Route::get('{id}', [NotificationController::class, 'show']);
        Route::post('{id}/marquer-lue', [NotificationController::class, 'marquerCommeLue']);
        Route::post('marquer-toutes-lues', [NotificationController::class, 'marquerToutesCommeLues']);
        Route::delete('{id}', [NotificationController::class, 'supprimer']);
        Route::delete('toutes', [NotificationController::class, 'supprimerToutes']);
    });

    // Routes globales pour Documents
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/types', [DocumentController::class, 'getTypes']);
        Route::get('/statistiques', [DocumentController::class, 'statistiques']);
        Route::get('/search', [DocumentController::class, 'search']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::put('/{id}', [DocumentController::class, 'update']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
        Route::get('/{id}/telecharger', [DocumentController::class, 'telecharger']);
        Route::post('/{id}/archiver', [DocumentController::class, 'archiver']);
        Route::post('/{id}/desarchiver', [DocumentController::class, 'desarchiver']);
        Route::post('/{id}/dupliquer', [DocumentController::class, 'dupliquer']);
    });

    // Routes globales pour Absences
    Route::prefix('absences')->group(function () {
        Route::get('/', [AbsenceController::class, 'index']);
        Route::post('/', [AbsenceController::class, 'store']);
        Route::get('/statuts', [AbsenceController::class, 'getStatuts']);
        Route::get('/statistiques', [AbsenceController::class, 'statistiques']);
        Route::get('/search', [AbsenceController::class, 'search']);
        Route::get('/{id}', [AbsenceController::class, 'show']);
        Route::put('/{id}', [AbsenceController::class, 'update']);
        Route::delete('/{id}', [AbsenceController::class, 'destroy']);
        Route::post('/{id}/valider', [AbsenceController::class, 'valider']);
        Route::post('/{id}/rejeter', [AbsenceController::class, 'rejeter']);
        Route::post('/{id}/annuler', [AbsenceController::class, 'annuler']);
    });
});

// Route de test
//Route::get('test', function () {
//    return response()->json([
//        'message' => 'API de gestion ISI fonctionnelle',
//        'version' => '1.0.0',
//        'timestamp' => now()
//    ]);
//});
