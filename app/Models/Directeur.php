<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Directeur extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'signature',
    ];

    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // Méthodes métier
    public function genererRapportAnnuel($annee = null)
    {
        $annee = $annee ?? now()->year;
        
        $statistiques = [
            'annee' => $annee,
            'total_etudiants' => Etudiant::where('annee', $annee)->count(),
            'total_absences' => Absence::whereYear('date_declaration', $annee)->count(),
            'absences_validees' => Absence::whereYear('date_declaration', $annee)
                ->where('statut', 'validee')->count(),
            'absences_refusees' => Absence::whereYear('date_declaration', $annee)
                ->where('statut', 'refusee')->count(),
            'total_documents' => Document::whereYear('date_generation', $annee)->count(),
            'documents_par_type' => Document::whereYear('date_generation', $annee)
                ->groupBy('type')
                ->selectRaw('type, count(*) as total')
                ->get(),
        ];
        
        return $statistiques;
    }

    public function consulterStatistiques($periode = 'mois')
    {
        $query = Absence::query();
        
        switch ($periode) {
            case 'jour':
                $query->whereDate('date_declaration', now());
                break;
            case 'semaine':
                $query->whereBetween('date_declaration', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mois':
                $query->whereMonth('date_declaration', now()->month)
                      ->whereYear('date_declaration', now()->year);
                break;
            case 'annee':
                $query->whereYear('date_declaration', now()->year);
                break;
        }
        
        return [
            'periode' => $periode,
            'total_absences' => $query->count(),
            'absences_validees' => $query->where('statut', 'validee')->count(),
            'absences_refusees' => $query->where('statut', 'refusee')->count(),
            'absences_en_attente' => $query->where('statut', 'en_attente')->count(),
            'taux_validation' => $query->count() > 0 ? 
                round(($query->where('statut', 'validee')->count() / $query->count()) * 100, 2) : 0,
        ];
    }

    // Méthodes supplémentaires pour les rapports
    public function statistiquesParFiliere($annee = null)
    {
        $annee = $annee ?? now()->year;
        
        return Etudiant::withCount(['absences' => function($query) use ($annee) {
                $query->whereYear('date_declaration', $annee);
            }])
            ->where('annee', $annee)
            ->groupBy('filiere')
            ->selectRaw('filiere, COUNT(*) as total_etudiants, AVG(absences_count) as moyenne_absences')
            ->get();
    }

    public function statistiquesParMois($annee = null)
    {
        $annee = $annee ?? now()->year;
        
        return Absence::whereYear('date_declaration', $annee)
            ->selectRaw('MONTH(date_declaration) as mois, COUNT(*) as total_absences')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->mapWithKeys(function($item) {
                $mois = [
                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                ];
                return [$mois[$item->mois] => $item->total_absences];
            });
    }

    public function exportAbsences($format = 'excel', $filtres = [])
    {
        $query = Absence::with(['etudiant.utilisateur', 'enseignant.utilisateur']);
        
        if (isset($filtres['date_debut'])) {
            $query->where('date_declaration', '>=', $filtres['date_debut']);
        }
        
        if (isset($filtres['date_fin'])) {
            $query->where('date_declaration', '<=', $filtres['date_fin']);
        }
        
        if (isset($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }
        
        if (isset($filtres['filiere'])) {
            $query->whereHas('etudiant', function($q) use ($filtres) {
                $q->where('filiere', $filtres['filiere']);
            });
        }
        
        return $query->get();
    }

    public function exportDocuments($format = 'excel', $filtres = [])
    {
        $query = Document::with(['etudiant.utilisateur', 'modeleDocument']);
        
        if (isset($filtres['type'])) {
            $query->where('type', $filtres['type']);
        }
        
        if (isset($filtres['date_debut'])) {
            $query->where('date_generation', '>=', $filtres['date_debut']);
        }
        
        if (isset($filtres['date_fin'])) {
            $query->where('date_generation', '<=', $filtres['date_fin']);
        }
        
        return $query->get();
    }

    public function tableauDeBord()
    {
        return [
            'absences_aujourd_hui' => Absence::whereDate('date_declaration', now())->count(),
            'absences_en_attente' => Absence::where('statut', 'en_attente')->count(),
            'documents_generes_aujourd_hui' => Document::whereDate('date_generation', now())->count(),
            'total_etudiants' => Etudiant::count(),
            'total_enseignants' => Enseignant::count(),
            'total_administrateurs' => Administrateur::count(),
            'activite_recente' => $this->getActiviteRecente(),
        ];
    }

    private function getActiviteRecente()
    {
        return [
            'dernieres_absences' => Absence::with(['etudiant.utilisateur'])
                ->orderBy('date_declaration', 'desc')
                ->limit(5)
                ->get(),
            'derniers_documents' => Document::with(['etudiant.utilisateur'])
                ->orderBy('date_generation', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}
