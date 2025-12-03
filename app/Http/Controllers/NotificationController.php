<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $utilisateur = Auth::user();
        
        $query = $utilisateur->notifications();
        
        // Filtres
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('lue')) {
            $query->where('lue', $request->boolean('lue'));
        }
        
        $notifications = $query->latest()->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function show($id)
    {
        $utilisateur = Auth::user();
        $notification = $utilisateur->notifications()->findOrFail($id);
        
        // Marquer comme lue
        if (!$notification->lue) {
            $notification->update(['lue' => true, 'date_lecture' => now()]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    public function marquerCommeLue($id)
    {
        $utilisateur = Auth::user();
        $notification = $utilisateur->notifications()->findOrFail($id);
        
        $notification->update([
            'lue' => true,
            'date_lecture' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    public function marquerToutesCommeLues()
    {
        $utilisateur = Auth::user();
        
        $utilisateur->notifications()
            ->where('lue', false)
            ->update([
                'lue' => true,
                'date_lecture' => now()
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    public function supprimer($id)
    {
        $utilisateur = Auth::user();
        $notification = $utilisateur->notifications()->findOrFail($id);
        
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }

    public function supprimerToutes()
    {
        $utilisateur = Auth::user();
        
        $utilisateur->notifications()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été supprimées'
        ]);
    }

    public function statistiques()
    {
        $utilisateur = Auth::user();
        
        $statistiques = [
            'total' => $utilisateur->notifications()->count(),
            'non_lues' => $utilisateur->notifications()->where('lue', false)->count(),
            'lues' => $utilisateur->notifications()->where('lue', true)->count(),
            'par_type' => $utilisateur->notifications()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $statistiques
        ]);
    }

    // Méthodes pour créer des notifications
    public static function creerNotification($utilisateurId, $type, $titre, $message, $donnees = [])
    {
        return Notification::create([
            'utilisateur_id' => $utilisateurId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'donnees' => $donnees,
            'lue' => false
        ]);
    }

    public static function notifierAbsenceDeclaree($absence)
    {
        // Notifier l'enseignant
        if ($absence->enseignant) {
            self::creerNotification(
                $absence->enseignant->utilisateur_id,
                'absence_declaree',
                'Nouvelle absence déclarée',
                "L'étudiant {$absence->etudiant->utilisateur->nom} {$absence->etudiant->utilisateur->prenom} a déclaré une absence du {$absence->date_debut} au {$absence->date_fin}",
                [
                    'absence_id' => $absence->id,
                    'etudiant_id' => $absence->etudiant_id,
                    'date_debut' => $absence->date_debut,
                    'date_fin' => $absence->date_fin
                ]
            );
        }
    }

    public static function notifierAbsenceValidee($absence)
    {
        // Notifier l'étudiant (validation par la direction)
        self::creerNotification(
            $absence->etudiant->utilisateur_id,
            'absence_validee',
            'Absence validée',
            "Votre absence du {$absence->date_debut} au {$absence->date_fin} a été validée par la direction.",
            [
                'absence_id' => $absence->id,
                'date_debut' => $absence->date_debut,
                'date_fin' => $absence->date_fin
            ]
        );
    }

    public static function notifierAbsenceRefusee($absence)
    {
        // Notifier l'étudiant (refus par la direction)
        self::creerNotification(
            $absence->etudiant->utilisateur_id,
            'absence_refusee',
            'Absence refusée',
            "Votre absence du {$absence->date_debut} au {$absence->date_fin} a été refusée par la direction.",
            [
                'absence_id' => $absence->id,
                'date_debut' => $absence->date_debut,
                'date_fin' => $absence->date_fin,
                'motif_refus' => $absence->motif_refus
            ]
        );
    }

    public static function notifierDocumentGenere($document)
    {
        // Notifier l'étudiant
        self::creerNotification(
            $document->etudiant->utilisateur_id,
            'document_genere',
            'Document généré',
            "Votre document '{$document->nom}' a été généré avec succès",
            [
                'document_id' => $document->id,
                'type' => $document->type,
                'nom' => $document->nom
            ]
        );
    }

    public static function notifierNouvelUtilisateur($utilisateur)
    {
        // Notifier les administrateurs
        $administrateurs = Utilisateur::where('role', 'administrateur')->get();
        
        foreach ($administrateurs as $admin) {
            self::creerNotification(
                $admin->id,
                'nouvel_utilisateur',
                'Nouvel utilisateur inscrit',
                "Un nouvel utilisateur s'est inscrit: {$utilisateur->nom} {$utilisateur->prenom} ({$utilisateur->role})",
                [
                    'utilisateur_id' => $utilisateur->id,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'role' => $utilisateur->role
                ]
            );
        }
    }
}
