<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Administrateur extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'departement',
        'date_embauche',
    ];

    protected $casts = [
        'date_embauche' => 'date',
    ];

    // 🔗 Relation avec l'utilisateur
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    private function genererCheminFichier(string $type, string $nom): string
    {
        $dossier = 'documents/' . $type . '/' . now()->year . '/' . now()->month;
        $nomFichier = Str::slug($nom) . '_' . time() . '.pdf';
        return $dossier . '/' . $nomFichier;
    }

    // 🔗 Relation avec les documents générés
    public function documents()
    {
        return $this->hasMany(Document::class, 'administrateur_id');
    }

    // 🔗 Tous les documents
    public function tousLesDocuments()
    {
        return $this->documents()->orderBy('created_at', 'desc')->get();
    }

    // 🔗 Documents par type
    public function documentsParType($type)
    {
        return $this->documents()->where('type', $type)->orderBy('created_at', 'desc')->get();
    }

    // 📊 Statistiques sur les documents
    public function statistiquesDocuments()
    {
        return [
            'total' => $this->documents()->count(),
            'publics' => $this->documents()->where('est_public', true)->count(),
            'archives' => $this->documents()->where('est_archive', true)->count(),
            'recents' => $this->documents()->where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    // 🧾 Génération de document
    public function genererDocument($type, $utilisateurId, $donnees = [])
    {
        $modele = ModeleDocument::where('type_document', $type)->where('est_actif', true)->first();

        if (!$modele) {
            throw new \Exception("Aucun modèle actif trouvé pour le type de document : $type");
        }

        $nomDoc = $donnees['nom'] ?? ucfirst($type) . ' - ' . now()->format('d-m-Y H:i');
        $chemin = $this->genererCheminFichier($type, $nomDoc);

        $document = Document::create([
            'nom' => $nomDoc,
            'type' => $type,
            'etudiant_id' => $utilisateurId,
            'modele_document_id' => $modele->id,
            'administrateur_id' => $this->id,
            'donnees_document' => $donnees,
            'est_public' => $donnees['est_public'] ?? true,
            'date_generation' => now(),
            'chemin_fichier' => $chemin,
        ]);

        return $document;
    }

    // 📦 Archiver plusieurs documents
    public function archiverDocuments(array $documentIds)
    {
        return Document::whereIn('id', $documentIds)
            ->update(['est_archive' => true]);
    }

    // 👤 Création d’un utilisateur
    public function creerUtilisateur(array $data, string $role, array $donneesSpecifiques = [])
    {
        DB::beginTransaction();

        try {
            $utilisateur = Utilisateur::create([
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $role,
            ]);

            switch ($role) {
                case 'etudiant':
                    Etudiant::create(array_merge(['utilisateur_id' => $utilisateur->id], $donneesSpecifiques));
                    break;
                case 'enseignant':
                    Enseignant::create(array_merge(['utilisateur_id' => $utilisateur->id], $donneesSpecifiques));
                    break;
                case 'administrateur':
                    self::create(array_merge(['utilisateur_id' => $utilisateur->id], $donneesSpecifiques));
                    break;
                case 'directeur':
                    Directeur::create(array_merge(['utilisateur_id' => $utilisateur->id], $donneesSpecifiques));
                    break;
            }

            DB::commit();
            return $utilisateur->load([$role]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Erreur lors de la création de l’utilisateur : " . $e->getMessage());
        }
    }

    // ✏️ Modification d’un utilisateur
    public function modifierUtilisateur($id, array $data, array $donneesSpecifiques = [])
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->update($data);

        $role = $utilisateur->role;
        $relation = $utilisateur->$role;

        if ($relation && !empty($donneesSpecifiques)) {
            $relation->update($donneesSpecifiques);
        }

        return $utilisateur->fresh([$role]);
    }

    // ❌ Suppression d’un utilisateur
    public function supprimerUtilisateur($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        DB::beginTransaction();

        try {
            $role = $utilisateur->role;
            if ($relation = $utilisateur->$role) {
                $relation->delete();
            }
            $utilisateur->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
}
