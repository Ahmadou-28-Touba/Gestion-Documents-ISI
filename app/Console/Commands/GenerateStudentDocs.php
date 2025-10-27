<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Etudiant;
use App\Models\ModeleDocument;
use App\Models\Administrateur;
use App\Services\PdfRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateStudentDocs extends Command
{
    protected $signature = 'documents:generate-student {--types=attestation_scolarite,certificat_scolarite} {--etudiant-id=} {--all}';

    protected $description = 'Générer immédiatement des documents HTML (PDF) pour un étudiant ou pour tous les étudiants';

    public function handle(PdfRenderer $renderer): int
    {
        $types = collect(explode(',', (string)$this->option('types')))
            ->map(fn($t) => trim($t))
            ->filter()
            ->values();

        if ($types->isEmpty()) {
            $this->error('Aucun type fourni.');
            return self::FAILURE;
        }

        $modeles = ModeleDocument::query()
            ->whereIn('type_document', $types)
            ->where('est_actif', true)
            ->get()
            ->keyBy('type_document');

        if ($modeles->isEmpty()) {
            $this->error('Aucun modèle actif trouvé.');
            return self::FAILURE;
        }

        $adminId = Administrateur::query()->value('id');
        if (!$adminId) {
            $this->error('Aucun administrateur trouvé.');
            return self::FAILURE;
        }

        $query = Etudiant::query()->with('utilisateur');
        if ($id = $this->option('etudiant-id')) {
            $query->where('id', $id);
        } elseif (!$this->option('all')) {
            $this->error('Spécifiez --etudiant-id=<id> ou --all');
            return self::FAILURE;
        }

        $generated = 0;
        $query->chunkById(100, function ($etudiants) use ($modeles, $types, $renderer, $adminId, &$generated) {
            foreach ($etudiants as $etudiant) {
                foreach ($types as $type) {
                    $modele = $modeles->get($type);
                    if (!$modele) { continue; }

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

                    $nomBase = ($modele->nom ?? ucfirst($type)) . '_' . ($etudiant->numero_etudiant ?? $etudiant->id);
                    $chemin = 'documents/' . $type . '/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . Str::uuid() . '.pdf';

                    // Rendu immédiat (HTML)
                    $pdfBytes = $renderer->renderFromTemplatePath($modele->chemin_modele, $donnees);
                    Storage::disk('public')->put($chemin, $pdfBytes);

                    Document::create([
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

                    $generated++;
                }
            }
        });

        $this->info("Documents générés: {$generated}");
        return self::SUCCESS;
    }
}
