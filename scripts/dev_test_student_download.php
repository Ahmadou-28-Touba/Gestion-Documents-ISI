<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Utilisateur;
use App\Http\Controllers\EtudiantController;

function println($m){ echo $m, "\n"; }

try {
    $user = Utilisateur::where('email','etudiant@isi.com')->firstOrFail();
    Auth::login($user);
    $ctrl = app(EtudiantController::class);

    $doc = $user->etudiant->documents()->publics()->latest()->first();
    if (!$doc) { throw new Exception('Aucun document public pour cet étudiant'); }

    $resp = $ctrl->telechargerDocument($doc->id);
    $status = method_exists($resp,'getStatusCode') ? $resp->getStatusCode() : 200;
    println('Download response status: '.$status);
    if ($status !== 200) {
        $content = method_exists($resp,'getContent') ? $resp->getContent() : '';
        println('Body: '.$content);
    }
} catch (Throwable $e) {
    println('ERROR: '.$e->getMessage());
}
