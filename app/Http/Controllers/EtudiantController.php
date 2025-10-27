<?php

namespace App\Http\Controllers;

use App\Models\Etudiant;
use App\Models\Absence;
use App\Models\Document;
use App\Models\DownloadLog;
use App\Jobs\RegenererDocument;
use App\Services\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EtudiantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function dashboard()
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $utilisateur->etudiant;
        
        $statistiques = [
            'total_absences' => $etudiant->absences()->count(),
            'absences_validees' => $etudiant->absences()->validees()->count(),
            'absences_refusees' => $etudiant->absences()->refusees()->count(),
            'absences_en_attente' => $etudiant->absences()->enAttente()->count(),
            'total_documents' => $etudiant->documents()->publics()->count(),
        ];

        $recent_absences = $etudiant->absences()
            ->with('enseignant.utilisateur')
            ->latest()
            ->limit(5)
            ->get();

        $recent_documents = $etudiant->documents()
            ->publics()
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'etudiant' => $etudiant,
                'statistiques' => $statistiques,
                'recent_absences' => $recent_absences,
                'recent_documents' => $recent_documents,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'motif' => 'required|string|max:1000',
            'justificatif' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $etudiant = $utilisateur->etudiant;
        $justificatifChemin = null;

        // Gérer l'upload du justificatif si fourni
        if ($request->hasFile('justificatif')) {
            $justificatifChemin = $request->file('justificatif')->store(
                'justificatifs/absences/' . $etudiant->numero_etudiant,
                'public'
            );
        }

        $absence = $etudiant->declarerAbsence(
            $request->date_debut,
            $request->date_fin,
            $request->motif,
            $justificatifChemin
        );

        return response()->json([
            'success' => true,
            'message' => 'Absence déclarée avec succès',
            'data' => $absence
        ], 201);
    }

    public function index(Request $request)
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $utilisateur->etudiant;
        
        $query = $etudiant->absences()->with('enseignant.utilisateur');

        // Filtres
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('date_debut')) {
            $query->where('date_debut', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_fin', '<=', $request->date_fin);
        }

        $absences = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $absences
        ]);
    }

    public function consulterDocuments(Request $request)
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $utilisateur->etudiant;
        
        $query = $etudiant->documents()->publics();

        // Filtres
        if ($request->has('type')) {
            $query->parType($request->type);
        }

        if ($request->has('date_debut')) {
            $query->where('date_generation', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_generation', '<=', $request->date_fin);
        }

        $documents = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function telechargerDocument($id)
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $utilisateur->etudiant;
        $document = $etudiant->documents()->publics()->findOrFail($id);

        if (!Storage::disk('public')->exists($document->chemin_fichier)) {
            // Fallback: régénérer si modèle HTML léger, sinon en file d'attente
            $document->load('modeleDocument');
            $modele = $document->modeleDocument;
            if (!$modele) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modèle introuvable pour régénération'
                ], 404);
            }

            $ext = strtolower(pathinfo($modele->chemin_modele ?? '', PATHINFO_EXTENSION));
            $isHtml = in_array($ext, ['html', 'htm']);

            if ($isHtml) {
                // Régénération immédiate
                /** @var PdfRenderer $renderer */
                $renderer = app(PdfRenderer::class);
                $pdfBytes = $renderer->renderFromTemplatePath($modele->chemin_modele, $document->donnees_document ?? []);

                // Déterminer un chemin
                $nomBase = $document->nom ?: ($modele->nom . '_' . ($etudiant->numero_etudiant ?? $etudiant->id));
                $chemin = 'documents/' . $document->type . '/' . now()->year . '/' . now()->month . '/' . Str::slug($nomBase) . '_' . Str::uuid() . '.pdf';

                Storage::disk('public')->put($chemin, $pdfBytes);
                $document->chemin_fichier = $chemin;
                $document->date_generation = now();
                $document->save();
            } else {
                // Enfiler un job et informer l'étudiant
                RegenererDocument::dispatch($document->id)->onQueue('default');
                return response()->json([
                    'success' => false,
                    'message' => 'Le document est en cours de régénération. Réessayez dans quelques instants.'
                ], 202);
            }
        }

        DownloadLog::create([
            'user_id' => $utilisateur->id,
            'document_id' => $document->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'downloaded_at' => now(),
        ]);

        return Storage::disk('public')->download($document->chemin_fichier, $document->nom . '.pdf');
    }

    public function profil()
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $etudiant = $utilisateur->etudiant;

        return response()->json([
            'success' => true,
            'data' => [
                'utilisateur' => $utilisateur,
                'etudiant' => $etudiant,
            ]
        ]);
    }

    public function modifierProfil(Request $request)
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur->isEtudiant()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:utilisateurs,email,' . $utilisateur->id,
            'filiere' => 'sometimes|string|max:255',
            'annee' => 'sometimes|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mettre à jour les données utilisateur
        $utilisateur->update($request->only(['nom', 'prenom', 'email']));

        // Mettre à jour les données étudiant
        $etudiant = $utilisateur->etudiant;
        $etudiant->update($request->only(['filiere', 'annee']));

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => [
                'utilisateur' => $utilisateur->fresh(),
                'etudiant' => $etudiant->fresh(),
            ]
        ]);
    }
}
