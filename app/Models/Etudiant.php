<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'numero_etudiant',
        'filiere',
        'annee',
        'groupe',
        'date_inscription',
    ];

    protected $casts = [
        'date_inscription' => 'date',
    ];

    protected static function booted()
    {
        static::saving(function (Etudiant $etudiant) {
            $filiere = trim((string) $etudiant->filiere);
            $annee = trim((string) $etudiant->annee);
            $groupe = $etudiant->groupe !== null && $etudiant->groupe !== '' ? trim((string) $etudiant->groupe) : null;
            if ($filiere !== '' && $annee !== '') {
                $classe = \App\Models\Classe::firstOrCreate([
                    'filiere' => $filiere,
                    'annee' => $annee,
                    'groupe' => $groupe,
                ]);
                $etudiant->classe_id = $classe->id;
            }
        });
    }

    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    // Relation avec les absences
    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    // Relation avec les documents
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'etudiant_id');
    }

    // Méthodes métier
    public function consulterDocuments()
    {
        return $this->documents()
            ->where('est_public', true)
            ->with('modeleDocument')
            ->orderBy('date_generation', 'desc')
            ->get();
    }

    public function declarerAbsence($dateDebut, $dateFin, $motif, $justificatifChemin = null)
    {
        return $this->absences()->create([
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'motif' => $motif,
            'justificatif_chemin' => $justificatifChemin,
            'statut' => 'en_attente',
        ]);
    }

    // Méthodes supplémentaires pour la gestion des absences
    public function mesAbsencesEnAttente()
    {
        return $this->absences()->where('statut', 'en_attente')->get();
    }

    public function mesAbsencesValidees()
    {
        return $this->absences()->where('statut', 'validee')->get();
    }

    public function mesAbsencesRefusees()
    {
        return $this->absences()->where('statut', 'refusee')->get();
    }

    // Méthodes pour les documents
    public function telechargerDocument($documentId)
    {
        $document = $this->documents()
            ->where('id', $documentId)
            ->where('est_public', true)
            ->first();
        
        if (!$document) {
            return null;
        }

        return $document;
    }

    // Méthodes de validation
    public function peutDeclarerAbsence($dateDebut, $dateFin)
    {
        // Vérifier qu'il n'y a pas de conflit avec une absence existante
        $conflit = $this->absences()
            ->where(function($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('date_debut', [$dateDebut, $dateFin])
                      ->orWhereBetween('date_fin', [$dateDebut, $dateFin])
                      ->orWhere(function($q) use ($dateDebut, $dateFin) {
                          $q->where('date_debut', '<=', $dateDebut)
                            ->where('date_fin', '>=', $dateFin);
                      });
            })
            ->where('statut', '!=', 'refusee')
            ->exists();

        return !$conflit;
    }
}
