<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\ModeleDocument;
use App\Services\PdfRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegenererDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $documentId
    ) {}

    public function handle(PdfRenderer $renderer): void
    {
        $debut = microtime(true);

        $document = Document::with('modeleDocument', 'etudiant')->find($this->documentId);
        if (!$document || !$document->modeleDocument) {
            return;
        }

        $modele = $document->modeleDocument;
        $donnees = $document->donnees_document ?? [];

        // Générer un chemin si absent
        $nomBase = $document->nom ?: ($modele->nom . '_' . ($document->etudiant->numero_etudiant ?? $document->etudiant_id));
        $chemin = $document->chemin_fichier ?: ('documents/' . $document->type . '/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . Str::uuid() . '.pdf');

        // Rendre le PDF
        $pdfBytes = $renderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

        // Écrire et mettre à jour
        Storage::disk('public')->put($chemin, $pdfBytes);
        $document->chemin_fichier = $chemin;
        $document->date_generation = now();
        $document->save();

        // Optionnel: enregistrer la durée
        // $dureeMs = (int) ((microtime(true) - $debut) * 1000);
        // $document->duree_ms = $dureeMs; (si colonne ajoutée plus tard)
    }
}
