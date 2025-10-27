<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ModeleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
     * Créer un nouveau document
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modele_document_id' => 'required|exists:modele_documents,id',
            'type' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
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

        $document = Document::create([
            'modele_document_id' => $request->modele_document_id,
            'etudiant_id' => Auth::user()->etudiant->id ?? null,
            'administrateur_id' => Auth::user()->administrateur->id ?? null,
            'type' => $request->type,
            'nom' => $request->nom,
            'chemin_fichier' => $this->genererCheminFichier($request->type, $request->nom),
            'donnees_document' => $request->donnees ?? [],
            'est_public' => $request->boolean('est_public', true),
            'date_generation' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document créé avec succès',
            'data' => $document->load(['etudiant.utilisateur', 'modeleDocument'])
        ], 201);
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
    private function genererCheminFichier($type, $nom)
    {
        $dossier = 'documents/' . $type . '/' . now()->year . '/' . now()->month;
        $nomFichier = \Str::slug($nom) . '_' . time() . '.pdf';
        
        return $dossier . '/' . $nomFichier;
    }
}
