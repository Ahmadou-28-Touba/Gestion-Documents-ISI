# Documentation API - Gestion Documents ISI

## Vue d'ensemble

Cette API REST permet de gérer les documents, absences et utilisateurs de l'Institut Supérieur d'Informatique (ISI).

## Authentification

Toutes les routes (sauf `/auth/login` et `/auth/register`) nécessitent un token d'authentification.

```bash
Authorization: Bearer {token}
```

## Endpoints d'authentification

### POST /auth/login
Connexion utilisateur

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "utilisateur": {...},
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer"
  }
}
```

### POST /auth/register
Inscription utilisateur

**Body:**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "etudiant"
}
```

### POST /auth/logout
Déconnexion (nécessite authentification)

### GET /auth/me
Informations utilisateur connecté (nécessite authentification)

## Gestion des Documents

### GET /documents
Lister les documents avec filtres

**Query Parameters:**
- `type`: Filtrer par type
- `est_public`: Filtrer par statut (true/false)
- `utilisateur_id`: Filtrer par utilisateur
- `date_debut`: Date de début
- `date_fin`: Date de fin
- `sort_by`: Champ de tri
- `sort_order`: Ordre (asc/desc)
- `per_page`: Nombre d'éléments par page

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

### POST /documents
Créer un document

**Body:**
```json
{
  "modele_document_id": 1,
  "type": "attestation",
  "nom": "Attestation de scolarité",
  "donnees": {
    "nom": "Dupont",
    "prenom": "Jean"
  },
  "est_public": true
}
```

### GET /documents/{id}
Voir un document

### PUT /documents/{id}
Modifier un document

### DELETE /documents/{id}
Supprimer un document

### GET /documents/{id}/telecharger
Télécharger un document

### POST /documents/{id}/archiver
Archiver un document

### POST /documents/{id}/desarchiver
Désarchiver un document

### POST /documents/{id}/dupliquer
Dupliquer un document

**Body:**
```json
{
  "nom": "Nouveau nom (optionnel)"
}
```

### GET /documents/types
Obtenir les types de documents disponibles

### GET /documents/statistiques
Statistiques des documents

### GET /documents/search
Rechercher des documents

**Query Parameters:**
- `q`: Terme de recherche
- `type`: Type de document
- `utilisateur_id`: ID utilisateur

## Gestion des Absences

### GET /absences
Lister les absences

**Query Parameters:**
- `statut`: Filtrer par statut
- `date_debut`: Date de début
- `date_fin`: Date de fin
- `etudiant_id`: ID étudiant

### POST /absences
Créer une absence (étudiants uniquement)

**Body:**
```json
{
  "date_debut": "2024-01-15",
  "date_fin": "2024-01-16",
  "motif": "Rendez-vous médical",
  "justificatif": "fichier_upload"
}
```

### GET /absences/{id}
Voir une absence

### PUT /absences/{id}
Modifier une absence (étudiants uniquement)

### DELETE /absences/{id}
Supprimer une absence (étudiants uniquement)

### POST /absences/{id}/valider
Valider une absence (enseignants uniquement)

### POST /absences/{id}/rejeter
Rejeter une absence (enseignants uniquement)

**Body:**
```json
{
  "motif_refus": "Justificatif insuffisant"
}
```

### POST /absences/{id}/annuler
Annuler une absence (étudiants uniquement)

### GET /absences/statuts
Obtenir les statuts disponibles

### GET /absences/statistiques
Statistiques des absences

### GET /absences/search
Rechercher des absences

## Gestion des Modèles de Documents (Administrateurs)

### GET /admin/modeles
Lister les modèles

### POST /admin/modeles
Créer un modèle

**Body:**
```json
{
  "nom": "Attestation de scolarité",
  "type_document": "attestation",
  "chemin_modele": "templates/attestation.docx",
  "champs_requis": ["nom", "prenom", "filiere"],
  "description": "Modèle d'attestation de scolarité",
  "est_actif": true
}
```

### GET /admin/modeles/{id}
Voir un modèle

### PUT /admin/modeles/{id}
Modifier un modèle

### DELETE /admin/modeles/{id}
Supprimer un modèle

### POST /admin/modeles/{id}/dupliquer
Dupliquer un modèle

### POST /admin/modeles/{id}/activer
Activer un modèle

### POST /admin/modeles/{id}/desactiver
Désactiver un modèle

## Gestion des Utilisateurs (Administrateurs)

### GET /admin/utilisateurs
Lister les utilisateurs

### POST /admin/utilisateurs
Créer un utilisateur

### GET /admin/utilisateurs/{id}
Voir un utilisateur

### PUT /admin/utilisateurs/{id}
Modifier un utilisateur

### DELETE /admin/utilisateurs/{id}
Supprimer un utilisateur

## Codes de statut HTTP

- `200`: Succès
- `201`: Créé avec succès
- `400`: Requête invalide
- `401`: Non authentifié
- `403`: Accès refusé
- `404`: Ressource non trouvée
- `422`: Erreurs de validation
- `500`: Erreur serveur

## Format des réponses d'erreur

```json
{
  "success": false,
  "message": "Message d'erreur",
  "errors": {
    "champ": ["Erreur de validation"]
  }
}
```

## Exemples d'utilisation

### JavaScript (Axios)
```javascript
// Connexion
const response = await axios.post('/api/auth/login', {
  email: 'user@example.com',
  password: 'password123'
});

const token = response.data.data.token;

// Utiliser le token pour les requêtes suivantes
axios.defaults.headers.Authorization = `Bearer ${token}`;

// Créer un document
const document = await axios.post('/api/documents', {
  modele_document_id: 1,
  type: 'attestation',
  nom: 'Mon attestation',
  donnees: { nom: 'Dupont', prenom: 'Jean' }
});
```

### cURL
```bash
# Connexion
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Lister les documents
curl -X GET http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Tests

Un script de test est disponible pour vérifier le fonctionnement des API :

```bash
node test-api.js
```

## Notes importantes

1. **Permissions**: Certaines routes sont restreintes selon le rôle utilisateur
2. **Validation**: Tous les champs requis doivent être fournis
3. **Fichiers**: Les uploads de fichiers sont supportés pour les justificatifs
4. **Pagination**: Utilisez les paramètres `page` et `per_page` pour la pagination
5. **Filtres**: Tous les endpoints de liste supportent des filtres avancés