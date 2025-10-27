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

// Page d'admin pour génération manuelle (simple Blade)
Route::get('/admin/generation', function () {
    return view('admin.generation');
});

// Catch-all pour SPA (Vue Router history mode), exclure les routes API
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
