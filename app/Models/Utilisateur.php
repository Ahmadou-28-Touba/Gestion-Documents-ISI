<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations polymorphes pour les rôles
    public function etudiant()
    {
        return $this->hasOne(Etudiant::class);
    }

    public function enseignant()
    {
        return $this->hasOne(Enseignant::class);
    }

    public function administrateur()
    {
        return $this->hasOne(Administrateur::class);
    }

    public function directeur()
    {
        return $this->hasOne(Directeur::class);
    }

    // Relation avec les notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Méthodes pour vérifier les rôles
    public function isEtudiant()
    {
        return $this->role === 'etudiant';
    }

    public function isEnseignant()
    {
        return $this->role === 'enseignant';
    }

    public function isAdministrateur()
    {
        return $this->role === 'administrateur';
    }

    public function isDirecteur()
    {
        return $this->role === 'directeur';
    }
}
