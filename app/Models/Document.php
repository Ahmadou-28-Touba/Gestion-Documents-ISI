<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'modele_document_id',
        'etudiant_id',
        'administrateur_id',
        'type',
        'nom',
        'chemin_fichier',
        'date_generation',
        'est_public',
        'donnees_document',
    ];

    protected $casts = [
        'date_generation' => 'datetime',
        'est_public' => 'boolean',
        'donnees_document' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔗 Relations
    |--------------------------------------------------------------------------
    */

    public function modeleDocument()
    {
        return $this->belongsTo(ModeleDocument::class, 'modele_document_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(Etudiant::class, 'etudiant_id');
    }

    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class, 'administrateur_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Méthodes métier
    |--------------------------------------------------------------------------
    */

    public function generer()
    {
        // Logique de génération du document (ex. PDF)
        $this->update([
            'date_generation' => now(),
        ]);
    }

    public function telecharger()
    {
        if ($this->estTelechargeable()) {
            return response()->download(storage_path('app/' . $this->chemin_fichier));
        }

        abort(404, 'Fichier introuvable');
    }

    public function archiver()
    {
        $this->update(['est_public' => false]);
    }

    public function desarchiver()
    {
        $this->update(['est_public' => true]);
    }

    public function changerVisibilite($public = true)
    {
        $this->update(['est_public' => $public]);
    }

    public function dupliquer($nouveauNom = null)
    {
        $nouveauDocument = $this->replicate();
        $nouveauDocument->nom = $nouveauNom ?? $this->nom . '_copie';
        $nouveauDocument->chemin_fichier = $this->genererNouveauChemin();
        $nouveauDocument->date_generation = now();
        $nouveauDocument->save();

        return $nouveauDocument;
    }

    private function genererNouveauChemin()
    {
        $extension = pathinfo($this->chemin_fichier, PATHINFO_EXTENSION);
        $nomSansExtension = pathinfo($this->chemin_fichier, PATHINFO_FILENAME);
        $dossier = dirname($this->chemin_fichier);

        return $dossier . '/' . $nomSansExtension . '_copie_' . time() . '.' . $extension;
    }

    /*
    |--------------------------------------------------------------------------
    | 📁 Gestion du fichier
    |--------------------------------------------------------------------------
    */

    public function estTelechargeable()
    {
        return Storage::exists($this->chemin_fichier);
    }

    public function getTailleFichier()
    {
        if ($this->estTelechargeable()) {
            return Storage::size($this->chemin_fichier);
        }
        return 0;
    }

    public function getTailleFichierFormatee()
    {
        $taille = $this->getTailleFichier();
        $unites = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($taille >= 1024 && $i < count($unites) - 1) {
            $taille /= 1024;
            $i++;
        }

        return round($taille, 2) . ' ' . $unites[$i];
    }

    public function getCheminCompletAttribute()
    {
        return asset('storage/' . $this->chemin_fichier);
    }

    /*
    |--------------------------------------------------------------------------
    | 🔍 Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePublics($query)
    {
        return $query->where('est_public', true);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePourEtudiant($query, $etudiantId)
    {
        return $query->where('etudiant_id', $etudiantId);
    }

    public function scopeRecents($query, $jours = 30)
    {
        return $query->where('date_generation', '>=', now()->subDays($jours));
    }

    public function scopeArchives($query)
    {
        return $query->where('est_public', false);
    }

    /*
    |--------------------------------------------------------------------------
    | 📊 Statistiques globales (utile pour Dashboard Admin)
    |--------------------------------------------------------------------------
    */

    public static function statistiquesGlobales()
    {
        return [
            'total' => self::count(),
            'publics' => self::where('est_public', true)->count(),
            'archives' => self::where('est_public', false)->count(),
            'recents' => self::where('date_generation', '>=', now()->subMonth())->count(),
        ];
    }
}
