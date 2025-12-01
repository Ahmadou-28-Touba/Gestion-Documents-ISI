<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ModeleDocument;
use App\Models\Etudiant;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Afficher la liste des documents
     */
    public function index(Request $request)
    {
        $query = Document::with(['etudiant.utilisateur', 'modeleDocument']);

        // Filtres
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('est_public')) {
            $query->where('est_public', $request->boolean('est_public'));
        }

        if ($request->has('etudiant_id')) {
            $query->where('etudiant_id', $request->etudiant_id);
        }

        if ($request->has('date_debut')) {
            $query->where('date_generation', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_generation', '<=', $request->date_fin);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'date_generation');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $documents = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    /**
     * Afficher un document spécifique
     */
    public function show($id)
    {
        $document = Document::with(['etudiant.utilisateur', 'modeleDocument'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }

    /**
     * Mettre à jour un document
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'donnees' => 'sometimes|array',
            'est_public' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $document = Document::findOrFail($id);

        // Vérifier les permissions
        if ($document->etudiant_id !== Auth::user()->etudiant->id && !Auth::user()->isAdministrateur()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour modifier ce document'
            ], 403);
        }

        $document->update($request->only(['nom', 'donnees', 'est_public']));

        return response()->json([
            'success' => true,
            'message' => 'Document mis à jour avec succès',
            'data' => $document->load(['etudiant.utilisateur', 'modeleDocument'])
        ]);
    }

    /**
     * Supprimer un document
     */
    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        // Vérifier les permissions
        if ($document->etudiant_id !== Auth::user()->etudiant->id && !Auth::user()->isAdministrateur()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour supprimer ce document'
            ], 403);
        }

        // Supprimer le fichier physique s'il existe
        if ($document->chemin_fichier && Storage::exists($document->chemin_fichier)) {
            Storage::delete($document->chemin_fichier);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ]);
    }

    /**
     * Télécharger un document
     */
    public function telecharger($id)
    {
        $document = Document::findOrFail($id);

        // Vérifier les permissions
        if (!$document->est_public && $document->etudiant_id !== Auth::user()->etudiant->id && !Auth::user()->isAdministrateur()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour télécharger ce document'
            ], 403);
        }

        if (!$document->estTelechargeable()) {
            return response()->json([
                'success' => false,
                'message' => 'Le fichier n\'existe pas sur le serveur'
            ], 404);
        }

        return Storage::download($document->chemin_fichier, $document->nom . '.pdf');
    }

    /**
     * Archiver un document
     */
    public function archiver($id)
    {
        $document = Document::findOrFail($id);

        // Vérifier les permissions
        if ($document->etudiant_id !== Auth::user()->etudiant->id && !Auth::user()->isAdministrateur()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour archiver ce document'
            ], 403);
        }

        $document->archiver();

        return response()->json([
            'success' => true,
            'message' => 'Document archivé avec succès',
            'data' => $document
        ]);
    }

    /**
     * Désarchiver un document
     */
    public function desarchiver($id)
    {
        $document = Document::findOrFail($id);

        // Vérifier les permissions
        if ($document->etudiant_id !== Auth::user()->etudiant->id && !Auth::user()->isAdministrateur()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour désarchiver ce document'
            ], 403);
        }

        $document->desarchiver();

        return response()->json([
            'success' => true,
            'message' => 'Document désarchivé avec succès',
            'data' => $document
        ]);
    }

    /**
     * Dupliquer un document
     */
    public function dupliquer(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Vérifier les permissions
        if ($document->etudiant_id !== Auth::user()->etudiant->id && !Auth::user()->isAdministrateur()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour dupliquer ce document'
            ], 403);
        }

        $nouveauNom = $request->get('nom', $document->nom . '_copie');
        $nouveauDocument = $document->dupliquer($nouveauNom);

        return response()->json([
            'success' => true,
            'message' => 'Document dupliqué avec succès',
            'data' => $nouveauDocument->load(['etudiant.utilisateur', 'modeleDocument'])
        ]);
    }

    /**
     * Obtenir les types de documents disponibles
     */
    public function getTypes()
    {
        $types = Document::select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    /**
     * Obtenir les statistiques des documents
     */
    public function statistiques()
    {
        $utilisateur = Auth::user();
        
        $statistiques = [
            'total_documents' => Document::where('etudiant_id', $utilisateur->etudiant->id ?? null)->count(),
            'documents_publics' => Document::where('etudiant_id', $utilisateur->etudiant->id ?? null)->where('est_public', true)->count(),
            'documents_archives' => Document::where('etudiant_id', $utilisateur->etudiant->id ?? null)->where('est_public', false)->count(),
            'documents_ce_mois' => Document::where('etudiant_id', $utilisateur->etudiant->id ?? null)
                ->whereMonth('date_generation', now()->month)
                ->whereYear('date_generation', now()->year)
                ->count(),
            'taille_totale' => Document::where('etudiant_id', $utilisateur->etudiant->id ?? null)
                ->get()
                ->sum(function ($doc) {
                    return $doc->getTailleFichier();
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $statistiques
        ]);
    }

    /**
     * Rechercher des documents
     */
    public function search(Request $request)
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

    /**
     * Générer le chemin du fichier
     */
    /**
     * Récupère les données de l'étudiant pour préremplir les documents
     */
    public function getStudentData($etudiantId)
    {
        $etudiant = Etudiant::with(['utilisateur', 'classe'])->findOrFail($etudiantId);
        
        $data = [
            'etudiant' => [
                'id' => $etudiant->id,
                'numero_etudiant' => $etudiant->numero_etudiant,
                'nom' => $etudiant->utilisateur->nom,
                'prenom' => $etudiant->utilisateur->prenom,
                'email' => $etudiant->utilisateur->email,
                'date_naissance' => $etudiant->utilisateur->date_naissance,
                'lieu_naissance' => $etudiant->lieu_naissance ?? '',
                'adresse' => $etudiant->adresse ?? '',
                'telephone' => $etudiant->telephone ?? '',
                'filiere' => $etudiant->filiere,
                'annee' => $etudiant->annee,
                'groupe' => $etudiant->groupe,
                'date_inscription' => $etudiant->date_inscription,
            ],
            'classe' => $etudiant->classe ? [
                'id' => $etudiant->classe->id,
                'filiere' => $etudiant->classe->filiere,
                'annee' => $etudiant->classe->annee,
                'groupe' => $etudiant->classe->groupe,
                'label' => $etudiant->classe->label,
            ] : null,
            'date_emission' => now()->format('d/m/Y'),
            'annee_scolaire' => $this->getAnneeScolaire(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Obtient l'année scolaire en cours
     */
    private function getAnneeScolaire()
    {
        $mois = (int) date('m');
        $annee = (int) date('Y');
        
        if ($mois >= 9) { // Septembre à Décembre
            return $annee . '/' . ($annee + 1);
        } else { // Janvier à Août
            return ($annee - 1) . '/' . $annee;
        }
    }

    private function buildBulletinDataFromNotes(Etudiant $etudiant, Request $request): array
    {
        $periode = $request->input('trimestre', $request->input('periode', '1er Trimestre'));

        $notesQuery = Note::where('etudiant_id', $etudiant->id)
            ->where('est_valide', true);

        if ($request->filled('periode')) {
            $notesQuery->where('periode', $request->input('periode'));
        }

        $notes = $notesQuery->get();

        $parMatiere = $notes->groupBy('matiere')->map(function ($collection, $matiere) {
            $moyenne = $collection->avg('valeur');

            return [
                'matiere' => $matiere,
                'moyenne' => round((float) $moyenne, 2),
                'notes' => $collection->map(function (Note $note) {
                    return [
                        'type_controle' => $note->type_controle,
                        'valeur' => (float) $note->valeur,
                        'date' => $note->date ? $note->date->format('d/m/Y') : null,
                        'commentaire' => $note->commentaire,
                        'periode' => $note->periode,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $moyenneGenerale = null;
        if ($notes->count() > 0) {
            $moyenneGenerale = round((float) $notes->avg('valeur'), 2);
        }

        return [
            'bulletin_notes' => [
                'periode' => $periode,
                'moyenne_generale' => $moyenneGenerale,
                'matieres' => $parMatiere,
            ],
        ];
    }

    /**
     * Créer un nouveau document avec préremplissage des données étudiant
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modele_document_id' => 'required|exists:modele_documents,id',
            'type' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'donnees' => 'sometimes|array',
            'est_public' => 'sometimes|boolean',
            'etudiant_id' => 'required|exists:etudiants,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Récupérer l'étudiant avec ses relations
        $etudiant = Etudiant::with(['utilisateur', 'classe'])->findOrFail($request->etudiant_id);
        
        // Récupérer les informations de l'établissement (à remplacer par votre logique de récupération)
        $infosEtablissement = [
            'nom_etablissement' => 'Institut Supérieur d\'Informatique',
            'adresse_etablissement' => '123 Avenue de l\'Université',
            'code_postal_etablissement' => '75000',
            'ville_etablissement' => 'Paris',
            'pays_etablissement' => 'France',
            'telephone_etablissement' => '01 23 45 67 89',
            'email_etablissement' => 'contact@example.com',
            'site_web_etablissement' => 'www.example.com',
            'logo_etablissement' => asset('images/logo-isi.png'),
            'directeur_nom' => 'Jean Dupont',
            'directeur_poste' => 'Directeur',
        ];
        
        // Formater la date de naissance
        $dateNaissance = $etudiant->utilisateur->date_naissance 
            ? \Carbon\Carbon::parse($etudiant->utilisateur->date_naissance)->format('d/m/Y')
            : 'Non renseignée';
            
        // Formater la date d'inscription
        $dateInscription = $etudiant->date_inscription 
            ? \Carbon\Carbon::parse($etudiant->date_inscription)->format('d/m/Y')
            : now()->format('d/m/Y');
        
        // Préparer les données par défaut
        $donneesParDefaut = [
            // Informations de l'étudiant
            'etudiant_id' => $etudiant->id,
            'etudiant_numero' => $etudiant->numero_etudiant ?? 'Non défini',
            'etudiant_nom' => $etudiant->utilisateur->nom,
            'etudiant_prenom' => $etudiant->utilisateur->prenom,
            'etudiant_email' => $etudiant->utilisateur->email,
            'etudiant_date_naissance' => $dateNaissance,
            'etudiant_lieu_naissance' => $etudiant->lieu_naissance ?? 'Non renseigné',
            'etudiant_adresse' => $etudiant->adresse ?? 'Non renseignée',
            'etudiant_telephone' => $etudiant->telephone ?? 'Non renseigné',
            'etudiant_filiere' => $etudiant->filiere ?? 'Non renseignée',
            'etudiant_annee' => $etudiant->annee ?? 'Non renseignée',
            'etudiant_groupe' => $etudiant->groupe ?? 'Non renseigné',
            'etudiant_date_inscription' => $dateInscription,
            
            // Informations de l'établissement
            'nom_etablissement' => $infosEtablissement['nom_etablissement'],
            'adresse_etablissement' => $infosEtablissement['adresse_etablissement'],
            'code_postal_etablissement' => $infosEtablissement['code_postal_etablissement'],
            'ville_etablissement' => $infosEtablissement['ville_etablissement'],
            'pays_etablissement' => $infosEtablissement['pays_etablissement'],
            'telephone_etablissement' => $infosEtablissement['telephone_etablissement'],
            'email_etablissement' => $infosEtablissement['email_etablissement'],
            'site_web_etablissement' => $infosEtablissement['site_web_etablissement'],
            'logo_etablissement' => $infosEtablissement['logo_etablissement'],
            'directeur_nom' => $infosEtablissement['directeur_nom'],
            'directeur_poste' => $infosEtablissement['directeur_poste'],
            
            // Informations de date et année scolaire
            'date_emission' => now()->locale('fr_FR')->isoFormat('LL'),
            'annee_scolaire' => $this->getAnneeScolaire(),
        ];

        // Fusionner avec les données fournies (celles-ci écraseront les valeurs par défaut si elles existent)
        $donnees = array_merge($donneesParDefaut, $request->donnees ?? []);

        // Démarrer une transaction pour s'assurer que tout est cohérent
        return DB::transaction(function () use ($request, $etudiant, $donnees) {
            $document = Document::create([
                'modele_document_id' => $request->modele_document_id,
                'etudiant_id' => $etudiant->id,
                'administrateur_id' => Auth::user()->administrateur->id ?? null,
                'type' => $request->type,
                'nom' => $request->nom,
                'chemin_fichier' => $this->genererCheminFichier($request->type, $request->nom),
                'donnees_document' => $donnees,
                'est_public' => $request->boolean('est_public', true),
                'date_generation' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document créé avec succès',
                'data' => $document->load(['etudiant.utilisateur', 'modeleDocument'])
            ], 201);
        });
    }

    /**
     * Générer le chemin du fichier
     */
    private function genererCheminFichier($type, $nom)
    {
        $dossier = 'documents/' . $type . '/' . now()->year . '/' . now()->month;
        $nomFichier = \Str::slug($nom) . '_' . time() . '.pdf';
        
        return $dossier . '/' . $nomFichier;
    }

    /**
     * Publier un document pour tous les étudiants
     */
    public function publierPourTous(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string',
                'annee_scolaire' => 'required|string',
            ]);

            $type = $request->input('type');
            $anneeScolaire = $request->input('annee_scolaire');
            
            // Vérifier si le type de document est valide
            $modele = ModeleDocument::where('type_document', $type)->first();
            if (!$modele) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de document non valide.'
                ], 400);
            }

            // Récupérer tous les étudiants actifs
            $etudiants = Etudiant::with('utilisateur')
                ->whereHas('utilisateur', function($query) {
                    $query->where('est_actif', true);
                })
                ->get();

            $resultats = [
                'success' => [],
                'errors' => []
            ];

            // Désactiver temporairement les notifications par email
            config(['mail.default' => 'log']);

            foreach ($etudiants as $etudiant) {
                try {
                    // Préparer les données du document
                    $donnees = [
                        'annee_scolaire' => $anneeScolaire,
                        'date_emission' => now()->format('d/m/Y'),
                        'etudiant_id' => $etudiant->id,
                        'etudiant_nom' => $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'etudiant_email' => $etudiant->utilisateur->email,
                        // Ajoutez ici d'autres champs spécifiques au type de document
                    ];

                    // Créer le document
                    $document = Document::create([
                        'modele_document_id' => $modele->id,
                        'etudiant_id' => $etudiant->id,
                        'administrateur_id' => auth()->id(),
                        'type' => $type,
                        'nom' => ucfirst($type) . ' - ' . $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'chemin_fichier' => $this->genererCheminFichier($type, $type . '_' . $etudiant->id),
                        'donnees_document' => $donnees,
                        'est_public' => true,
                        'date_generation' => now()
                    ]);

                    $resultats['success'][] = [
                        'etudiant_id' => $etudiant->id,
                        'etudiant_nom' => $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'document_id' => $document->id
                    ];

                } catch (\Exception $e) {
                    \Log::error('Erreur génération document pour étudiant ' . $etudiant->id . ': ' . $e->getMessage());
                    
                    $resultats['errors'][] = [
                        'etudiant_id' => $etudiant->id,
                        'etudiant_nom' => $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'message' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Publication terminée.',
                'resultats' => $resultats
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la publication groupée: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la publication groupée.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publier les bulletins de notes pour tous les étudiants
     */
    public function publierBulletinsPourTous(Request $request)
    {
        try {
            // Vérifier si l'utilisateur est autorisé
            if (!auth()->user()->hasRole('administrateur') && !auth()->user()->hasRole('directeur')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.'
                ], 403);
            }

            // Récupérer le modèle de bulletin de notes
            $modele = ModeleDocument::where('type_document', 'bulletin_notes')->first();
            
            if (!$modele) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modèle de bulletin de notes non trouvé.'
                ], 404);
            }

            // Récupérer tous les étudiants actifs
            $etudiants = Etudiant::with('utilisateur')
                ->whereHas('utilisateur', function($query) {
                    $query->where('est_actif', true);
                })
                ->get();

            $resultats = [
                'success' => [],
                'errors' => []
            ];

            // Désactiver temporairement les notifications par email
            config(['mail.default' => 'log']);

            // Générer un bulletin pour chaque étudiant
            foreach ($etudiants as $etudiant) {
                try {
                    $baseDonnees = [
                        'annee_scolaire' => $request->input('annee_scolaire', $this->getAnneeScolaire()),
                        'trimestre' => $request->input('trimestre', '1er Trimestre'),
                        'date_emission' => now()->format('d/m/Y')
                    ];

                    $donneesNotes = $this->buildBulletinDataFromNotes($etudiant, $request);

                    $donnees = array_merge($baseDonnees, $donneesNotes);

                    // Créer le document
                    $document = Document::create([
                        'modele_document_id' => $modele->id,
                        'etudiant_id' => $etudiant->id,
                        'administrateur_id' => auth()->id(),
                        'type' => 'bulletin_notes',
                        'nom' => 'Bulletin de notes - ' . $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'chemin_fichier' => $this->genererCheminFichier('bulletins', 'bulletin_' . $etudiant->id),
                        'donnees_document' => $donnees,
                        'est_public' => true,
                        'date_generation' => now()
                    ]);

                    $resultats['success'][] = [
                        'etudiant_id' => $etudiant->id,
                        'etudiant_nom' => $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'document_id' => $document->id
                    ];

                } catch (\Exception $e) {
                    $resultats['errors'][] = [
                        'etudiant_id' => $etudiant->id,
                        'etudiant_nom' => $etudiant->utilisateur->nom . ' ' . $etudiant->utilisateur->prenom,
                        'message' => $e->getMessage()
                    ];
                    \Log::error('Erreur génération bulletin pour étudiant ' . $etudiant->id . ': ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Génération des bulletins terminée.',
                'resultats' => $resultats
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération des bulletins: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la génération des bulletins.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
