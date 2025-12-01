<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Etudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    public function indexPourEnseignant(Request $request)
    {
        $utilisateur = Auth::user();
        $enseignant = $utilisateur->enseignant;

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé',
            ], 404);
        }

        $query = Note::with(['etudiant.utilisateur', 'etudiant.classe', 'classe'])
            ->where('enseignant_id', $enseignant->id);

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->input('classe_id'));
        }

        if ($request->filled('matiere')) {
            $query->where('matiere', $request->input('matiere'));
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->input('periode'));
        }

        if ($request->filled('etudiant_id')) {
            $query->where('etudiant_id', $request->input('etudiant_id'));
        }

        $notes = $query->orderByDesc('date')->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
        ]);
    }

    public function storePourEnseignant(Request $request)
    {
        $utilisateur = Auth::user();
        $enseignant = $utilisateur->enseignant;

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'etudiant_id' => 'required|exists:etudiants,id',
            'valeur' => 'required|numeric|min:0|max:20',
            'type_controle' => 'required|string|max:100',
            'date' => 'required|date',
            'classe_id' => 'nullable|exists:classes,id',
            'matiere' => 'nullable|string|max:100',
            'commentaire' => 'nullable|string',
            'periode' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        $etudiant = Etudiant::findOrFail($request->input('etudiant_id'));

        $classeId = $request->input('classe_id', $etudiant->classe_id);

        $note = Note::create([
            'etudiant_id' => $etudiant->id,
            'enseignant_id' => $enseignant->id,
            'classe_id' => $classeId,
            'matiere' => $request->input('matiere'),
            'valeur' => $request->input('valeur'),
            'type_controle' => $request->input('type_controle'),
            'date' => $request->input('date'),
            'commentaire' => $request->input('commentaire'),
            'periode' => $request->input('periode'),
            'est_valide' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note enregistrée',
            'data' => $note->load(['etudiant.utilisateur', 'classe']),
        ], 201);
    }

    public function updatePourEnseignant($id, Request $request)
    {
        $utilisateur = Auth::user();
        $enseignant = $utilisateur->enseignant;

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé',
            ], 404);
        }

        $note = Note::where('id', $id)
            ->where('enseignant_id', $enseignant->id)
            ->first();

        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Note introuvable pour cet enseignant',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'etudiant_id' => 'sometimes|exists:etudiants,id',
            'valeur' => 'sometimes|numeric|min:0|max:20',
            'type_controle' => 'sometimes|string|max:100',
            'date' => 'sometimes|date',
            'classe_id' => 'sometimes|nullable|exists:classes,id',
            'matiere' => 'sometimes|nullable|string|max:100',
            'commentaire' => 'sometimes|nullable|string',
            'periode' => 'sometimes|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('etudiant_id')) {
            $etudiant = Etudiant::findOrFail($request->input('etudiant_id'));
            $note->etudiant_id = $etudiant->id;

            if ($request->has('classe_id')) {
                $note->classe_id = $request->input('classe_id');
            } elseif (!$note->classe_id && $etudiant->classe_id) {
                $note->classe_id = $etudiant->classe_id;
            }
        }

        foreach (['valeur', 'type_controle', 'date', 'matiere', 'commentaire', 'periode'] as $champ) {
            if ($request->has($champ)) {
                $note->{$champ} = $request->input($champ);
            }
        }

        $note->save();

        return response()->json([
            'success' => true,
            'message' => 'Note mise à jour',
            'data' => $note->fresh()->load(['etudiant.utilisateur', 'classe']),
        ]);
    }

    public function mesNotesPourEtudiant(Request $request)
    {
        $utilisateur = Auth::user();
        $etudiant = $utilisateur->etudiant;

        if (!$etudiant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé',
            ], 404);
        }

        $query = Note::with(['enseignant.utilisateur', 'classe'])
            ->where('etudiant_id', $etudiant->id);

        if ($request->filled('periode')) {
            $query->where('periode', $request->input('periode'));
        }

        if ($request->filled('matiere')) {
            $query->where('matiere', $request->input('matiere'));
        }

        $notes = $query->orderByDesc('date')->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
        ]);
    }

    public function validerParAdmin($id)
    {
        $utilisateur = Auth::user();

        if (!$utilisateur->isAdministrateur() && !$utilisateur->isDirecteur()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $note = Note::findOrFail($id);
        $note->est_valide = true;
        $note->save();

        return response()->json([
            'success' => true,
            'message' => 'Note validée',
            'data' => $note,
        ]);
    }

    public function destroyPourEnseignant($id)
    {
        $utilisateur = Auth::user();
        $enseignant = $utilisateur->enseignant;

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé',
            ], 404);
        }

        $note = Note::where('id', $id)
            ->where('enseignant_id', $enseignant->id)
            ->first();

        if (!$note) {
            return response()->json([
                'success' => false,
                'message' => 'Note introuvable pour cet enseignant',
            ], 404);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note supprimée',
        ]);
    }
}
