<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'enseignant_id',
        'date_debut',
        'date_fin',
        'motif',
        'statut',
        'justificatif_chemin',
        'motif_refus',
        'date_declaration',
        'date_traitement',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_declaration' => 'datetime',
        'date_traitement' => 'datetime',
    ];

    // Relations
    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    // Méthodes métier
    public function declarer()
    {
        $this->update([
            'statut' => 'en_attente',
            'date_declaration' => now(),
        ]);
    }

    public function valider($enseignantId)
    {
        $this->update([
            'statut' => 'validee',
            'enseignant_id' => $enseignantId,
            'date_traitement' => now(),
        ]);
    }

    public function rejeter($enseignantId, $motifRefus)
    {
        $this->update([
            'statut' => 'refusee',
            'enseignant_id' => $enseignantId,
            'motif_refus' => $motifRefus,
            'date_traitement' => now(),
        ]);
    }

    // Méthodes supplémentaires
    public function peutEtreModifiee()
    {
        return $this->statut === 'en_attente';
    }

    public function peutEtreValidee()
    {
        return $this->statut === 'en_attente';
    }

    public function peutEtreRejetee()
    {
        return $this->statut === 'en_attente';
    }

    public function getDureeEnJours()
    {
        return $this->date_debut->diffInDays($this->date_fin) + 1;
    }

    public function estEnConflit($dateDebut, $dateFin)
    {
        return $this->date_debut <= $dateFin && $this->date_fin >= $dateDebut;
    }

    public function getStatutLabel()
    {
        $labels = [
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'refusee' => 'Refusée'
        ];
        
        return $labels[$this->statut] ?? $this->statut;
    }

    public function getStatutBadgeClass()
    {
        $classes = [
            'en_attente' => 'bg-warning',
            'validee' => 'bg-success',
            'refusee' => 'bg-danger'
        ];
        
        return $classes[$this->statut] ?? 'bg-secondary';
    }

    public function getDelaiTraitement()
    {
        if ($this->date_traitement) {
            return $this->date_declaration->diffInHours($this->date_traitement);
        }
        
        return $this->date_declaration->diffInHours(now());
    }

    public function estEnRetard()
    {
        return $this->statut === 'en_attente' && $this->getDelaiTraitement() > 72; // 3 jours
    }

    public function annuler()
    {
        if ($this->statut === 'en_attente') {
            $this->update(['statut' => 'annulee']);
            return true;
        }
        
        return false;
    }

    public function dupliquer($nouvellesDates = [])
    {
        $nouvelleAbsence = $this->replicate();
        $nouvelleAbsence->statut = 'en_attente';
        $nouvelleAbsence->enseignant_id = null;
        $nouvelleAbsence->motif_refus = null;
        $nouvelleAbsence->date_declaration = now();
        $nouvelleAbsence->date_traitement = null;
        
        if (!empty($nouvellesDates)) {
            $nouvelleAbsence->date_debut = $nouvellesDates['date_debut'] ?? $this->date_debut;
            $nouvelleAbsence->date_fin = $nouvellesDates['date_fin'] ?? $this->date_fin;
        }
        
        $nouvelleAbsence->save();
        return $nouvelleAbsence;
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeRefusees($query)
    {
        return $query->where('statut', 'refusee');
    }
}
