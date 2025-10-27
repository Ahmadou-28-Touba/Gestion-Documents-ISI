<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Utilisateur;
use App\Models\ModeleDocument;
use App\Http\Controllers\AdministrateurController;
use App\Services\PdfRenderer;

try {
    // 1) Trouver l'admin
    $admin = Utilisateur::where('email','admin@isi.com')->firstOrFail();
    Auth::login($admin);

    // 2) Créer un fichier modèle HTML de test dans storage/app/templates/attestation_scolarite/
    $type = 'attestation_scolarite';
    $dir = 'templates/'.Str::slug($type);
    $filename = 'modele_test_'.time().'.html';
    $path = $dir.'/'.$filename;
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <style> body { font-family: Arial; } .title{ text-align:center; font-size:20px; } </style>
</head>
<body>
  <div class="title">ATTESTATION DE SCOLARITÉ (TEST)</div>
  <p>Nom: <strong>{{ etudiant.nom }}</strong></p>
  <p>Prénom: <strong>{{ etudiant.prenom }}</strong></p>
  <p>Filière: <strong>{{ etudiant.filiere }}</strong></p>
  <p>Année: <strong>{{ etudiant.annee }}</strong></p>
  <p>Date: <strong>{{ date_du_jour }}</strong></p>
</body>
</html>
HTML;
    Storage::disk('local')->put($path, $html);

    // 3) Appeler uploadModele (activer par défaut)
    /** @var AdministrateurController $ctrl */
    $ctrl = app(AdministrateurController::class);
    $tmpFile = tempnam(sys_get_temp_dir(), 'tpl_');
    file_put_contents($tmpFile, $html);
    $uploaded = new \Illuminate\Http\UploadedFile(
        $tmpFile,
        $filename,
        'text/html',
        null,
        true
    );
    $req = Request::create('/api/admin/modeles/upload','POST', [
        'nom' => 'Modèle Test '.date('H:i:s'),
        'type_document' => $type,
        'description' => 'Modèle de test automatique',
        'activer' => true,
    ], [], [ 'fichier_modele' => $uploaded ]);

    $resp = $ctrl->uploadModele($req);
    $data = json_decode($resp->getContent(), true);
    if (!($data['success'] ?? false)) { throw new Exception('Upload échoué: '.json_encode($data)); }

    $modeleId = $data['data']['id'] ?? null;
    if (!$modeleId) { throw new Exception('ID modèle manquant après upload'); }

    // 4) Récupérer le modèle actif et faire un rendu PDF de test
    /** @var ModeleDocument $modele */
    $modele = ModeleDocument::findOrFail($modeleId);

    /** @var PdfRenderer $renderer */
    $renderer = app(PdfRenderer::class);

    $donnees = [
        'etudiant' => [
            'nom' => 'SOW',
            'prenom' => 'Awa',
            'filiere' => 'Licence 3 Informatique',
            'annee' => date('Y'),
        ],
        'date_du_jour' => date('Y-m-d')
    ];

    $pdfBytes = $renderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

    // 5) Sauvegarder un PDF de preview dans storage/app/public
    $out = 'test_previews/modele_test_'.time().'.pdf';
    Storage::disk('public')->put($out, $pdfBytes);

    echo json_encode([
        'success' => true,
        'message' => 'Test modèles OK',
        'modele_id' => $modeleId,
        'chemin_modele' => $modele->chemin_modele,
        'preview_public_path' => $out
    ], JSON_PRETTY_PRINT), "\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage()."\n");
    exit(1);
}
