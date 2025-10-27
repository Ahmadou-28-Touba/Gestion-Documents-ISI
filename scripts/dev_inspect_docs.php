<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Utilisateur;
use Illuminate\Support\Facades\Auth;

$u = Utilisateur::where('email','etudiant@isi.com')->first();
if(!$u){echo "no user\n"; exit;}
Auth::login($u);
$docs = $u->etudiant->documents()->orderByDesc('id')->limit(10)->get(['id','nom','type','est_public','chemin_fichier','created_at']);
foreach($docs as $d){
  echo json_encode($d->toArray(), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n";
}
