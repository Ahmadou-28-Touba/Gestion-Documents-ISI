<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// 1. Mettre à jour la configuration du mail
$envFile = __DIR__.'/.env';
$envContent = file_get_contents($envFile);

// Configuration du mail pour la journalisation
$newConfig = [
    'MAIL_MAILER=log' => 'MAIL_MAILER=log',
    'MAIL_HOST=mailpit' => 'MAIL_HOST=127.0.0.1',
    'MAIL_PORT=1025' => 'MAIL_PORT=1025',
    'MAIL_USERNAME=null' => 'MAIL_USERNAME=null',
    'MAIL_PASSWORD=null' => 'MAIL_PASSWORD=null',
    'MAIL_ENCRYPTION=null' => 'MAIL_ENCRYPTION=null',
    'MAIL_FROM_ADDRESS="hello@example.com"' => 'MAIL_FROM_ADDRESS="no-reply@example.com"',
    'MAIL_FROM_NAME="${APP_NAME}"' => 'MAIL_FROM_NAME="Gestion Documents ISI"',
];

foreach ($newConfig as $search => $replace) {
    $envContent = preg_replace("/^" . preg_quote($search, '/') . "$/m", $replace, $envContent);
}

file_put_contents($envFile, $envContent);

// 2. Nettoyer le cache de configuration
Artisan::call('config:clear');
Artisan::call('cache:clear');
Artisan::call('config:cache');

echo "Configuration du mail mise à jour avec succès.\n";
echo "Le système utilisera maintenant les logs pour les emails.\n";
echo "Vous pouvez vérifier les logs dans storage/logs/laravel.log\n\n";

echo "Pour tester l'envoi d'email, vous pouvez exécuter :\n";
echo "php artisan tinker\n";
echo ">>> \\Mail::raw('Test email', function(\$message) {\n...     \$message->to('test@example.com')->subject('Test Email');
... });";
