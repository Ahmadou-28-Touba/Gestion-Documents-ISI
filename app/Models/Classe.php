<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = [
        'filiere',
        'annee',
        'groupe',
        'label',
    ];

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'classe_enseignant')->withPivot('matiere');
    }

    public function etudiants()
    {
        return $this->hasMany(Etudiant::class, 'classe_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'classe_id');
    }
}
