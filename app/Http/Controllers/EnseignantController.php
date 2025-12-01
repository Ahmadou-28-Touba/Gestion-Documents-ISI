<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absence;
use App\Models\Enseignant;
use App\Models\Document;
use App\Models\Classe;
use App\Models\Etudiant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EnseignantController extends Controller
{
    public function dashboard()
    {
        $enseignant = Auth::user()->enseignant;
        
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $dep = trim((string) $enseignant->departement);
        $norm = $dep !== '' ? mb_strtolower($dep, 'UTF-8') : null;

        // Absences en attente pour la filière du département
        $absEnAttente = Absence::with(['etudiant.utilisateur'])
            ->where('statut', 'en_attente')
            ->when($norm !== null, function ($q) use ($norm) {
                $q->whereHas('etudiant', function ($qq) use ($norm) {
                    $qq->whereRaw('TRIM(LOWER(filiere)) = ?', [$norm]);
                });
            })
            ->orderByDesc('date_declaration')
            ->limit(5)
            ->get();

        // Absences traitées récemment par cet enseignant
        $absTraitees = Absence::with(['etudiant.utilisateur'])
            ->where('enseignant_id', $enseignant->id)
            ->whereIn('statut', ['validee', 'refusee'])
            ->orderByDesc('date_traitement')
            ->limit(5)
            ->get();

        // Statistiques pour l'enseignant
        $statistiques = [
            'absences_en_attente' => Absence::when($norm !== null, function ($q) use ($norm) {
                    $q->whereHas('etudiant', function ($qq) use ($norm) {
                        $qq->whereRaw('TRIM(LOWER(filiere)) = ?', [$norm]);
                    });
                })
                ->where('statut', 'en_attente')
                ->count(),
            'absences_validees_par_moi' => Absence::where('enseignant_id', $enseignant->id)->where('statut', 'validee')->count(),
            'absences_refusees_par_moi' => Absence::where('enseignant_id', $enseignant->id)->where('statut', 'refusee')->count(),
            'total_absences' => Absence::when($norm !== null, function ($q) use ($norm) {
                    $q->whereHas('etudiant', function ($qq) use ($norm) {
                        $qq->whereRaw('TRIM(LOWER(filiere)) = ?', [$norm]);
                    });
                })->count(),
        ];

        // Calcul dérivé
        $statistiques['absences_traitees_par_moi'] = $statistiques['absences_validees_par_moi'] + $statistiques['absences_refusees_par_moi'];

        return response()->json([
            'success' => true,
            'data' => [
                'enseignant' => $enseignant,
                'statistiques' => $statistiques,
                'absences_en_attente' => $absEnAttente,
                'absences_traitees' => $absTraitees,
            ]
        ]);
    }

    /**
     * Classes affectées à l'enseignant connecté
     */
    public function mesClasses()
    {
        $enseignant = Auth::user()->enseignant;
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $classes = $enseignant->classes()->orderBy('filiere')->orderBy('annee')->orderBy('groupe')->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    /**
     * Suggestions de classes (distinct filiere/annee/groupe) basées sur les étudiants de la filière de l'enseignant
     */
    public function classesSuggestions()
    {
        $enseignant = Auth::user()->enseignant;
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $dep = trim((string) $enseignant->departement);
        if ($dep === '') {
            return response()->json(['success' => true, 'data' => []]);
        }
        $depNorm = mb_strtolower($dep, 'UTF-8');

        $rows = Etudiant::query()
            ->select(['filiere', 'annee', 'groupe'])
            ->whereRaw('TRIM(LOWER(filiere)) = ?', [$depNorm])
            ->whereNotNull('annee')
            ->distinct()
            ->orderBy('annee')
            ->orderBy('groupe')
            ->get()
            ->map(function ($r) {
                $label = $r->filiere . ' ' . $r->annee . ($r->groupe ? ' (' . $r->groupe . ')' : '');
                return [
                    'filiere' => $r->filiere,
                    'annee' => $r->annee,
                    'groupe' => $r->groupe,
                    'label' => $label,
                ];
            })->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /** Attach a classe to teacher, creating classe if it doesn't exist */
    public function attachClasse(Request $request)
    {
        $request->validate([
            'filiere' => 'required|string',
            'annee' => 'required|string',
            'groupe' => 'nullable|string',
            'label' => 'nullable|string',
        ]);

        $enseignant = Auth::user()->enseignant;
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $classe = Classe::firstOrCreate([
            'filiere' => $request->filiere,
            'annee' => $request->annee,
            'groupe' => $request->groupe,
        ], [
            'label' => $request->label,
        ]);

        $enseignant->classes()->syncWithoutDetaching([$classe->id]);

        return response()->json([
            'success' => true,
            'data' => $classe
        ]);
    }

    /** Detach a classe from teacher */
    public function detachClasse($id)
    {
        $enseignant = Auth::user()->enseignant;
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $enseignant->classes()->detach($id);

        return response()->json([
            'success' => true,
            'message' => 'Classe détachée'
        ]);
    }

    public function profil()
    {
        $enseignant = Auth::user()->enseignant;
        
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'utilisateur' => Auth::user(),
                'enseignant' => $enseignant
            ]
        ]);
    }

    /**
     * Liste des étudiants accessibles à l'enseignant (pour la saisie de notes).
     * Par défaut filtrés par la filière (département) de l'enseignant.
     */
    public function etudiants()
    {
        $enseignant = Auth::user()->enseignant;

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $dep = trim((string) $enseignant->departement);
        $norm = $dep !== '' ? mb_strtolower($dep, 'UTF-8') : null;

        $query = Etudiant::with('utilisateur');

        if ($norm !== null) {
            $query->whereRaw('TRIM(LOWER(filiere)) = ?', [$norm]);
        }

        $etudiants = $query
            ->orderBy('filiere')
            ->orderBy('annee')
            ->orderBy('groupe')
            ->orderBy('numero_etudiant')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $etudiants
        ]);
    }

    public function modifierProfil(Request $request)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:utilisateurs,email,' . Auth::id(),
            'matricule' => 'sometimes|string|max:255',
            'matieres_enseignees' => 'sometimes|array',
            'bureau' => 'sometimes|string|max:255',
            'departement' => 'sometimes|string|max:255'
        ]);

        $utilisateur = Auth::user();
        $enseignant = $utilisateur->enseignant;

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        // Mettre à jour les données utilisateur
        $utilisateur->update($request->only(['nom', 'prenom', 'email']));

        // Mettre à jour les données enseignant
        $enseignant->update($request->only([
            'matricule', 'matieres_enseignees', 'bureau', 'departement'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'utilisateur' => $utilisateur->fresh(),
                'enseignant' => $enseignant->fresh()
            ]
        ]);
    }

    public function consulterAbsences(Request $request)
    {
        $enseignant = Auth::user()->enseignant;
        
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $filiere = $request->get('filiere');
        $annee = $request->get('annee');
        
        $absences = $enseignant->consulterAbsencesClasse($filiere, $annee);

        return response()->json([
            'success' => true,
            'data' => $absences
        ]);
    }

    public function absencesEnAttente()
    {
        $enseignant = Auth::user()->enseignant;
        
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $dep = trim((string) $enseignant->departement);
        $norm = $dep !== '' ? mb_strtolower($dep, 'UTF-8') : null;

        $absences = Absence::with(['etudiant.utilisateur'])
            ->where('statut', 'en_attente')
            ->when($norm !== null, function ($q) use ($norm) {
                $q->whereHas('etudiant', function ($qq) use ($norm) {
                    $qq->whereRaw('TRIM(LOWER(filiere)) = ?', [$norm]);
                });
            })
            ->orderByDesc('date_declaration')
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $absences
        ]);
    }

    public function validerAbsence(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:valider,refuser',
            'motif_refus' => 'required_if:action,refuser|string|max:500'
        ]);

        $enseignant = Auth::user()->enseignant;
        
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $absence = Absence::with('etudiant')->findOrFail($id);

        // Autorisation: departement (enseignant) ↔ filiere (étudiant) normalisés
        $dep = trim((string) $enseignant->departement);
        if ($dep !== '') {
            $depNorm = mb_strtolower($dep, 'UTF-8');
            $fil = $absence->etudiant ? trim((string) $absence->etudiant->filiere) : '';
            $filNorm = mb_strtolower($fil, 'UTF-8');
            if ($filNorm !== $depNorm) {
                return response()->json([
                    'success' => false,
                    'message' => "Vous n'êtes pas autorisé à traiter cette absence (hors de votre filière)"
                ], 403);
            }
        }

        if ($request->action === 'valider') {
            $absence = $enseignant->validerAbsence($id);
            $message = 'Absence validée avec succès';
        } else {
            $absence = $enseignant->rejeterAbsence($id, $request->motif_refus);
            $message = 'Absence refusée avec succès';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $absence
        ]);
    }

    public function refuserAbsence(Request $request, $id)
    {
        $request->validate([
            'motif_refus' => 'required|string|max:500'
        ]);

        $enseignant = Auth::user()->enseignant;
        
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        // Autorisation: departement (enseignant) ↔ filiere (étudiant) normalisés
        $absence = Absence::with('etudiant')->findOrFail($id);
        $dep = trim((string) $enseignant->departement);
        if ($dep !== '') {
            $depNorm = mb_strtolower($dep, 'UTF-8');
            $fil = $absence->etudiant ? trim((string) $absence->etudiant->filiere) : '';
            $filNorm = mb_strtolower($fil, 'UTF-8');
            if ($filNorm !== $depNorm) {
                return response()->json([
                    'success' => false,
                    'message' => "Vous n'êtes pas autorisé à traiter cette absence (hors de votre filière)"
                ], 403);
            }
        }

        $absence = $enseignant->rejeterAbsence($id, $request->motif_refus);

        return response()->json([
            'success' => true,
            'message' => 'Absence refusée avec succès',
            'data' => $absence
        ]);
    }

    /**
     * Emploi du temps de l'enseignant (liste de documents type 'emploi_temps')
     */
    public function emploiDuTemps(Request $request)
    {
        $enseignant = Auth::user()->enseignant;
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $documents = Document::query()
            ->where('type', 'emploi_temps')
            ->where('est_public', true)
            ->when(true, function ($q) use ($enseignant) {
                // Filtrer par métadonnée JSON si présente
                return $q->where('donnees_document->enseignant_id', $enseignant->id);
            })
            ->orderByDesc('date_generation')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Télécharger un emploi du temps en PDF si l'enseignant en est le destinataire
     */
    public function telechargerEmploiDuTemps($id)
    {
        $enseignant = Auth::user()->enseignant;
        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil enseignant non trouvé'
            ], 404);
        }

        $doc = Document::where('id', $id)
            ->where('type', 'emploi_temps')
            ->where('est_public', true)
            ->where('donnees_document->enseignant_id', $enseignant->id)
            ->firstOrFail();

        if (!$doc->chemin_fichier || !Storage::disk('public')->exists($doc->chemin_fichier)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier introuvable'
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($doc->chemin_fichier);
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf'
        ]);
    }
}
