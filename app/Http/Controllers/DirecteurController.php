<?php

namespace App\Http\Controllers;

use App\Models\Directeur;
use App\Models\Document;
use App\Models\Absence;
use App\Models\Utilisateur;
use App\Models\Etudiant;
use App\Models\ModeleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class DirecteurController extends Controller
{
    public function dashboard()
    {
        $directeur = Auth::user()->directeur;
        
        if (!$directeur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil directeur non trouvé'
            ], 404);
        }

        $statistiques = Cache::remember('directeur_stats_globales', now()->addMinutes(10), function () {
            return $this->getStatistiquesGenerales();
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'directeur' => $directeur,
                'statistiques' => $statistiques,
                'documents_recents' => Document::with(['etudiant.utilisateur', 'modeleDocument'])
                    ->latest()
                    ->limit(5)
                    ->get(),
                'absences_recentes' => Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur'])
                    ->latest()
                    ->limit(5)
                    ->get()
            ]
        ]);
    }

    /**
     * Liste des filières disponibles (distinctes) pour filtres UI
     */
    public function filieres()
    {
        $filieres = Etudiant::query()
            ->whereNotNull('filiere')
            ->distinct()
            ->orderBy('filiere')
            ->pluck('filiere');

        return response()->json([
            'success' => true,
            'data' => $filieres
        ]);
    }

    public function profil()
    {
        $directeur = Auth::user()->directeur;
        
        if (!$directeur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil directeur non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'utilisateur' => Auth::user(),
                'directeur' => $directeur
            ]
        ]);
    }

    public function modifierProfil(Request $request)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:utilisateurs,email,' . Auth::id(),
            'signature' => 'sometimes|string|max:1000'
        ]);

        $utilisateur = Auth::user();
        $directeur = $utilisateur->directeur;

        if (!$directeur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil directeur non trouvé'
            ], 404);
        }

        // Mettre à jour les données utilisateur
        $utilisateur->update($request->only(['nom', 'prenom', 'email']));

        // Mettre à jour les données directeur
        $directeur->update($request->only(['signature']));

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'utilisateur' => $utilisateur->fresh(),
                'directeur' => $directeur->fresh()
            ]
        ]);
    }

    public function consulterStatistiques()
    {
        $statistiques = $this->getStatistiquesGenerales();

        return response()->json([
            'success' => true,
            'data' => $statistiques
        ]);
    }

    public function genererRapportAnnuel(Request $request, $annee = null)
    {
        $annee = $annee ?? $request->get('annee', now()->year);
        
        $rapport = Cache::remember("directeur_rapport_annuel_{$annee}", now()->addMinutes(10), function () use ($annee) {
            return [
                'annee' => $annee,
                'periode' => [
                    'debut' => $annee . '-01-01',
                    'fin' => $annee . '-12-31'
                ],
                'statistiques' => $this->getStatistiquesAnnuelles($annee),
                'documents_generes' => $this->getDocumentsAnnuels($annee),
                'absences_traitees' => $this->getAbsencesAnnuelles($annee),
                'utilisateurs_actifs' => $this->getUtilisateursActifs($annee)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rapport
        ]);
    }

    public function genererRapportAnnuelPdf(Request $request, $annee = null)
    {
        $annee = $annee ?? $request->get('annee', now()->year);
        $rapport = [
            'annee' => $annee,
            'statistiques' => $this->getStatistiquesAnnuelles($annee),
            'documents_generes' => $this->getDocumentsAnnuels($annee),
            'absences_traitees' => $this->getAbsencesAnnuelles($annee),
        ];

        $html = view('reports.rapport_annuel_pdf', ['rapport' => $rapport])->render();

        // Génération PDF via Dompdf
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4', 'portrait');
        $filename = 'rapport_annuel_'.$annee.'.pdf';
        return $pdf->download($filename);
    }

    public function exportAbsences(Request $request)
    {
        $filtres = $request->only(['date_debut', 'date_fin', 'statut', 'filiere']);
        
        $absences = Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur'])
            ->when($filtres['date_debut'], function ($query, $date) {
                return $query->where('date_debut', '>=', $date);
            })
            ->when($filtres['date_fin'], function ($query, $date) {
                return $query->where('date_fin', '<=', $date);
            })
            ->when($filtres['statut'], function ($query, $statut) {
                return $query->where('statut', $statut);
            })
            ->when($filtres['filiere'], function ($query, $filiere) {
                return $query->whereHas('etudiant', function ($q) use ($filiere) {
                    $q->where('filiere', $filiere);
                });
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $absences
        ]);
    }

    public function exportDocuments(Request $request)
    {
        $filtres = $request->only(['date_debut', 'date_fin', 'type', 'utilisateur_id', 'filiere']);
        
        $documents = Document::with(['etudiant.utilisateur', 'modeleDocument'])
            ->when($filtres['date_debut'], function ($query, $date) {
                return $query->where('date_generation', '>=', $date);
            })
            ->when($filtres['date_fin'], function ($query, $date) {
                return $query->where('date_generation', '<=', $date);
            })
            ->when($filtres['type'], function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($filtres['utilisateur_id'], function ($query, $userId) {
                return $query->where('etudiant_id', $userId);
            })
            ->when($filtres['filiere'], function ($query, $filiere) {
                return $query->whereHas('etudiant', function ($q) use ($filiere) {
                    $q->where('filiere', $filiere);
                });
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    private function getStatistiquesGenerales()
    {
        return [
            'utilisateurs' => [
                'total' => Utilisateur::count(),
                'par_role' => Utilisateur::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role')
                    ->toArray(),
                'nouveaux_ce_mois' => Utilisateur::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count()
            ],
            'documents' => [
                'total' => Document::count(),
                'publics' => Document::where('est_public', true)->count(),
                'archives' => Document::where('est_public', false)->count(),
                'par_type' => Document::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type')
                    ->toArray(),
                'ce_mois' => Document::whereMonth('date_generation', now()->month)
                    ->whereYear('date_generation', now()->year)
                    ->count()
            ],
            'absences' => [
                'total' => Absence::count(),
                'en_attente' => Absence::enAttente()->count(),
                'validees' => Absence::validees()->count(),
                'refusees' => Absence::refusees()->count(),
                'ce_mois' => Absence::whereMonth('date_declaration', now()->month)
                    ->whereYear('date_declaration', now()->year)
                    ->count()
            ],
            'modeles' => [
                'total' => ModeleDocument::count(),
                'actifs' => ModeleDocument::actifs()->count(),
                'inactifs' => ModeleDocument::where('est_actif', false)->count()
            ]
        ];
    }

    private function getStatistiquesAnnuelles($annee)
    {
        return [
            'documents_generes' => Document::whereYear('date_generation', $annee)->count(),
            'absences_declarees' => Absence::whereYear('date_declaration', $annee)->count(),
            'utilisateurs_inscrits' => Utilisateur::whereYear('created_at', $annee)->count(),
            'evolution_mensuelle' => $this->getEvolutionMensuelle($annee)
        ];
    }

    private function getEvolutionMensuelle($annee)
    {
        $evolution = [];
        for ($mois = 1; $mois <= 12; $mois++) {
            $evolution[$mois] = [
                'documents' => Document::whereYear('date_generation', $annee)
                    ->whereMonth('date_generation', $mois)
                    ->count(),
                'absences' => Absence::whereYear('date_declaration', $annee)
                    ->whereMonth('date_declaration', $mois)
                    ->count(),
                'utilisateurs' => Utilisateur::whereYear('created_at', $annee)
                    ->whereMonth('created_at', $mois)
                    ->count()
            ];
        }
        return $evolution;
    }

    private function getDocumentsAnnuels($annee)
    {
        return Document::with(['etudiant.utilisateur', 'modeleDocument'])
            ->whereYear('date_generation', $annee)
            ->orderBy('date_generation', 'desc')
            ->get();
    }

    private function getAbsencesAnnuelles($annee)
    {
        return Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur'])
            ->whereYear('date_declaration', $annee)
            ->orderBy('date_declaration', 'desc')
            ->get();
    }

    private function getUtilisateursActifs($annee)
    {
        return Utilisateur::with(['etudiant', 'enseignant', 'administrateur', 'directeur'])
            ->whereYear('created_at', $annee)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
