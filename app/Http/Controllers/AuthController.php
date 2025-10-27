<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        $utilisateur = Auth::user();
        $token = $utilisateur->createToken('auth_token')->plainTextToken;

        // Charger les données spécifiques selon le rôle
        $this->chargerDonneesRole($utilisateur);

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'utilisateur' => $utilisateur,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:utilisateurs',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:etudiant,enseignant,administrateur,directeur',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $utilisateur = Utilisateur::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Créer le profil spécifique selon le rôle
        $this->creerProfilRole($utilisateur, $request);

        $token = $utilisateur->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'utilisateur' => $utilisateur,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    public function me(Request $request)
    {
        $utilisateur = $request->user();
        $this->chargerDonneesRole($utilisateur);

        return response()->json([
            'success' => true,
            'data' => $utilisateur
        ]);
    }

    private function chargerDonneesRole(Utilisateur $utilisateur)
    {
        switch ($utilisateur->role) {
            case 'etudiant':
                $utilisateur->load('etudiant');
                break;
            case 'enseignant':
                $utilisateur->load('enseignant');
                break;
            case 'administrateur':
                $utilisateur->load('administrateur');
                break;
            case 'directeur':
                $utilisateur->load('directeur');
                break;
        }
    }

    private function creerProfilRole(Utilisateur $utilisateur, Request $request)
    {
        switch ($utilisateur->role) {
            case 'etudiant':
                Etudiant::create([
                    'utilisateur_id' => $utilisateur->id,
                    'numero_etudiant' => $request->numero_etudiant ?? 'ETU' . $utilisateur->id,
                    'filiere' => $request->filiere ?? 'Non définie',
                    'annee' => $request->annee ?? now()->year,
                    'date_inscription' => now(),
                ]);
                break;
            case 'enseignant':
                Enseignant::create([
                    'utilisateur_id' => $utilisateur->id,
                    'matricule' => $request->matricule ?? 'ENS' . $utilisateur->id,
                    'matieres_enseignees' => $request->matieres_enseignees ?? [],
                    'bureau' => $request->bureau,
                    'departement' => $request->departement ?? 'Non défini',
                ]);
                break;
            case 'administrateur':
                Administrateur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'departement' => $request->departement ?? 'Administration',
                    'date_embauche' => now(),
                ]);
                break;
            case 'directeur':
                Directeur::create([
                    'utilisateur_id' => $utilisateur->id,
                    'signature' => $request->signature,
                ]);
                break;
        }
    }
}
