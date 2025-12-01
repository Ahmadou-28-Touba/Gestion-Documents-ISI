<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Pages d'admin pour génération manuelle (simple Blade)
Route::get('/admin/generation', function () {
    return view('admin.generation');
})->middleware(['auth', 'role:administrateur']);

Route::get('/admin/generer-publier', function () {
    return view('admin.generation');
})->middleware(['auth', 'role:administrateur']);

// Catch-all pour SPA (Vue Router history mode), exclure les routes API
// Route temporaire pour le débogage des statistiques
Route::get('/debug/stats', function () {
    try {
        // Vérifier la connexion à la base de données
        DB::connection()->getPdo();
        
        // Récupérer les statistiques
        $stats = [
            'documents' => [
                'total' => DB::table('documents')->count(),
                'par_type' => DB::table('documents')
                    ->select('type', DB::raw('count(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ],
            'utilisateurs' => [
                'total' => DB::table('utilisateurs')->count(),
                'par_role' => DB::table('utilisateurs')
                    ->select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->pluck('count', 'role')
                    ->toArray(),
            ],
            'absences' => [
                'en_attente' => DB::table('absences')->where('statut', 'en_attente')->count(),
            ],
            'modeles' => [
                'actifs' => DB::table('modele_documents')->where('est_actif', true)->count(),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération des statistiques',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Catch-all pour SPA (Vue Router history mode), exclure les routes API
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
