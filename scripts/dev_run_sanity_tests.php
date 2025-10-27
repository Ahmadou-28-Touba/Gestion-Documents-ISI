<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\AbsenceController;

function println($msg){ echo $msg, "\n"; }

$results = [
  'generate_document' => null,
  'absence_flow' => null,
];

// 1) Génération d'un document (admin)
try {
    $admin = Utilisateur::where('email','admin@isi.com')->firstOrFail();
    Auth::login($admin);

    $etu = Etudiant::with('utilisateur')->firstOrFail();

    $controller = app(AdministrateurController::class);
    $req = Request::create('/api/admin/documents/generer','POST', [
        'type' => 'attestation_scolarite',
        'etudiant_id' => $etu->id,
        'donnees' => [ 'date_du_jour' => date('Y-m-d') ]
    ]);

    $resp = $controller->genererDocument($req);
    $payload = method_exists($resp,'getData') ? $resp->getData(true) : null;

    if (!$payload || empty($payload['success'])) {
        $raw = method_exists($resp,'getContent') ? $resp->getContent() : json_encode($payload);
        throw new Exception('Réponse invalide de génération: '.$raw);
    }

    $doc = $payload['data'] ?? [];
    $path = $doc['chemin_fichier'] ?? null;
    $exists = $path ? Storage::disk('public')->exists($path) : false;

    $results['generate_document'] = [
        'success' => (bool)$exists,
        'document_id' => $doc['id'] ?? null,
        'chemin_fichier' => $path,
        'etudiant_id' => $etu->id,
    ];
    println('Document generation: '.($exists ? 'OK' : 'FAIL'));
} catch (Throwable $e) {
    $results['generate_document'] = [ 'success' => false, 'error' => $e->getMessage() ];
    println('Document generation: FAIL - '.$e->getMessage());
}

// 2) Flux Absence (étudiant crée -> enseignant valide)
try {
    $etudiantUser = Utilisateur::where('email','etudiant@isi.com')->firstOrFail();
    Auth::login($etudiantUser);

    $absController = app(AbsenceController::class);
    $offset = 30 + random_int(0, 100);
    $start = Carbon::now()->addDays($offset)->toDateString();
    $end = Carbon::now()->addDays($offset + 1)->toDateString();
    $storeReq = Request::create('/api/etudiant/absences','POST', [
        'date_debut' => $start,
        'date_fin' => $end,
        'motif' => 'Test automatisé',
    ]);
    $storeResp = $absController->store($storeReq);
    $storePayload = method_exists($storeResp,'getData') ? $storeResp->getData(true) : null;
    if (!$storePayload || empty($storePayload['success'])) {
        $raw = method_exists($storeResp,'getContent') ? $storeResp->getContent() : json_encode($storePayload);
        throw new Exception('Création absence échouée: '.$raw);
    }
    $absence = $storePayload['data'] ?? [];

    // Valider en tant qu'enseignant
    $enseignantUser = Utilisateur::where('email','enseignant@isi.com')->firstOrFail();
    Auth::login($enseignantUser);
    $validerReq = Request::create('/api/enseignant/absences/'.$absence['id'].'/valider','POST');
    $validerResp = $absController->valider($validerReq, $absence['id']);
    $valPayload = method_exists($validerResp,'getData') ? $validerResp->getData(true) : null;
    $ok = $valPayload && !empty($valPayload['success']);

    $results['absence_flow'] = [
        'success' => (bool)$ok,
        'absence_id' => $absence['id'] ?? null,
        'statut' => $valPayload['data']['statut'] ?? null,
    ];
    println('Absence flow: '.($ok ? 'OK' : 'FAIL'));
} catch (Throwable $e) {
    $results['absence_flow'] = [ 'success' => false, 'error' => $e->getMessage() ];
    println('Absence flow: FAIL - '.$e->getMessage());
}

println("\nRESULTS:\n".json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
