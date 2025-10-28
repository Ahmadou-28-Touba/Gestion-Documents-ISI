<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentPublieMailable;
use App\Models\Document;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\ModeleDocument;
use App\Models\Etudiant;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class AdministrateurController extends Controller
{
    public function dashboard()
    {
        $administrateur = Auth::user()->administrateur;
        
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        $statistiques = $administrateur->statistiquesDocuments();
        
        return response()->json([
            'success' => true,
            'data' => [
                'administrateur' => $administrateur,
                'statistiques' => $statistiques,
                'documents_recents' => $administrateur->tousLesDocuments()->take(5),
                'modeles_actifs' => ModeleDocument::actifs()->get()
            ]
        ]);
    }

    /**
     * Gestion des classes (CRUD + affectations enseignants)
     */
    public function listeClasses(Request $request)
    {
        try {
            $q = Classe::query();
            if ($request->filled('filiere')) {
                $q->where('filiere', $request->input('filiere'));
            }
            if ($request->filled('annee')) {
                $q->where('annee', $request->input('annee'));
            }
            $classes = $q->with('enseignants.utilisateur')->orderBy('filiere')->orderBy('annee')->orderBy('groupe')->get();
            return response()->json(['success' => true, 'data' => $classes]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function creerClasse(Request $request)
    {
        try {
            $data = $request->validate([
                'filiere' => 'required|string|max:255',
                'annee' => 'required|string|max:50',
                'groupe' => 'nullable|string|max:50',
                'label' => 'nullable|string|max:255',
            ]);

            // Respecter l'unicité (filiere, annee, groupe)
            $classe = Classe::firstOrCreate([
                'filiere' => $data['filiere'],
                'annee' => $data['annee'],
                'groupe' => $data['groupe'] ?? null,
            ], [
                'label' => $data['label'] ?? null,
            ]);

            return response()->json(['success' => true, 'data' => $classe]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function mettreAJourClasse(Request $request, $id)
    {
        try {
            $classe = Classe::findOrFail($id);
            $data = $request->validate([
                'filiere' => 'sometimes|string|max:255',
                'annee' => 'sometimes|string|max:50',
                'groupe' => 'nullable|string|max:50',
                'label' => 'nullable|string|max:255',
            ]);

            // Tentative de mise à jour avec vérification d'unicité manuelle
            if (isset($data['filiere']) || isset($data['annee']) || array_key_exists('groupe', $data)) {
                $f = $data['filiere'] ?? $classe->filiere;
                $a = $data['annee'] ?? $classe->annee;
                $g = array_key_exists('groupe', $data) ? $data['groupe'] : $classe->groupe;
                $exists = Classe::where('filiere', $f)->where('annee', $a)->where('groupe', $g)->where('id', '!=', $classe->id)->exists();
                if ($exists) {
                    return response()->json(['success' => false, 'message' => 'Une classe identique existe déjà'], 422);
                }
            }

            $classe->update($data);
            return response()->json(['success' => true, 'data' => $classe->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function supprimerClasse($id)
    {
        try {
            $classe = Classe::findOrFail($id);
            $classe->delete();
            return response()->json(['success' => true, 'message' => 'Classe supprimée']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function attachEnseignantClasse(Request $request, $id)
    {
        try {
            $classe = Classe::findOrFail($id);
            $data = $request->validate([
                'enseignant_id' => 'required|integer|exists:enseignants,id',
            ]);
            $classe->enseignants()->syncWithoutDetaching([$data['enseignant_id']]);
            return response()->json(['success' => true, 'data' => $classe->load('enseignants.utilisateur')]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function detachEnseignantClasse($id, $enseignantId)
    {
        try {
            $classe = Classe::findOrFail($id);
            $classe->enseignants()->detach($enseignantId);
            return response()->json(['success' => true, 'message' => 'Enseignant détaché de la classe']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour la matière enseignée pour la relation Classe-Enseignant (pivot)
     */
    public function updateMatiereClasseEnseignant(Request $request, $id, $enseignantId)
    {
        try {
            $data = $request->validate([
                'matiere' => 'nullable|string|max:100',
            ]);
            $classe = Classe::findOrFail($id);
            // S'assurer que la relation existe
            if (!$classe->enseignants()->where('enseignant_id', $enseignantId)->exists()) {
                return response()->json(['success' => false, 'message' => 'Relation classe-enseignant introuvable'], 404);
            }
            $classe->enseignants()->updateExistingPivot($enseignantId, [
                'matiere' => $data['matiere'] ?? null,
            ]);

            // Synchroniser la matière vers la liste globale de l'enseignant si fournie
            if (($data['matiere'] ?? '') !== '') {
                $enseignant = Enseignant::findOrFail($enseignantId);
                $glob = is_array($enseignant->matieres_enseignees) ? $enseignant->matieres_enseignees : [];
                $glob = array_values(array_unique(array_filter(array_map(function($m){ return trim((string)$m); }, array_merge($glob, [$data['matiere']])))));
                $enseignant->matieres_enseignees = $glob;
                $enseignant->save();
            }

            return response()->json(['success' => true, 'data' => $classe->load('enseignants.utilisateur')]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupérer les matières enseignées par un enseignant
     */
    public function getMatieresEnseignant($id)
    {
        try {
            $enseignant = Enseignant::with('utilisateur')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'enseignant' => $enseignant,
                    'matieres' => $enseignant->matieres_enseignees ?? [],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour les matières enseignées par un enseignant
     */
    public function updateMatieresEnseignant(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'matieres' => 'required|array',
                'matieres.*' => 'string|max:100',
            ]);

            $enseignant = Enseignant::findOrFail($id);
            // Nettoyage: trim, filtre valeurs vides, unique, réindexation
            $matieres = array_values(array_unique(array_filter(array_map(function ($m) {
                return trim((string) $m);
            }, $data['matieres']))));

            $enseignant->matieres_enseignees = $matieres;
            $enseignant->save();

            return response()->json(['success' => true, 'data' => $enseignant->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Recherche d'enseignants par nom, prénom ou matricule (admin)
     */
    public function rechercherEnseignants(Request $request)
    {
        try {
            $q = trim((string) $request->input('q', ''));
            if ($q === '') {
                return response()->json(['success' => true, 'data' => []]);
            }
            $enseignants = Enseignant::with('utilisateur')
                ->whereHas('utilisateur', function($sub) use ($q) {
                    $sub->where('nom', 'like', "%$q%")
                        ->orWhere('prenom', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
                })
                ->orWhere('matricule', 'like', "%$q%")
                ->orderBy('matricule')
                ->limit(10)
                ->get();

            return response()->json(['success' => true, 'data' => $enseignants]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste complète des enseignants (admin)
     */
    public function listeEnseignants(Request $request)
    {
        try {
            $paginate = filter_var($request->input('paginate', false), FILTER_VALIDATE_BOOLEAN);
            $q = Enseignant::with('utilisateur')->orderBy('matricule');
            if ($paginate) {
                $perPage = (int) ($request->input('per_page', 20));
                return response()->json(['success' => true, 'data' => $q->paginate($perPage)]);
            }
            $list = $q->get();
            return response()->json(['success' => true, 'data' => $list]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Statistiques pour le tableau de bord Admin
     */
    public function statistiques()
    {
        $documentsParType = \App\Models\Document::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        $utilisateursParRole = \App\Models\Utilisateur::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get()
            ->pluck('count', 'role')
            ->toArray();

        $stats = [
            'documents' => [
                'total' => \App\Models\Document::count(),
                'par_type' => $documentsParType,
            ],
            'utilisateurs' => [
                'total' => \App\Models\Utilisateur::count(),
                'par_role' => $utilisateursParRole,
            ],
            'absences' => [
                'en_attente' => \App\Models\Absence::enAttente()->count(),
            ],
            'modeles' => [
                'actifs' => \App\Models\ModeleDocument::actifs()->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Phase 1 — Générer un aperçu PDF pour un type et (optionnellement) un étudiant.
     * Inputs:
     * - type: string (obligatoire)
     * - etudiant_id: int (optionnel)
     * - donnees: array (optionnel) si fourni, utilisé directement
     */
    public function genererPreviewDocuments(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'etudiant_id' => 'sometimes|integer|exists:etudiants,id',
            'donnees' => 'sometimes|array',
        ]);

        $type = $request->input('type');
        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->firstOrFail();
        $administrateur = Auth::user()->administrateur;
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        $donnees = $request->input('donnees', []);

        // Si un étudiant est fourni, compléter des données basiques
        if ($request->filled('etudiant_id')) {
            $etudiant = Etudiant::find($request->integer('etudiant_id'));
            if ($etudiant) {
                $donnees['etudiant'] = array_merge([
                    'nom' => $etudiant->nom ?? ($etudiant->utilisateur->nom ?? ''),
                    'prenom' => $etudiant->prenom ?? ($etudiant->utilisateur->prenom ?? ''),
                    'filiere' => $etudiant->filiere ?? '',
                    'annee' => $etudiant->annee ?? now()->year,
                ], $donnees['etudiant'] ?? []);
            }
        }

        if (!isset($donnees['date_du_jour'])) {
            $donnees['date_du_jour'] = now()->format('Y-m-d');
        }

        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(\App\Services\PdfRenderer::class);
        $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_'.$type.'.pdf"'
        ]);
    }

    /**
     * Phase 1 — Publier des documents pour une liste d'étudiants.
     * Inputs:
     * - type: string (obligatoire)
     * - etudiant_ids: array<int> (obligatoire)
     * - meta/donnees: optionnels (fusionnés par étudiant)
     */
    public function publierDocuments(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'etudiant_ids' => 'required|array',
            'etudiant_ids.*' => 'integer|exists:etudiants,id',
            'donnees' => 'sometimes|array',
        ]);

        $type = $request->input('type');
        $ids = $request->input('etudiant_ids', []);
        $extra = $request->input('donnees', []);

        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->firstOrFail();
        $administrateur = Auth::user()->administrateur;
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }
        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(\App\Services\PdfRenderer::class);

        $ok = 0; $errors = 0; $details = [];
        foreach ($ids as $eid) {
            try {
                $etudiant = Etudiant::findOrFail($eid);
                $donnees = $extra;
                $donnees['etudiant'] = array_merge([
                    'nom' => $etudiant->nom ?? ($etudiant->utilisateur->nom ?? ''),
                    'prenom' => $etudiant->prenom ?? ($etudiant->utilisateur->prenom ?? ''),
                    'filiere' => $etudiant->filiere ?? '',
                    'annee' => $etudiant->annee ?? now()->year,
                ], $donnees['etudiant'] ?? []);
                if (!isset($donnees['date_du_jour'])) {
                    $donnees['date_du_jour'] = now()->format('Y-m-d');
                }

                $pdf = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

                // Chemin de sortie public
                $nomBase = ($modele->nom ?: $type).'_'.$eid;
                $chemin = 'documents/'.$type.'/'.now()->year.'/'.\Illuminate\Support\Str::slug($nomBase).'_'.time().'.pdf';
                \Illuminate\Support\Facades\Storage::disk('public')->put($chemin, $pdf);

                // Enregistrement Document
                $doc = new Document();
                $doc->modele_document_id = $modele->id;
                $doc->etudiant_id = $eid;
                $doc->type = $type;
                $doc->nom = $nomBase;
                $doc->chemin_fichier = $chemin;
                $doc->donnees_document = $donnees;
                $doc->est_public = true;
                $doc->date_generation = now();
                $doc->administrateur_id = $administrateur->id;
                $doc->save();

                // Notification email
                if ($etudiant->utilisateur?->email) {
                    try {
                        Mail::to($etudiant->utilisateur->email)
                            ->send(new DocumentPublieMailable($etudiant, $doc));
                    } catch (\Throwable $mailEx) {
                        // Ne pas bloquer la publication en cas d'erreur d'envoi
                        $details[] = ['etudiant_id' => $eid, 'warning' => 'email_non_envoye', 'error' => $mailEx->getMessage()];
                    }
                }

                $ok++;
            } catch (\Throwable $e) {
                $errors++; $details[] = ['etudiant_id' => $eid, 'error' => $e->getMessage()];
            }
        }

        // TODO: notifications mail/in-app (phase suivante)

        return response()->json([
            'success' => true,
            'message' => 'Publication terminée',
            'data' => compact('ok','errors','details')
        ]);
    }

    /**
     * Endpoint de test: publier un document pour un seul étudiant (et envoyer l'email)
     * Payload JSON: { "type": "attestation_scolarite", "etudiant_id": 1, "donnees": { ... } }
     */
    public function publierDocumentTest(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'etudiant_id' => 'required|integer|exists:etudiants,id',
            'donnees' => 'sometimes|array',
        ]);

        $type = $request->input('type');
        $eid = (int) $request->input('etudiant_id');
        $extra = $request->input('donnees', []);

        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->firstOrFail();
        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(\App\Services\PdfRenderer::class);

        try {
            $etudiant = Etudiant::findOrFail($eid);
            $donnees = $extra;
            $donnees['etudiant'] = array_merge([
                'nom' => $etudiant->nom ?? ($etudiant->utilisateur->nom ?? ''),
                'prenom' => $etudiant->prenom ?? ($etudiant->utilisateur->prenom ?? ''),
                'filiere' => $etudiant->filiere ?? '',
                'annee' => $etudiant->annee ?? now()->year,
            ], $donnees['etudiant'] ?? []);
            if (!isset($donnees['date_du_jour'])) {
                $donnees['date_du_jour'] = now()->format('Y-m-d');
            }

            $pdf = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

            $nomBase = ($modele->nom ?: $type).'_'.$eid;
            $chemin = 'documents/'.$type.'/'.now()->year.'/'.\Illuminate\Support\Str::slug($nomBase).'_'.time().'.pdf';
            \Illuminate\Support\Facades\Storage::disk('public')->put($chemin, $pdf);

            $doc = new Document();
            $doc->modele_document_id = $modele->id;
            $doc->etudiant_id = $eid;
            $doc->type = $type;
            $doc->nom = $nomBase;
            $doc->chemin_fichier = $chemin;
            $doc->donnees_document = $donnees;
            $doc->est_public = true;
            $doc->date_generation = now();
            $doc->save();

            if ($etudiant->utilisateur?->email) {
                try {
                    Mail::to($etudiant->utilisateur->email)
                        ->send(new DocumentPublieMailable($etudiant, $doc));
                } catch (\Throwable $mailEx) {
                    // log silently
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Document publié et email envoyé (si possible)',
                'data' => $doc
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de publication: '.$e->getMessage()
            ], 500);
        }
    }

    // =================== Gestion simple des modèles par TYPE (admin minimal) ===================
    public function listeTypesModeles()
    {
        $types = [
            ['type' => 'attestation_scolarite', 'label' => 'Attestation de scolarité'],
            ['type' => 'attestation_reussite', 'label' => 'Attestation de réussite'],
            ['type' => 'bulletin_notes', 'label' => 'Bulletin de notes'],
            ['type' => 'emploi_temps', 'label' => 'Emploi du temps'],
            ['type' => 'certificat_scolarite', 'label' => 'Certificat de scolarité'],
            ['type' => 'document_administratif', 'label' => 'Document administratif'],
        ];
        $data = collect($types)->map(function ($t) {
            $modele = ModeleDocument::where('type_document', $t['type'])->where('est_actif', true)->first();
            return [
                'type' => $t['type'],
                'label' => $t['label'],
                'modele' => $modele ? [
                    'id' => $modele->id,
                    'nom' => $modele->nom,
                    'chemin_modele' => $modele->chemin_modele,
                    'extension' => pathinfo((string)$modele->chemin_modele, PATHINFO_EXTENSION),
                ] : null,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function showModeleParType($type)
    {
        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->first();
        if (!$modele) return response()->json(['success' => false, 'message' => 'Aucun modèle actif pour ce type'], 404);
        return response()->json(['success' => true, 'data' => $modele]);
    }

    public function downloadModeleParType($type)
    {
        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->first();
        if (!$modele) return response()->json(['success' => false, 'message' => 'Aucun modèle actif pour ce type'], 404);
        $path = $modele->chemin_modele;
        if (!$path || !@is_file($path)) return response()->json(['success' => false, 'message' => 'Fichier modèle introuvable'], 404);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'docx');
        $filename = Str::slug($type.'_modele').'.'.$ext;
        return response()->download($path, $filename);
    }

    public function uploadModeleParType(Request $request, $type)
    {
        $request->validate([
            'fichier_modele' => 'required|file|mimes:html,htm,odt,docx,txt|max:8192',
            'nom' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
        ]);
        $file = $request->file('fichier_modele');
        $relPath = 'templates/'.Str::slug($type).'/'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'_'.time().'.'.$file->getClientOriginalExtension();
        Storage::disk('local')->put($relPath, file_get_contents($file->getRealPath()));

        // Créer ou mettre à jour le modèle actif pour ce type
        $modele = ModeleDocument::firstOrNew(['type_document' => $type, 'est_actif' => true]);
        $modele->nom = $request->input('nom', ucfirst(str_replace('_',' ',$type)).' (modèle)');
        $modele->description = $request->input('description');
        $modele->chemin_modele = storage_path('app/'.$relPath);
        $modele->est_actif = true;
        $modele->save();
        // Désactiver les autres
        $modele->setAsDefault();

        return response()->json(['success' => true, 'message' => 'Modèle mis à jour', 'data' => $modele]);
    }

    public function newModeleDocxParType($type)
    {
        $labels = [
            'attestation_scolarite' => 'Attestation de scolarité',
            'attestation_reussite' => 'Attestation de réussite',
            'bulletin_notes' => 'Bulletin de notes',
            'emploi_temps' => 'Emploi du temps',
            'certificat_scolarite' => 'Certificat de scolarité',
            'document_administratif' => 'Document administratif',
        ];
        $title = $labels[$type] ?? ucfirst(str_replace('_',' ',$type));

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle($title.' (Modèle vierge)', 1);
        $section->addTextBreak(1);
        $section->addText('Variables disponibles (exemples):');
        $section->addListItem('{{ etudiant.nom }}');
        $section->addListItem('{{ etudiant.prenom }}');
        $section->addListItem('{{ etudiant.filiere }}');
        $section->addListItem('{{ etudiant.annee }}');
        $section->addListItem('{{ date_du_jour }}');
        $section->addTextBreak(1);
        $section->addText('Remplacez ce contenu par votre mise en page (logos, styles, textes, variables {{ ... }}).');

        $tmp = tempnam(sys_get_temp_dir(), 'modele_');
        @unlink($tmp);
        $tmpDocx = $tmp.'.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpDocx);

        $filename = \Illuminate\Support\Str::slug($type.'_modele_vierge').'.docx';
        return response()->download($tmpDocx, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Récupérer le contenu brut (HTML/texte) du modèle ACTIF pour un type donné
     */
    public function getModeleContentByType($type)
    {
        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->first();
        if (!$modele) {
            return response()->json(['success' => false, 'message' => 'Aucun modèle actif pour ce type'], 404);
        }
        $path = $modele->chemin_modele;
        if (!$path || !@is_file($path)) {
            return response()->json(['success' => false, 'message' => 'Fichier modèle introuvable'], 404);
        }
        $content = @file_get_contents($path);
        return response()->json(['success' => true, 'data' => [
            'modele_id' => $modele->id,
            'type_document' => $type,
            'content' => $content
        ]]);
    }

    /**
     * Mettre à jour le contenu brut (HTML/texte) du modèle ACTIF pour un type donné
     */
    public function updateModeleContentByType(Request $request, $type)
    {
        $request->validate([
            'content' => 'required|string'
        ]);
        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->first();
        if (!$modele) {
            return response()->json(['success' => false, 'message' => 'Aucun modèle actif pour ce type'], 404);
        }
        $path = $modele->chemin_modele;
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Chemin modèle manquant'], 400);
        }
        // Écrire le contenu directement dans le fichier actuel
        if (@file_put_contents($path, $request->input('content')) === false) {
            return response()->json(['success' => false, 'message' => 'Échec de l\'écriture du fichier modèle'], 500);
        }
        // S'assurer qu'il est actif
        $modele->setAsDefault();
        return response()->json(['success' => true, 'message' => 'Modèle mis à jour', 'data' => $modele]);
    }

    /**
     * Prévisualiser un modèle par type.
     * Si 'content' est fourni, on rend depuis ce contenu, sinon depuis le fichier du modèle actif.
     * Les données de test peuvent être passées dans 'donnees' (JSON).
     */
    public function previewModeleType(Request $request, $type)
    {
        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(PdfRenderer::class);

        $donnees = $request->input('donnees', []);
        if (!isset($donnees['date_du_jour'])) {
            $donnees['date_du_jour'] = now()->format('Y-m-d');
        }

        $content = $request->input('content');
        if (is_string($content) && $content !== '') {
            $pdfBytes = $pdfRenderer->renderFromTemplateContent($content, $donnees);
        } else {
            $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->firstOrFail();
            $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);
        }

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_'.$type.'.pdf"'
        ]);
    }

    /**
     * Régénérer les documents existants d'un type à partir du modèle actif.
     * Paramètres optionnels: limit (par défaut 50), date_debut, date_fin.
     */
    public function regenererDocumentsParType(Request $request, $type)
    {
        $limit = (int) $request->input('limit', 50);
        $limit = max(1, min($limit, 500));

        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->first();
        if (!$modele) {
            return response()->json(['success' => false, 'message' => 'Aucun modèle actif pour ce type'], 404);
        }

        $q = Document::where('type', $type)->orderByDesc('date_generation');
        if ($request->filled('date_debut')) {
            $q->where('date_generation', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $q->where('date_generation', '<=', $request->input('date_fin'));
        }
        $docs = $q->limit($limit)->get();

        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(PdfRenderer::class);

        $ok = 0; $errors = 0; $details = [];
        foreach ($docs as $doc) {
            try {
                $donnees = $doc->donnees_document ?? [];
                if (!isset($donnees['date_du_jour'])) {
                    $donnees['date_du_jour'] = now()->format('Y-m-d');
                }
                $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);
                // Ecrire sur un nouveau chemin et mettre à jour
                $nomBase = $doc->nom ?: ($modele->nom.'_'.$doc->id);
                $chemin = 'documents/'.$type.'/'.now()->year.'/'.now()->month.'/'.\Illuminate\Support\Str::slug($nomBase).'_'.time().'.pdf';
                \Illuminate\Support\Facades\Storage::disk('public')->put($chemin, $pdfBytes);
                $doc->chemin_fichier = $chemin;
                $doc->date_generation = now();
                $doc->save();
                $ok++;
            } catch (\Throwable $e) {
                $errors++; $details[] = ['id' => $doc->id, 'error' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'message' => 'Régénération terminée', 'data' => compact('ok','errors','details')]);
    }

    /**
     * Lister les documents récents, filtrables par type, limit par défaut 10
     * Query params: type (optionnel), limit (1..100)
     */
    public function listeDocumentsRecents(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $limit = max(1, min($limit, 100));
        $type = $request->input('type');

        $q = Document::orderByDesc('date_generation');
        if ($type) {
            $q->where('type', $type);
        }
        $docs = $q->limit($limit)->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'type' => $d->type,
                'nom' => $d->nom,
                'etudiant_id' => $d->etudiant_id,
                'date_generation' => $d->date_generation,
                'chemin_fichier' => $d->chemin_fichier,
                'url_public' => $d->chemin_fichier ? \Illuminate\Support\Facades\Storage::disk('public')->url($d->chemin_fichier) : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $docs]);
    }

    /**
     * Télécharger un document par ID (force le téléchargement)
     */
    public function downloadDocumentById($id)
    {
        $doc = Document::find($id);
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document introuvable'], 404);
        }
        $rel = $doc->chemin_fichier;
        if (!$rel || !\Illuminate\Support\Facades\Storage::disk('public')->exists($rel)) {
            return response()->json(['success' => false, 'message' => 'Fichier manquant'], 404);
        }
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($rel);
        $filename = \Illuminate\Support\Str::slug(($doc->nom ?: 'document').'_'.$doc->id).'.pdf';
        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
        ]);
    }

    /**
     * Importer un CSV d'Emploi du Temps et générer un document par ligne (par étudiant).
     * Colonnes attendues (CSV en UTF-8, avec en-tête):
     * - numero_etudiant, filiere, annee, semaine, jour, heure_debut, heure_fin, matiere, salle, enseignant, groupe
     */
    public function uploadEmploiTempsCsv(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimetypes:text/plain,text/csv,text/tsv,text/plain,application/csv,application/vnd.ms-excel',
        ]);

        $administrateur = Auth::user()->administrateur;
        if (!$administrateur) {
            return response()->json(['success' => false, 'message' => 'Profil administrateur non trouvé'], 404);
        }

        $modele = ModeleDocument::where('type_document', 'emploi_temps')->where('est_actif', true)->first();
        if (!$modele) return response()->json(['success' => false, 'message' => 'Modèle emploi_temps introuvable ou inactif'], 422);

        $path = $request->file('fichier')->getRealPath();
        $fh = new \SplFileObject($path);
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $fh->setCsvControl(',');

        $headers = null;
        $ok = 0; $errors = 0; $details = [];
        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(PdfRenderer::class);

        while (!$fh->eof()) {
            $row = $fh->fgetcsv();
            if ($row === [null] || $row === false) continue;
            if ($headers === null) { $headers = array_map('trim', $row); continue; }
            $data = [];
            foreach ($headers as $i => $h) { $data[$h] = $row[$i] ?? null; }

            try {
                $etu = Etudiant::where('numero_etudiant', $data['numero_etudiant'] ?? '')->first();
                if (!$etu) { throw new \RuntimeException('Étudiant introuvable: '.$data['numero_etudiant']); }

                $donnees = [
                    'etudiant' => [
                        'id' => $etu->id,
                        'numero_etudiant' => $etu->numero_etudiant,
                        'nom' => $etu->utilisateur->nom ?? '',
                        'prenom' => $etu->utilisateur->prenom ?? '',
                        'filiere' => $etu->filiere,
                        'annee' => $etu->annee,
                    ],
                    'emploi' => [
                        'semaine' => $data['semaine'] ?? '',
                        'jour' => $data['jour'] ?? '',
                        'heure_debut' => $data['heure_debut'] ?? '',
                        'heure_fin' => $data['heure_fin'] ?? '',
                        'matiere' => $data['matiere'] ?? '',
                        'salle' => $data['salle'] ?? '',
                        'enseignant' => $data['enseignant'] ?? '',
                        'groupe' => $data['groupe'] ?? '',
                    ],
                    'date_du_jour' => now()->format('Y-m-d'),
                ];

                $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);
                $nomBase = ($modele->nom ?? 'emploi_temps') . '_' . ($etu->numero_etudiant ?? $etu->id);
                $chemin = 'documents/emploi_temps/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . time() . '.pdf';
                Storage::disk('public')->put($chemin, $pdfBytes);

                Document::create([
                    'modele_document_id' => $modele->id,
                    'etudiant_id' => $etu->id,
                    'administrateur_id' => $administrateur->id,
                    'type' => 'emploi_temps',
                    'nom' => $modele->nom . ' - ' . ($etu->utilisateur->nom ?? ''),
                    'chemin_fichier' => $chemin,
                    'donnees_document' => $donnees,
                    'est_public' => true,
                    'date_generation' => now(),
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $errors++; $details[] = ['row' => $data, 'error' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'message' => 'Import emploi du temps terminé', 'data' => compact('ok','errors','details')]);
    }

    /**
     * Importer un CSV de Bulletins et générer un document par étudiant.
     * Colonnes attendues: numero_etudiant, filiere, annee, semestre, moyenne, appreciation
     */
    public function uploadBulletinsCsv(Request $request)
    {
        $request->validate([
            'fichier' => 'required|file|mimetypes:text/plain,text/csv,text/tsv,text/plain,application/csv,application/vnd.ms-excel',
        ]);

        $administrateur = Auth::user()->administrateur;
        if (!$administrateur) {
            return response()->json(['success' => false, 'message' => 'Profil administrateur non trouvé'], 404);
        }

        $modele = ModeleDocument::where('type_document', 'bulletin_notes')->where('est_actif', true)->first();
        if (!$modele) return response()->json(['success' => false, 'message' => 'Modèle bulletin_notes introuvable ou inactif'], 422);

        $path = $request->file('fichier')->getRealPath();
        $fh = new \SplFileObject($path);
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $fh->setCsvControl(',');

        $headers = null; $ok = 0; $errors = 0; $details = [];
        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(PdfRenderer::class);

        while (!$fh->eof()) {
            $row = $fh->fgetcsv();
            if ($row === [null] || $row === false) continue;
            if ($headers === null) { $headers = array_map('trim', $row); continue; }
            $data = [];
            foreach ($headers as $i => $h) { $data[$h] = $row[$i] ?? null; }

            try {
                $etu = Etudiant::where('numero_etudiant', $data['numero_etudiant'] ?? '')->first();
                if (!$etu) { throw new \RuntimeException('Étudiant introuvable: '.$data['numero_etudiant']); }

                $donnees = [
                    'etudiant' => [
                        'id' => $etu->id,
                        'numero_etudiant' => $etu->numero_etudiant,
                        'nom' => $etu->utilisateur->nom ?? '',
                        'prenom' => $etu->utilisateur->prenom ?? '',
                        'filiere' => $etu->filiere,
                        'annee' => $etu->annee,
                    ],
                    'bulletin' => [
                        'semestre' => $data['semestre'] ?? '',
                        'moyenne' => $data['moyenne'] ?? '',
                        'appreciation' => $data['appreciation'] ?? '',
                    ],
                    'date_du_jour' => now()->format('Y-m-d'),
                ];

                $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);
                $nomBase = ($modele->nom ?? 'bulletin') . '_' . ($etu->numero_etudiant ?? $etu->id);
                $chemin = 'documents/bulletin_notes/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . time() . '.pdf';
                Storage::disk('public')->put($chemin, $pdfBytes);

                Document::create([
                    'modele_document_id' => $modele->id,
                    'etudiant_id' => $etu->id,
                    'administrateur_id' => $administrateur->id,
                    'type' => 'bulletin_notes',
                    'nom' => $modele->nom . ' - ' . ($etu->utilisateur->nom ?? ''),
                    'chemin_fichier' => $chemin,
                    'donnees_document' => $donnees,
                    'est_public' => true,
                    'date_generation' => now(),
                ]);
                $ok++;
            } catch (\Throwable $e) {
                $errors++; $details[] = ['row' => $data, 'error' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'message' => 'Import bulletins terminé', 'data' => compact('ok','errors','details')]);
    }

    /**
     * Upload d'un modèle (HTML/ODT), enregistrement et activation optionnelle
     */
    public function uploadModele(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type_document' => 'required|string|in:attestation_scolarite,attestation_reussite,bulletin_notes,emploi_temps,certificat_scolarite,document_administratif',
            'description' => 'sometimes|string|max:1000',
            'champs_requis' => 'sometimes|array',
            'activer' => 'sometimes|boolean',
            'fichier_modele' => 'required|file|mimes:html,htm,odt,docx,txt|max:5120'
        ]);

        $file = $request->file('fichier_modele');
        $type = $request->input('type_document');

        // Stockage centralisé sous storage/app/templates/{type}/...
        $path = 'templates/' . Str::slug($type) . '/' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $file->getClientOriginalExtension();
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $modele = ModeleDocument::create([
            'nom' => $request->nom,
            'type_document' => $type,
            // On enregistre le chemin absolu pour compatibilité avec PdfRenderer/resolvePath
            'chemin_modele' => storage_path('app/' . $path),
            'champs_requis' => $request->input('champs_requis', []),
            'description' => $request->input('description'),
            'est_actif' => false
        ]);

        // Si demandé, activer comme modèle par défaut pour ce type (désactive les autres)
        if ($request->boolean('activer', true)) {
            $modele->setAsDefault();
        }

        \Log::info('Upload modèle document', [
            'admin_id' => Auth::id(),
            'modele_id' => $modele->id,
            'type_document' => $modele->type_document
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Modèle importé avec succès',
            'data' => $modele
        ], 201);
    }

    /**
     * Générer des documents en lot (par promotion, filière/groupe ou liste fournie)
     */
    public function genererBatchDocuments(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'cible' => 'required|in:promotion,groupe,liste',
            'promotion' => 'sometimes',
            'filiere' => 'sometimes|string',
            'utilisateur_ids' => 'sometimes|array',
            'utilisateur_ids.*' => 'integer|exists:utilisateurs,id',
            'etudiant_ids' => 'sometimes|array',
            'etudiant_ids.*' => 'integer|exists:etudiants,id',
            'donnees_communes' => 'sometimes|array',
            'publication' => 'sometimes|boolean',
        ]);

        $administrateur = Auth::user()->administrateur;
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        $publication = $request->boolean('publication', true);
        $type = $request->type;

        // Résoudre la cible -> collection d'étudiants
        $etudiantsQuery = Etudiant::with('utilisateur');
        if ($request->cible === 'promotion' && !is_null($request->promotion)) {
            $etudiantsQuery->where('annee', $request->promotion);
        } elseif ($request->cible === 'groupe' && $request->filled('filiere')) {
            $etudiantsQuery->where('filiere', $request->filiere);
            if (!is_null($request->promotion)) {
                $etudiantsQuery->where('annee', $request->promotion);
            }
        } elseif ($request->cible === 'liste') {
            if ($request->filled('etudiant_ids')) {
                $etudiantsQuery->whereIn('id', $request->etudiant_ids);
            } elseif ($request->filled('utilisateur_ids')) {
                $etudiantsQuery->whereIn('utilisateur_id', $request->utilisateur_ids);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Liste vide: fournir etudiant_ids ou utilisateur_ids'
                ], 422);
            }
        }

        $etudiants = $etudiantsQuery->get();

        if ($etudiants->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun étudiant trouvé pour la cible demandée'
            ], 404);
        }

        // Récupération du modèle actif
        $modele = ModeleDocument::where('type_document', $type)
            ->where('est_actif', true)
            ->first();
        if (!$modele) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun modèle actif trouvé pour ce type de document'
            ], 422);
        }

        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(PdfRenderer::class);

        $resultats = [
            'total' => $etudiants->count(),
            'success' => 0,
            'errors' => 0,
            'details' => []
        ];

        foreach ($etudiants as $etudiant) {
            try {
                // Construire données spécifiques étudiant
                $donnees = array_merge($request->get('donnees_communes', []), [
                    'etudiant' => [
                        'id' => $etudiant->id,
                        'numero_etudiant' => $etudiant->numero_etudiant,
                        'nom' => $etudiant->utilisateur->nom ?? '',
                        'prenom' => $etudiant->utilisateur->prenom ?? '',
                        'filiere' => $etudiant->filiere,
                        'annee' => $etudiant->annee,
                    ],
                    'date_du_jour' => now()->format('Y-m-d')
                ]);

                // Générer PDF (toujours), publier si demandé
                $nomBase = ($modele->nom ?? ucfirst($type)) . '_' . ($etudiant->numero_etudiant ?? $etudiant->id);
                $chemin = 'documents/' . $type . '/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . time() . '.pdf';

                $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

                if ($publication) {
                    Storage::disk('public')->put($chemin, $pdfBytes);

                    $document = Document::create([
                        'modele_document_id' => $modele->id,
                        'etudiant_id' => $etudiant->id,
                        'administrateur_id' => $administrateur->id,
                        'type' => $type,
                        'nom' => $modele->nom . ' - ' . ($etudiant->utilisateur->nom ?? ''),
                        'chemin_fichier' => $chemin,
                        'donnees_document' => $donnees,
                        'est_public' => true,
                        'date_generation' => now()
                    ]);

                    // Notification
                    try {
                        NotificationController::notifierDocumentGenere($document->load(['etudiant.utilisateur']));
                    } catch (\Throwable $ex) {
                        // ne bloque pas la génération
                    }

                    $resultats['success']++;
                    $resultats['details'][] = [
                        'etudiant_id' => $etudiant->id,
                        'status' => 'published',
                        'document_id' => $document->id,
                    ];
                } else {
                    // Prévisualisation: retourner un lien data URL tronqué
                    $resultats['success']++;
                    $resultats['details'][] = [
                        'etudiant_id' => $etudiant->id,
                        'status' => 'preview',
                        'preview_bytes' => base64_encode(substr($pdfBytes, 0, 0))
                    ];
                }
            } catch (\Throwable $e) {
                $resultats['errors']++;
                $resultats['details'][] = [
                    'etudiant_id' => $etudiant->id,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        // Journalisation simple
        \Log::info('Publication batch documents', [
            'admin_id' => $administrateur->id,
            'type' => $type,
            'publication' => $publication,
            'cible' => $request->cible,
            'promotion' => $request->promotion,
            'filiere' => $request->filiere,
            'resultats' => [
                'total' => $resultats['total'],
                'success' => $resultats['success'],
                'errors' => $resultats['errors']
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => $publication ? 'Documents publiés' : 'Prévisualisation générée',
            'data' => $resultats
        ]);
    }

    public function profil()
    {
        $administrateur = Auth::user()->administrateur;
        
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'utilisateur' => Auth::user(),
                'administrateur' => $administrateur
            ]
        ]);
    }

    public function modifierProfil(Request $request)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:utilisateurs,email,' . Auth::id(),
            'departement' => 'sometimes|string|max:255',
            'date_embauche' => 'sometimes|date'
        ]);

        $utilisateur = Auth::user();
        $administrateur = $utilisateur->administrateur;

        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        // Mettre à jour les données utilisateur
        $utilisateur->update($request->only(['nom', 'prenom', 'email']));

        // Mettre à jour les données administrateur
        $administrateur->update($request->only(['departement', 'date_embauche']));

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'utilisateur' => $utilisateur->fresh(),
                'administrateur' => $administrateur->fresh()
            ]
        ]);
    }

    // Gestion des documents
    public function index(Request $request)
    {
        $administrateur = Auth::user()->administrateur;
        
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        $type = $request->get('type');
        
        if ($type) {
            $documents = $administrateur->documentsParType($type);
        } else {
            $documents = $administrateur->tousLesDocuments();
        }

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function show($id)
    {
        $document = Document::with(['etudiant.utilisateur', 'modeleDocument'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }

    public function genererDocument(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'etudiant_id' => 'required|exists:etudiants,id',
            'donnees' => 'sometimes|array'
        ]);

        $administrateur = Auth::user()->administrateur;
        
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        try {
            // 1) Créer l'enregistrement Document (métadonnées)
            $document = $administrateur->genererDocument(
                $request->type,
                $request->etudiant_id,
                $request->donnees ?? []
            );

            // 2) Récupérer le modèle actif et générer le PDF
            $modele = ModeleDocument::where('type_document', $request->type)
                ->where('est_actif', true)
                ->first();

            if (!$modele) {
                throw new \Exception('Aucun modèle actif trouvé pour ce type de document');
            }

            /** @var PdfRenderer $pdfRenderer */
            $pdfRenderer = app(PdfRenderer::class);
            // Préparer les données: auto-remplir bloc etudiant si absent
            $etudiant = Etudiant::with('utilisateur')->findOrFail($request->etudiant_id);
            $donnees = $request->donnees ?? [];
            if (!isset($donnees['etudiant'])) {
                $donnees['etudiant'] = [
                    'id' => $etudiant->id,
                    'numero_etudiant' => $etudiant->numero_etudiant,
                    'nom' => $etudiant->utilisateur->nom ?? '',
                    'prenom' => $etudiant->utilisateur->prenom ?? '',
                    'filiere' => $etudiant->filiere,
                    'annee' => $etudiant->annee,
                ];
            }
            if (!isset($donnees['date_du_jour'])) {
                $donnees['date_du_jour'] = now()->format('Y-m-d');
            }

            // Valider les champs requis du modèle
            $requis = $modele->champs_requis ?? [];
            $manquants = [];
            foreach ($requis as $champ) {
                // support dot-notation
                $value = data_get($donnees, $champ);
                if ($value === null || $value === '') {
                    $manquants[] = $champ;
                }
            }
            if (!empty($manquants)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Champs requis manquants',
                    'manquants' => $manquants,
                ], 422);
            }

            $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

            // 3) Déterminer le chemin et enregistrer le PDF dans le storage public
            $nomBase = $document->nom ?: (ucfirst($request->type) . '_' . $request->utilisateur_id);
            $chemin = 'documents/' . $request->type . '/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . time() . '.pdf';

            Storage::disk('public')->put($chemin, $pdfBytes);

            // 4) Mettre à jour le document avec le chemin du fichier généré
            $document->chemin_fichier = $chemin;
            $document->save();

            return response()->json([
                'success' => true,
                'message' => 'Document généré avec succès',
                'data' => $document->load(['etudiant.utilisateur', 'modeleDocument'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exposer le schéma d'un modèle (placeholders et champs requis)
     */
    

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'est_public' => 'sometimes|boolean'
        ]);

        $document = Document::findOrFail($id);
        $document->update($request->only(['nom', 'est_public']));

        return response()->json([
            'success' => true,
            'message' => 'Document mis à jour avec succès',
            'data' => $document
        ]);
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ]);
    }

    public function archiverDocuments(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id'
        ]);

        $administrateur = Auth::user()->administrateur;
        
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        $administrateur->archiverDocuments($request->document_ids);

        return response()->json([
            'success' => true,
            'message' => 'Documents archivés avec succès'
        ]);
    }

    // Gestion des modèles de documents
    public function indexModeles()
    {
        $modeles = ModeleDocument::withCount('documents')->get();

        return response()->json([
            'success' => true,
            'data' => $modeles
        ]);
    }

    

    public function supprimerModele($id)
    {
        $modele = ModeleDocument::findOrFail($id);
        $modele->delete();

        return response()->json([
            'success' => true,
            'message' => 'Modèle supprimé avec succès'
        ]);
    }

    

    // Méthodes supplémentaires pour la gestion des modèles
    public function showModele($id)
    {
        $modele = ModeleDocument::withCount('documents')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $modele
        ]);
    }

    public function activerModele($id)
    {
        $modele = ModeleDocument::findOrFail($id);
        // Activer comme défaut pour son type et désactiver les autres
        $modele->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Modèle défini comme défaut pour le type ' . $modele->type_document,
            'data' => $modele
        ]);
    }

    

    

    

    /**
     * Installer les modèles par défaut à partir des templates présents dans resources/templates
     */
    public function seedModelesDefaut()
    {
        $defs = [
            ['type' => 'attestation_scolarite', 'nom' => 'Attestation de scolarité', 'path' => 'resources/templates/attestation_scolarite.html'],
            ['type' => 'attestation_reussite', 'nom' => 'Attestation de réussite', 'path' => 'resources/templates/attestation_reussite.html'],
            ['type' => 'certificat_scolarite', 'nom' => 'Certificat de scolarité', 'path' => 'resources/templates/certificat_scolarite.html'],
            ['type' => 'bulletin_notes', 'nom' => 'Bulletin de notes', 'path' => 'resources/templates/bulletin_notes.html'],
            ['type' => 'emploi_temps', 'nom' => 'Emploi du temps', 'path' => 'resources/templates/emploi_temps.html'],
            // Utiliser le type autorisé par la contrainte pour les documents divers (ex: convocation)
            ['type' => 'document_administratif', 'nom' => 'Convocation', 'path' => 'resources/templates/convocation.html'],
        ];

        $created = [];
        foreach ($defs as $def) {
            if (!ModeleDocument::where('type_document', $def['type'])->exists()) {
                $full = base_path($def['path']);
                if (!file_exists($full)) {
                    continue;
                }
                $m = new ModeleDocument();
                $m->nom = $def['nom'];
                $m->type_document = $def['type'];
                $m->chemin_modele = $full;
                $m->champs_requis = [];
                $m->description = 'Modèle par défaut';
                $m->est_actif = true;
                $m->save();
                $created[] = $m;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Modèles par défaut installés',
            'data' => [
                'crees' => collect($created)->map(fn($m) => ['id' => $m->id, 'type' => $m->type_document])
            ]
        ]);
    }

    public function previewModele(Request $request, $id)
    {
        $modele = ModeleDocument::findOrFail($id);

        /** @var PdfRenderer $pdfRenderer */
        $pdfRenderer = app(PdfRenderer::class);

        // Données passées dans la requête (GET/POST) pour remplir le modèle
        $donnees = $request->all();

        $pdfBytes = $pdfRenderer->renderFromTemplatePath($modele->chemin_modele, $donnees);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_modele_'.$modele->id.'.pdf"'
        ]);
    }

    /**
     * Télécharger le fichier brut du modèle (HTML/DOCX/ODT)
     */
    public function downloadModeleFichier($id)
    {
        $modele = ModeleDocument::findOrFail($id);
        $path = $modele->chemin_modele;
        if (!$path || !@is_file($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier modèle introuvable'
            ], 404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $filename = \Illuminate\Support\Str::slug($modele->nom ?: ('modele_'.$modele->id)) . '.' . ($ext ?: 'tpl');
        return response()->download($path, $filename);
    }

    /**
     * Remplacer le fichier du modèle par un nouvel upload
     */
    public function remplacerFichierModele(Request $request, $id)
    {
        $request->validate([
            'fichier_modele' => 'required|file|mimes:html,htm,odt,docx,txt|max:5120',
            'activer' => 'sometimes|boolean'
        ]);

        $modele = ModeleDocument::findOrFail($id);

        $file = $request->file('fichier_modele');
        $type = $modele->type_document ?: 'document_administratif';

        $relPath = 'templates/' . \Illuminate\Support\Str::slug($type) . '/' . \Illuminate\Support\Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $file->getClientOriginalExtension();
        \Illuminate\Support\Facades\Storage::disk('local')->put($relPath, file_get_contents($file->getRealPath()));

        $modele->chemin_modele = storage_path('app/' . $relPath);
        $modele->save();

        // Rendre effectif immédiatement: définir ce modèle comme défaut pour son type
        $modele->setAsDefault();

        return response()->json([
            'success' => true,
            'message' => 'Fichier du modèle remplacé avec succès',
            'data' => $modele
        ]);
    }

    

    // Méthodes pour la gestion des documents
    public function rechercherDocuments(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type');
        $utilisateur = $request->get('utilisateur_id');

        $documents = Document::with(['etudiant.utilisateur', 'modeleDocument'])
            ->when($query, function ($q) use ($query) {
                return $q->where('nom', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%");
            })
            ->when($type, function ($q) use ($type) {
                return $q->where('type', $type);
            })
            ->when($utilisateur, function ($q) use ($utilisateur) {
                return $q->where('etudiant_id', $utilisateur);
            })
            ->orderBy('date_generation', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function statistiquesGenerales()
    {
        $administrateur = Auth::user()->administrateur;
        
        if (!$administrateur) {
            return response()->json([
                'success' => false,
                'message' => 'Profil administrateur non trouvé'
            ], 404);
        }

        $statistiques = [
            'documents' => $administrateur->statistiquesDocuments(),
            'utilisateurs' => [
                'total' => Utilisateur::count(),
                'par_role' => Utilisateur::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->get()
                    ->pluck('count', 'role')
                    ->toArray()
            ],
            'absences' => [
                'total' => Absence::count(),
                'en_attente' => Absence::enAttente()->count(),
                'validees' => Absence::validees()->count(),
                'refusees' => Absence::refusees()->count()
            ],
            'modeles' => [
                'total' => ModeleDocument::count(),
                'actifs' => ModeleDocument::actifs()->count(),
                'inactifs' => ModeleDocument::where('est_actif', false)->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $statistiques
        ]);
    }
}