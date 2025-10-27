<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use App\Http\Controllers\AdministrateurController;

try {
    $admin = Utilisateur::where('email','admin@isi.com')->firstOrFail();
    $etuUser = Utilisateur::where('email','etudiant@isi.com')->firstOrFail();
    $etudiant = $etuUser->etudiant;
    if (!$etudiant) { throw new Exception('Profil étudiant manquant'); }

    Auth::login($admin);
    $ctrl = app(AdministrateurController::class);
    $req = Request::create('/api/admin/documents/generer','POST', [
        'type' => 'attestation_scolarite',
        'etudiant_id' => $etudiant->id,
        'donnees' => [ 'date_du_jour' => date('Y-m-d') ]
    ]);
    $resp = $ctrl->genererDocument($req);
    echo method_exists($resp,'getContent') ? $resp->getContent() : json_encode($resp),"\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: '.$e->getMessage().'\n');
    exit(1);
}
