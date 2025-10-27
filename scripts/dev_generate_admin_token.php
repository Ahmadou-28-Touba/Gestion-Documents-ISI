<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Utilisateur;

$u = Utilisateur::where('email', 'admin@isi.com')->first();
if (!$u) {
    fwrite(STDERR, "Admin user not found\n");
    exit(1);
}
$token = $u->createToken('auth_token')->plainTextToken;
echo $token, "\n";
