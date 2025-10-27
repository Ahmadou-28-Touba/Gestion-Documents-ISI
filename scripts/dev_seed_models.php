<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\Utilisateur;
use App\Http\Controllers\AdministrateurController;
use Illuminate\Http\Request;

$admin = Utilisateur::where('email','admin@isi.com')->first();
if (!$admin) { fwrite(STDERR, "Admin user not found\n"); exit(1);} 
Auth::login($admin);

$controller = app(AdministrateurController::class);
$request = Request::create('/api/admin/modeles/seed','POST');
$response = $controller->seedModelesDefaut($request);

if (method_exists($response,'getContent')) {
    echo $response->getContent(),"\n";
} else {
    var_dump($response);
}
