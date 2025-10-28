<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enseignant extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'matricule',
        'matieres_enseignees',
        'bureau',
        'departement',
    ];

    protected $casts = [
        'matieres_enseignees' => 'array',
    ];

    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    // Relation avec les absences à traiter
    public function absencesATraiter()
    {
        return $this->hasMany(Absence::class);
    }

    // Méthodes métier
    public function classes()
    {
        return $this->belongsToMany(Classe::class, 'classe_enseignant');
    }

    public function validerAbsence($absenceId, $motifRefus = null)
    {
        $absence = Absence::findOrFail($absenceId);
        
        if ($motifRefus) {
            $absence->update([
                'statut' => 'refusee',
                'motif_refus' => $motifRefus,
                'date_traitement' => now(),
                'enseignant_id' => $this->id,
            ]);
        } else {
            $absence->update([
                'statut' => 'validee',
                'date_traitement' => now(),
                'enseignant_id' => $this->id,
            ]);
        }
        
        return $absence;
    }

    public function consulterAbsencesClasse($filiere = null, $annee = null)
    {
        $query = Absence::with(['etudiant.utilisateur'])
            ->where('statut', 'en_attente');
        
        if ($filiere) {
            $query->whereHas('etudiant', function($q) use ($filiere) {
                $q->where('filiere', $filiere);
            });
        } elseif (!empty($this->departement)) {
            // Par défaut, restreindre à la filière du département de l'enseignant
            $dep = $this->departement;
            $query->whereHas('etudiant', function($q) use ($dep) {
                $q->where('filiere', $dep);
            });
        }
        
        if ($annee) {
            $query->whereHas('etudiant', function($q) use ($annee) {
                $q->where('annee', $annee);
            });
        }
        
        return $query->get();
    }

    // Méthodes supplémentaires
    public function absencesEnAttente()
    {
        $query = Absence::with(['etudiant.utilisateur'])
            ->where('statut', 'en_attente');

        if (!empty($this->departement)) {
            $dep = $this->departement;
            $query->whereHas('etudiant', function($q) use ($dep) {
                $q->where('filiere', $dep);
            });
        }

        return $query->orderBy('date_declaration', 'asc')->get();
    }

    public function absencesTraitees()
    {
        return Absence::with(['etudiant.utilisateur'])
            ->where('enseignant_id', $this->id)
            ->whereIn('statut', ['validee', 'refusee'])
            ->orderBy('date_traitement', 'desc')
            ->get();
    }

    public function statistiquesAbsences()
    {
        return [
            'en_attente' => Absence::where('statut', 'en_attente')->count(),
            'validees' => Absence::where('enseignant_id', $this->id)
                ->where('statut', 'validee')->count(),
            'refusees' => Absence::where('enseignant_id', $this->id)
                ->where('statut', 'refusee')->count(),
        ];
    }

    public function rejeterAbsence($absenceId, $motifRefus)
    {
        return $this->validerAbsence($absenceId, $motifRefus);
    }
}
