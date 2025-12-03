<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Etudiant;
use App\Models\Enseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\NotificationController;

class AbsenceController extends Controller
{
    /**
     * Afficher la liste des absences
     */
    public function index(Request $request)
    {
        $query = Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur']);

        // Filtres selon le rôle
        $utilisateur = Auth::user();
        
        if ($utilisateur->isEtudiant()) {
            $etudiant = $utilisateur->etudiant;
            if ($etudiant) {
                $query->where('etudiant_id', $etudiant->id);
            }
        } elseif ($utilisateur->isEnseignant()) {
            $enseignant = $utilisateur->enseignant;
            if ($enseignant) {
                // Afficher les absences des étudiants de la filière correspondant au département de l'enseignant
                $dep = trim((string) $enseignant->departement);
                if ($dep !== '') {
                    $norm = mb_strtolower($dep, 'UTF-8');
                    $query->whereHas('etudiant', function ($q) use ($norm) {
                        $q->whereRaw('TRIM(LOWER(filiere)) = ?', [$norm]);
                    });
                }
            }
        }

        // Filtres supplémentaires
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('date_debut')) {
            $query->where('date_debut', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_fin', '<=', $request->date_fin);
        }

        if ($request->has('etudiant_id')) {
            $query->where('etudiant_id', $request->etudiant_id);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'date_declaration');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $absences = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $absences
        ]);
    }

    /**
     * Afficher une absence spécifique
     */
    public function show($id)
    {
        $absence = Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur'])->findOrFail($id);

        // Vérifier les permissions
        $utilisateur = Auth::user();
        if ($utilisateur->isEtudiant()) {
            $etudiant = $utilisateur->etudiant;
            if (!$etudiant || $absence->etudiant_id !== $etudiant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les permissions pour voir cette absence'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $absence
        ]);
    }

    /**
     * Créer une nouvelle absence
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'required|string|max:1000',
            'justificatif' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les étudiants peuvent déclarer des absences'
            ], 403);
        }

        $etudiant = $utilisateur->etudiant;
        if (!$etudiant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
        }

        // Vérifier les conflits d'absences
        $conflits = Absence::where('etudiant_id', $etudiant->id)
            ->where('statut', '!=', 'refusee')
            ->where(function ($query) use ($request) {
                $query->whereBetween('date_debut', [$request->date_debut, $request->date_fin])
                    ->orWhereBetween('date_fin', [$request->date_debut, $request->date_fin])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('date_debut', '<=', $request->date_debut)
                          ->where('date_fin', '>=', $request->date_fin);
                    });
            })
            ->exists();

        if ($conflits) {
            return response()->json([
                'success' => false,
                'message' => 'Une absence existe déjà pour cette période'
            ], 422);
        }

        // Gérer le justificatif
        $justificatifChemin = null;
        if ($request->hasFile('justificatif')) {
            $justificatifChemin = $request->file('justificatif')->store('justificatifs', 'public');
        }

        $absence = Absence::create([
            'etudiant_id' => $etudiant->id,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'motif' => $request->motif,
            'justificatif_chemin' => $justificatifChemin,
            'statut' => 'en_attente',
            'date_declaration' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absence déclarée avec succès',
            'data' => $absence->load(['etudiant.utilisateur'])
        ], 201);
    }

    /**
     * Mettre à jour une absence
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|date|after_or_equal:today',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'motif' => 'sometimes|string|max:1000',
            'justificatif' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $absence = Absence::findOrFail($id);

        // Vérifier les permissions
        $utilisateur = Auth::user();
        if ($utilisateur->isEtudiant()) {
            $etudiant = $utilisateur->etudiant;
            if (!$etudiant || $absence->etudiant_id !== $etudiant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les permissions pour modifier cette absence'
                ], 403);
            }
        }

        if (!$absence->peutEtreModifiee()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette absence ne peut plus être modifiée'
            ], 422);
        }

        // Gérer le justificatif
        if ($request->hasFile('justificatif')) {
            // Supprimer l'ancien justificatif
            if ($absence->justificatif_chemin) {
                \Storage::disk('public')->delete($absence->justificatif_chemin);
            }
            
            $justificatifChemin = $request->file('justificatif')->store('justificatifs', 'public');
            $request->merge(['justificatif_chemin' => $justificatifChemin]);
        }

        $absence->update($request->only(['date_debut', 'date_fin', 'motif', 'justificatif_chemin']));

        return response()->json([
            'success' => true,
            'message' => 'Absence mise à jour avec succès',
            'data' => $absence->load(['etudiant.utilisateur', 'enseignant.utilisateur'])
        ]);
    }

    /**
     * Supprimer une absence
     */
    public function destroy($id)
    {
        $absence = Absence::findOrFail($id);

        // Vérifier les permissions
        $utilisateur = Auth::user();
        if ($utilisateur->isEtudiant()) {
            $etudiant = $utilisateur->etudiant;
            if (!$etudiant || $absence->etudiant_id !== $etudiant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les permissions pour supprimer cette absence'
                ], 403);
            }
        }

        if (!$absence->peutEtreModifiee()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette absence ne peut plus être supprimée'
            ], 422);
        }

        // Supprimer le justificatif
        if ($absence->justificatif_chemin) {
            \Storage::disk('public')->delete($absence->justificatif_chemin);
        }

        $absence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Absence supprimée avec succès'
        ]);
    }

    /**
     * Valider une absence
     */
    public function valider(Request $request, $id)
    {
        $absence = Absence::findOrFail($id);

        // Vérifier les permissions (seul le directeur peut valider)
        $utilisateur = Auth::user();
        if (!$utilisateur->isDirecteur()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le directeur peut valider les absences'
            ], 403);
        }

        if (!$absence->peutEtreValidee()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette absence ne peut plus être validée'
            ], 422);
        }

        // On ne renseigne plus d'enseignant traitant : décision prise par le directeur
        $absence->valider(null);

        // Notifier l'étudiant que son absence a été validée
        NotificationController::notifierAbsenceValidee($absence);

        return response()->json([
            'success' => true,
            'message' => 'Absence validée avec succès',
            'data' => $absence->load(['etudiant.utilisateur', 'enseignant.utilisateur'])
        ]);
    }

    /**
     * Rejeter une absence
     */
    public function rejeter(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'motif_refus' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $absence = Absence::findOrFail($id);

        // Vérifier les permissions (seul le directeur peut rejeter)
        $utilisateur = Auth::user();
        if (!$utilisateur->isDirecteur()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le directeur peut rejeter les absences'
            ], 403);
        }

        if (!$absence->peutEtreRejetee()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette absence ne peut plus être rejetée'
            ], 422);
        }

        // On ne renseigne plus d'enseignant traitant : décision prise par le directeur
        $absence->rejeter(null, $request->motif_refus);

        // Notifier l'étudiant que son absence a été refusée
        NotificationController::notifierAbsenceRefusee($absence);

        return response()->json([
            'success' => true,
            'message' => 'Absence rejetée avec succès',
            'data' => $absence->load(['etudiant.utilisateur', 'enseignant.utilisateur'])
        ]);
    }

    /**
     * Annuler une absence
     */
    public function annuler($id)
    {
        $absence = Absence::findOrFail($id);

        // Vérifier les permissions
        $utilisateur = Auth::user();
        if ($utilisateur->isEtudiant()) {
            $etudiant = $utilisateur->etudiant;
            if (!$etudiant || $absence->etudiant_id !== $etudiant->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas les permissions pour annuler cette absence'
                ], 403);
            }
        }

        if (!$absence->annuler()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette absence ne peut plus être annulée'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Absence annulée avec succès',
            'data' => $absence
        ]);
    }

    /**
     * Obtenir les statuts disponibles
     */
    public function getStatuts()
    {
        $statuts = [
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'refusee' => 'Refusée',
            'annulee' => 'Annulée'
        ];

        return response()->json([
            'success' => true,
            'data' => $statuts
        ]);
    }

    /**
     * Obtenir les statistiques des absences
     */
    public function statistiques()
    {
        $utilisateur = Auth::user();
        
        if ($utilisateur->isEtudiant()) {
            $etudiant = $utilisateur->etudiant;
            if (!$etudiant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil étudiant non trouvé'
                ], 404);
            }

            $statistiques = [
                'total_absences' => Absence::where('etudiant_id', $etudiant->id)->count(),
                'absences_en_attente' => Absence::where('etudiant_id', $etudiant->id)->enAttente()->count(),
                'absences_validees' => Absence::where('etudiant_id', $etudiant->id)->validees()->count(),
                'absences_refusees' => Absence::where('etudiant_id', $etudiant->id)->refusees()->count(),
                'absences_ce_mois' => Absence::where('etudiant_id', $etudiant->id)
                    ->whereMonth('date_declaration', now()->month)
                    ->whereYear('date_declaration', now()->year)
                    ->count()
            ];
        } elseif ($utilisateur->isEnseignant()) {
            $enseignant = $utilisateur->enseignant;
            if (!$enseignant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil enseignant non trouvé'
                ], 404);
            }

            $statistiques = [
                'absences_en_attente' => Absence::enAttente()->count(),
                'absences_validees_par_moi' => Absence::where('enseignant_id', $enseignant->id)->validees()->count(),
                'absences_refusees_par_moi' => Absence::where('enseignant_id', $enseignant->id)->refusees()->count(),
                'absences_en_retard' => Absence::enAttente()->get()->filter(function ($absence) {
                    return $absence->estEnRetard();
                })->count()
            ];
        } else {
            $statistiques = [
                'total_absences' => Absence::count(),
                'absences_en_attente' => Absence::enAttente()->count(),
                'absences_validees' => Absence::validees()->count(),
                'absences_refusees' => Absence::refusees()->count(),
                'absences_en_retard' => Absence::enAttente()->get()->filter(function ($absence) {
                    return $absence->estEnRetard();
                })->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $statistiques
        ]);
    }

    /**
     * Rechercher des absences
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $statut = $request->get('statut');
        $etudiant = $request->get('etudiant_id');

        $absences = Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur'])
            ->when($query, function ($q) use ($query) {
                return $q->where('motif', 'like', "%{$query}%")
                    ->orWhereHas('etudiant.utilisateur', function ($userQuery) use ($query) {
                        $userQuery->where('nom', 'like', "%{$query}%")
                            ->orWhere('prenom', 'like', "%{$query}%");
                    });
            })
            ->when($statut, function ($q) use ($statut) {
                return $q->where('statut', $statut);
            })
            ->when($etudiant, function ($q) use ($etudiant) {
                return $q->where('etudiant_id', $etudiant);
            })
            ->orderBy('date_declaration', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $absences
        ]);
    }
}
