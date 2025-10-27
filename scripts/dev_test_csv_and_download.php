<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\ModeleDocument;
use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\EtudiantController;

function println($msg){ echo $msg, "\n"; }

$results = [
  'csv_emploi' => null,
  'csv_bulletins' => null,
  'download_doc' => null,
];

try {
    // Auth admin
    $adminUser = Utilisateur::where('email','admin@isi.com')->firstOrFail();
    Auth::login($adminUser);
    $adminCtrl = app(AdministrateurController::class);

    // Préparer un étudiant
    $etu = Etudiant::with('utilisateur')->firstOrFail();

    // S'assurer que les modèles existent
    if (!ModeleDocument::where('type_document','emploi_temps')->exists()) {
        throw new Exception('Modele emploi_temps absent');
    }
    if (!ModeleDocument::where('type_document','bulletin_notes')->exists()) {
        throw new Exception('Modele bulletin_notes absent');
    }

    // Créer CSV emploi du temps temporaire
    $tmpDir = storage_path('app/tmp');
    if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);
    $emploiCsv = $tmpDir.'/emploi_test.csv';
    file_put_contents($emploiCsv, "numero_etudiant,filiere,annee,semaine,jour,heure_debut,heure_fin,matiere,salle,enseignant,groupe\n".
        $etu->numero_etudiant.",".$etu->filiere.",".$etu->annee.",42,Lundi,08:00,10:00,Algo,S101,M. Prof,A\n");
    $uploadedEmploi = new UploadedFile($emploiCsv, 'emploi_test.csv', 'text/csv', null, true);
    $reqEmploi = Request::create('/api/admin/imports/emploi-temps','POST',[],[],['fichier'=>$uploadedEmploi]);
    $resEmploi = $adminCtrl->uploadEmploiTempsCsv($reqEmploi);
    $results['csv_emploi'] = method_exists($resEmploi,'getContent') ? json_decode($resEmploi->getContent(), true) : $resEmploi;
    println('CSV emploi import: DONE');

    // Créer CSV bulletins temporaire
    $bulletinCsv = $tmpDir.'/bulletins_test.csv';
    file_put_contents($bulletinCsv, "numero_etudiant,filiere,annee,semestre,moyenne,appreciation\n".
        $etu->numero_etudiant.",".$etu->filiere.",".$etu->annee.",S1,14.5,Bon travail\n");
    $uploadedBull = new UploadedFile($bulletinCsv, 'bulletins_test.csv', 'text/csv', null, true);
    $reqBull = Request::create('/api/admin/imports/bulletins','POST',[],[],['fichier'=>$uploadedBull]);
    $resBull = $adminCtrl->uploadBulletinsCsv($reqBull);
    $results['csv_bulletins'] = method_exists($resBull,'getContent') ? json_decode($resBull->getContent(), true) : $resBull;
    println('CSV bulletins import: DONE');

} catch (Throwable $e) {
    $results['csv_error'] = $e->getMessage();
}

// Test téléchargement document côté étudiant
try {
    $etudiantUser = Utilisateur::where('email','etudiant@isi.com')->firstOrFail();
    Auth::login($etudiantUser);
    $etuCtrl = app(EtudiantController::class);

    // Récupérer un document public récent
    $doc = $etudiantUser->etudiant->documents()->publics()->latest()->first();
    if (!$doc) {
        throw new Exception('Aucun document public trouvé pour l\'étudiant');
    }
    // Appel téléchargement
    $resp = $etuCtrl->telechargerDocument($doc->id);
    $results['download_doc'] = [ 'status' => method_exists($resp,'getStatusCode') ? $resp->getStatusCode() : 200, 'filename' => $doc->nom.'.pdf' ];
    println('Download doc: DONE');
} catch (Throwable $e) {
    $results['download_doc'] = [ 'error' => $e->getMessage() ];
}

println("\nRESULTS:\n".json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
