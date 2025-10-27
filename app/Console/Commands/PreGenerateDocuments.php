<?php

namespace App\Console\Commands;

use App\Jobs\RegenererDocument;
use App\Models\Document;
use App\Models\Etudiant;
use App\Models\ModeleDocument;
use App\Models\Administrateur;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PreGenerateDocuments extends Command
{
    protected $signature = 'documents:pregenerate {--types=attestation,certificat : Types de documents à pré-générer (séparés par des virgules)}';

    protected $description = 'Pré-générer les documents (attestations, certificats) pour les étudiants actifs en planifiant des jobs';

    public function handle(): int
    {
        $types = collect(explode(',', (string)$this->option('types')))
            ->map(fn($t) => trim($t))
            ->filter()
            ->values();

        if ($types->isEmpty()) {
            $this->warn('Aucun type fourni. Rien à faire.');
            return self::SUCCESS;
        }

        $modeles = ModeleDocument::query()
            ->whereIn('type_document', $types)
            ->where('est_actif', true)
            ->get()
            ->keyBy('type_document');

        if ($modeles->isEmpty()) {
            $this->warn('Aucun modèle actif trouvé pour les types: '.implode(', ', $types->all()));
            return self::SUCCESS;
        }

        $countScheduled = 0;

        $adminId = Administrateur::query()->value('id');
        if (!$adminId) {
            $this->warn('Aucun administrateur trouvé. Pré-génération ignorée.');
            return self::SUCCESS;
        }

        Etudiant::query()->with('utilisateur')->chunkById(200, function ($etudiants) use ($modeles, &$countScheduled, $adminId) {
            foreach ($etudiants as $etudiant) {
                foreach ($modeles as $type => $modele) {
                    // Vérifier s'il existe déjà un document récent pour l'année en cours
                    $exists = Document::query()
                        ->where('modele_document_id', $modele->id)
                        ->where('etudiant_id', $etudiant->id)
                        ->where('type', $type)
                        ->whereYear('date_generation', now()->year)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Préparer données communes
                    $donnees = [
                        'etudiant' => [
                            'id' => $etudiant->id,
                            'numero_etudiant' => $etudiant->numero_etudiant,
                            'nom' => $etudiant->utilisateur->nom ?? '',
                            'prenom' => $etudiant->utilisateur->prenom ?? '',
                            'filiere' => $etudiant->filiere,
                            'annee' => $etudiant->annee,
                        ],
                        'date_du_jour' => now()->format('Y-m-d'),
                    ];

                    // Créer l'enregistrement Document (sans fichier au départ)
                    $nomBase = ($modele->nom ?? ucfirst($type)) . '_' . ($etudiant->numero_etudiant ?? $etudiant->id);
                    $chemin = 'documents/' . $type . '/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . Str::uuid() . '.pdf';

                    $document = Document::create([
                        'modele_document_id' => $modele->id,
                        'etudiant_id' => $etudiant->id,
                        'administrateur_id' => $adminId,
                        'type' => $type,
                        'nom' => $modele->nom . ' - ' . ($etudiant->utilisateur->nom ?? ''),
                        'chemin_fichier' => $chemin,
                        'donnees_document' => $donnees,
                        'est_public' => true,
                        'date_generation' => now(),
                    ]);

                    RegenererDocument::dispatch($document->id)->onQueue('default');
                    $countScheduled++;
                }
            }
        });

        $this->info("Jobs planifiés: {$countScheduled}");
        return self::SUCCESS;
    }
}
