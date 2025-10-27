<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'type',
        'titre',
        'message',
        'donnees',
        'lue',
        'date_lecture'
    ];

    protected $casts = [
        'donnees' => 'array',
        'lue' => 'boolean',
        'date_lecture' => 'datetime'
    ];

    // Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }

    // Scopes
    public function scopeNonLues($query)
    {
        return $query->where('lue', false);
    }

    public function scopeLues($query)
    {
        return $query->where('lue', true);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Méthodes utilitaires
    public function marquerCommeLue()
    {
        $this->update([
            'lue' => true,
            'date_lecture' => now()
        ]);
    }

    public function getTypeLabel()
    {
        $labels = [
            'absence_declaree' => 'Absence déclarée',
            'absence_validee' => 'Absence validée',
            'absence_refusee' => 'Absence refusée',
            'document_genere' => 'Document généré',
            'nouvel_utilisateur' => 'Nouvel utilisateur',
            'systeme' => 'Système',
            'info' => 'Information',
            'warning' => 'Attention',
            'error' => 'Erreur'
        ];

        return $labels[$this->type] ?? $this->type;
    }

    public function getTypeIcon()
    {
        $icons = [
            'absence_declaree' => 'fas fa-calendar-times',
            'absence_validee' => 'fas fa-check-circle',
            'absence_refusee' => 'fas fa-times-circle',
            'document_genere' => 'fas fa-file-alt',
            'nouvel_utilisateur' => 'fas fa-user-plus',
            'systeme' => 'fas fa-cog',
            'info' => 'fas fa-info-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'error' => 'fas fa-exclamation-circle'
        ];

        return $icons[$this->type] ?? 'fas fa-bell';
    }

    public function getTypeColor()
    {
        $colors = [
            'absence_declaree' => 'warning',
            'absence_validee' => 'success',
            'absence_refusee' => 'danger',
            'document_genere' => 'info',
            'nouvel_utilisateur' => 'primary',
            'systeme' => 'secondary',
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'danger'
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    public function getTimeAgo()
    {
        return $this->created_at->diffForHumans();
    }

    public function isRecent()
    {
        return $this->created_at->isAfter(now()->subHours(24));
    }
}
