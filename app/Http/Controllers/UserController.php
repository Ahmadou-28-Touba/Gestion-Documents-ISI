<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\Enseignant;
use App\Models\Administrateur;
use App\Models\Directeur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $role = $request->get('role');
        $perPage = min((int)$request->get('per_page', 15), 100);

        $users = Utilisateur::query()
            ->when($q, function ($qr) use ($q) {
                $qr->where(function($w) use ($q){
                    $w->where('nom', 'like', "%$q%")
                      ->orWhere('prenom', 'like', "%$q%")
                      ->orWhere('email', 'like', "%$q%");
                });
            })
            ->when($role, fn($qr) => $qr->where('role', $role))
            ->orderBy('nom')
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function show($id)
    {
        $user = Utilisateur::with(['etudiant','enseignant','administrateur','directeur'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:utilisateurs,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:etudiant,enseignant,administrateur,directeur',
            // spécifiques
            'numero_etudiant' => 'sometimes|string|max:50',
            'filiere' => 'sometimes|string|max:255',
            'annee' => 'sometimes|string|max:50',
            'matricule' => 'sometimes|string|max:50',
            'departement' => 'sometimes|string|max:255',
            'bureau' => 'sometimes|string|max:255',
            'signature' => 'sometimes|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        return DB::transaction(function() use ($request) {
            $user = Utilisateur::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            switch ($user->role) {
                case 'etudiant':
                    Etudiant::create([
                        'utilisateur_id' => $user->id,
                        'numero_etudiant' => $request->numero_etudiant ?? ('ETU'.$user->id),
                        'filiere' => $request->filiere ?? 'Non définie',
                        'annee' => $request->annee ?? (string)now()->year,
                        'date_inscription' => now(),
                    ]);
                    break;
                case 'enseignant':
                    Enseignant::create([
                        'utilisateur_id' => $user->id,
                        'matricule' => $request->matricule ?? ('ENS'.$user->id),
                        'departement' => $request->departement ?? 'Non défini',
                        'bureau' => $request->bureau,
                        'matieres_enseignees' => $request->matieres_enseignees ?? [],
                    ]);
                    break;
                case 'administrateur':
                    Administrateur::create([
                        'utilisateur_id' => $user->id,
                        'departement' => $request->departement ?? 'Administration',
                        'date_embauche' => now(),
                    ]);
                    break;
                case 'directeur':
                    Directeur::create([
                        'utilisateur_id' => $user->id,
                        'signature' => $request->signature,
                    ]);
                    break;
            }

            return response()->json(['success' => true, 'message' => 'Utilisateur créé', 'data' => $user->load([$user->role])], 201);
        });
    }

    public function update(Request $request, $id)
    {
        $user = Utilisateur::findOrFail($id);
        $v = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:utilisateurs,email,'.$user->id,
            'role' => 'sometimes|in:etudiant,enseignant,administrateur,directeur',
            // spécifiques identiques au store, optionnels
            'numero_etudiant' => 'sometimes|string|max:50',
            'filiere' => 'sometimes|string|max:255',
            'annee' => 'sometimes|string|max:50',
            'matricule' => 'sometimes|string|max:50',
            'departement' => 'sometimes|string|max:255',
            'bureau' => 'sometimes|string|max:255',
            'signature' => 'sometimes|string|max:1000',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }

        return DB::transaction(function() use ($request, $user) {
            $user->update($request->only(['nom','prenom','email','role']));
            $role = $user->role;
            switch ($role) {
                case 'etudiant':
                    if ($user->etudiant) {
                        $user->etudiant->update(array_filter([
                            'numero_etudiant' => $request->numero_etudiant ?? null,
                            'filiere' => $request->filiere ?? null,
                            'annee' => $request->annee ?? null,
                        ], fn($v)=>!is_null($v)));
                    }
                    break;
                case 'enseignant':
                    if ($user->enseignant) {
                        $user->enseignant->update(array_filter([
                            'matricule' => $request->matricule ?? null,
                            'departement' => $request->departement ?? null,
                            'bureau' => $request->bureau ?? null,
                            'matieres_enseignees' => $request->matieres_enseignees ?? null,
                        ], fn($v)=>!is_null($v)));
                    }
                    break;
                case 'administrateur':
                    if ($user->administrateur) {
                        $user->administrateur->update(array_filter([
                            'departement' => $request->departement ?? null,
                        ], fn($v)=>!is_null($v)));
                    }
                    break;
                case 'directeur':
                    if ($user->directeur) {
                        $user->directeur->update(array_filter([
                            'signature' => $request->signature ?? null,
                        ], fn($v)=>!is_null($v)));
                    }
                    break;
            }
            return response()->json(['success' => true, 'message' => 'Utilisateur modifié', 'data' => $user->load([$role])]);
        });
    }

    public function updatePassword(Request $request, $id)
    {
        $user = Utilisateur::findOrFail($id);
        $v = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'errors'=>$v->errors()], 422);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['success' => true, 'message' => 'Mot de passe mis à jour']);
    }

    public function destroy($id)
    {
        $user = Utilisateur::findOrFail($id);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'Utilisateur supprimé']);
    }
}
