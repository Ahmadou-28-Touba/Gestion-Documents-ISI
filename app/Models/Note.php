<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'enseignant_id',
        'classe_id',
        'matiere',
        'valeur',
        'type_controle',
        'date',
        'commentaire',
        'est_valide',
        'periode',
    ];

    protected $casts = [
        'date' => 'date',
        'est_valide' => 'boolean',
        'valeur' => 'float',
    ];

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id');
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }
}
