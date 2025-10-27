<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModeleDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type_document',
        'chemin_modele',
        'est_actif',
        'champs_requis',
        'form_schema',
        'description',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'champs_requis' => 'array',
        'form_schema' => 'array',
    ];

    // Relations
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Méthodes métier
    public function creerModele($nom, $typeDocument, $cheminModele, $champsRequis = [], $description = null)
    {
        return $this->create([
            'nom' => $nom,
            'type_document' => $typeDocument,
            'chemin_modele' => $cheminModele,
            'champs_requis' => $champsRequis,
            'description' => $description,
            'est_actif' => true,
        ]);
    }

    public function modifierModele($nom = null, $cheminModele = null, $champsRequis = null, $description = null)
    {
        $data = [];
        
        if ($nom !== null) $data['nom'] = $nom;
        if ($cheminModele !== null) $data['chemin_modele'] = $cheminModele;
        if ($champsRequis !== null) $data['champs_requis'] = $champsRequis;
        if ($description !== null) $data['description'] = $description;
        
        return $this->update($data);
    }

    public function desactiverModele()
    {
        $this->update(['est_actif' => false]);
    }

    public function activerModele()
    {
        $this->update(['est_actif' => true]);
    }

    // Méthodes supplémentaires
    public function dupliquer($nouveauNom = null)
    {
        $nouveauModele = $this->replicate();
        $nouveauModele->nom = $nouveauNom ?? $this->nom . '_copie';
        $nouveauModele->chemin_modele = $this->genererNouveauChemin();
        $nouveauModele->est_actif = false; // Désactivé par défaut
        $nouveauModele->save();
        
        return $nouveauModele;
    }

    private function genererNouveauChemin()
    {
        $extension = pathinfo($this->chemin_modele, PATHINFO_EXTENSION);
        $nomSansExtension = pathinfo($this->chemin_modele, PATHINFO_FILENAME);
        $dossier = dirname($this->chemin_modele);
        
        return $dossier . '/' . $nomSansExtension . '_copie_' . time() . '.' . $extension;
    }

    public function validerChamps($donnees)
    {
        $champsRequis = $this->champs_requis ?? [];
        $champsManquants = [];
        
        foreach ($champsRequis as $champ) {
            if (!isset($donnees[$champ]) || empty($donnees[$champ])) {
                $champsManquants[] = $champ;
            }
        }
        
        return empty($champsManquants) ? true : $champsManquants;
    }

    public function genererDocument($donnees, $utilisateurId)
    {
        // Valider les champs requis
        $validation = $this->validerChamps($donnees);
        if ($validation !== true) {
            throw new \Exception('Champs manquants: ' . implode(', ', $validation));
        }
        
        // Créer le document
        $document = Document::create([
            'modele_document_id' => $this->id,
            'etudiant_id' => $utilisateurId,
            'type' => $this->type_document,
            'nom' => $this->genererNomDocument($donnees),
            'chemin_fichier' => $this->genererCheminDocument($donnees),
            'donnees_document' => $donnees,
            'est_public' => true,
        ]);
        
        return $document;
    }

    private function genererNomDocument($donnees)
    {
        $nom = $this->nom;
        if (isset($donnees['numero_etudiant'])) {
            $nom .= '_' . $donnees['numero_etudiant'];
        }
        $nom .= '_' . now()->format('Y-m-d');
        
        return $nom;
    }

    private function genererCheminDocument($donnees)
    {
        $dossier = 'documents/' . $this->type_document . '/' . now()->year;
        if (isset($donnees['filiere'])) {
            $dossier .= '/' . $donnees['filiere'];
        }
        
        return $dossier . '/' . $this->genererNomDocument($donnees) . '.pdf';
    }

    /**
     * Définir ce modèle comme défaut (actif) pour son type_document
     * et désactiver tous les autres du même type.
     */
    public function setAsDefault(): void
    {
        DB::transaction(function () {
            static::where('type_document', $this->type_document)
                ->where('id', '!=', $this->id)
                ->update(['est_actif' => false]);
            $this->est_actif = true;
            $this->save();
        });
    }

    public function getStatistiques()
    {
        return [
            'total_documents' => $this->documents()->count(),
            'documents_ce_mois' => $this->documents()
                ->whereMonth('date_generation', now()->month)
                ->whereYear('date_generation', now()->year)
                ->count(),
            'derniere_utilisation' => $this->documents()
                ->orderBy('date_generation', 'desc')
                ->first()?->date_generation,
        ];
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type_document', $type);
    }

    // Extraction des placeholders depuis le fichier modèle (HTML)
    public function extrairePlaceholders(): array
    {
        $path = $this->resoudreChemin($this->chemin_modele);
        $content = @file_get_contents($path) ?: '';
        // Capture {{ key }} ou {{key}}
        preg_match_all('/\{\{\s*([^}]+)\s*\}\}/', $content, $matches);
        $raw = array_unique(array_map('trim', $matches[1] ?? []));
        // Normaliser et filtrer variables techniques
        $placeholders = [];
        foreach ($raw as $p) {
            if ($p === '' || str_contains($p, '<') || str_contains($p, '>')) continue;
            $placeholders[] = $p;
        }
        sort($placeholders);
        return $placeholders;
    }

    private function resoudreChemin(string $path): string
    {
        $candidates = [
            $path,
            base_path($path),
            resource_path(trim($path, '/')),
            resource_path('templates/'.trim(basename($path), '/')),
            resource_path('views/'.trim($path, '/')),
            storage_path('app/'.trim($path, '/')),
            storage_path('app/public/'.trim($path, '/')),
        ];
        foreach ($candidates as $c) {
            if (@is_file($c)) return $c;
        }
        return $path;
    }
}
