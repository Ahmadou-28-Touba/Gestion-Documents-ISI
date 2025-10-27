#!/usr/bin/env node

/**
 * Script de test spécifique pour les API Administrateur
 * Usage: node test-admin-api.js
 */

const axios = require('axios');

// Configuration
const BASE_URL = 'http://localhost:8000/api';
let authToken = null;

// Configuration axios
const api = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Fonction pour afficher les résultats
function logTest(testName, result) {
  const status = result.success ? '✅' : '❌';
  console.log(`${status} ${testName}`);
  if (!result.success && result.error) {
    console.log(`   Erreur: ${result.error}`);
  }
  if (result.data) {
    console.log(`   Données: ${JSON.stringify(result.data).substring(0, 100)}...`);
  }
}

// Tests d'authentification administrateur
async function testAdminAuth() {
  console.log('\n🔐 Tests d\'authentification administrateur');
  
  try {
    // Test de connexion administrateur
    const loginResponse = await api.post('/auth/login', {
      email: 'admin@isi.com',
      password: 'password123'
    });
    
    if (loginResponse.data.success) {
      authToken = loginResponse.data.data.token;
      api.defaults.headers.Authorization = `Bearer ${authToken}`;
      logTest('Connexion administrateur', { success: true, data: loginResponse.data.data });
    } else {
      logTest('Connexion administrateur', { success: false, error: loginResponse.data.message });
    }
  } catch (error) {
    logTest('Connexion administrateur', { success: false, error: error.message });
  }
}

// Tests du dashboard administrateur
async function testAdminDashboard() {
  console.log('\n📊 Tests du dashboard administrateur');
  
  try {
    // Statistiques générales
    const statsResponse = await api.get('/admin/statistiques');
    logTest('Statistiques générales', { 
      success: statsResponse.data.success, 
      data: statsResponse.data.data 
    });
    
    // Dashboard
    const dashboardResponse = await api.get('/admin/dashboard');
    logTest('Dashboard administrateur', { 
      success: dashboardResponse.data.success, 
      data: dashboardResponse.data.data 
    });
    
  } catch (error) {
    logTest('Tests dashboard', { success: false, error: error.message });
  }
}

// Tests de gestion des utilisateurs
async function testUserManagement() {
  console.log('\n👥 Tests de gestion des utilisateurs');
  
  try {
    // Lister les utilisateurs
    const listResponse = await api.get('/admin/utilisateurs');
    logTest('Liste des utilisateurs', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Statistiques utilisateurs
    const statsResponse = await api.get('/admin/utilisateurs/statistiques');
    logTest('Statistiques utilisateurs', { 
      success: statsResponse.data.success, 
      data: statsResponse.data.data 
    });
    
    // Rechercher des utilisateurs
    const searchResponse = await api.get('/admin/utilisateurs/search?q=test');
    logTest('Recherche utilisateurs', { 
      success: searchResponse.data.success, 
      data: searchResponse.data.data 
    });
    
    // Créer un utilisateur test
    const createResponse = await api.post('/admin/utilisateurs', {
      nom: 'Test',
      prenom: 'User',
      email: 'test.user@example.com',
      password: 'password123',
      role: 'etudiant',
      donnees_specifiques: {
        numero_etudiant: 'ETU999',
        filiere: 'Informatique',
        annee: 2024
      }
    });
    logTest('Création utilisateur', { 
      success: createResponse.data.success, 
      data: createResponse.data.data 
    });
    
    const userId = createResponse.data.data?.id;
    
    if (userId) {
      // Voir un utilisateur
      const viewResponse = await api.get(`/admin/utilisateurs/${userId}`);
      logTest('Voir utilisateur', { 
        success: viewResponse.data.success, 
        data: viewResponse.data.data 
      });
      
      // Modifier un utilisateur
      const updateResponse = await api.put(`/admin/utilisateurs/${userId}`, {
        nom: 'Test Modifié'
      });
      logTest('Modifier utilisateur', { 
        success: updateResponse.data.success, 
        data: updateResponse.data.data 
      });
      
      // Supprimer un utilisateur
      const deleteResponse = await api.delete(`/admin/utilisateurs/${userId}`);
      logTest('Supprimer utilisateur', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests gestion utilisateurs', { success: false, error: error.message });
  }
}

// Tests de gestion des modèles
async function testModelManagement() {
  console.log('\n📋 Tests de gestion des modèles');
  
  try {
    // Lister les modèles
    const listResponse = await api.get('/admin/modeles');
    logTest('Liste des modèles', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Statistiques modèles
    const statsResponse = await api.get('/admin/modeles/statistiques');
    logTest('Statistiques modèles', { 
      success: statsResponse.data.success, 
      data: statsResponse.data.data 
    });
    
    // Créer un modèle test
    const createResponse = await api.post('/admin/modeles', {
      nom: 'Modèle Test',
      type_document: 'test',
      chemin_modele: 'templates/test.docx',
      champs_requis: ['nom', 'prenom'],
      description: 'Modèle de test',
      est_actif: true
    });
    logTest('Création modèle', { 
      success: createResponse.data.success, 
      data: createResponse.data.data 
    });
    
    const modeleId = createResponse.data.data?.id;
    
    if (modeleId) {
      // Voir un modèle
      const viewResponse = await api.get(`/admin/modeles/${modeleId}`);
      logTest('Voir modèle', { 
        success: viewResponse.data.success, 
        data: viewResponse.data.data 
      });
      
      // Désactiver un modèle
      const desactiverResponse = await api.post(`/admin/modeles/${modeleId}/desactiver`);
      logTest('Désactiver modèle', { 
        success: desactiverResponse.data.success, 
        data: desactiverResponse.data.data 
      });
      
      // Activer un modèle
      const activerResponse = await api.post(`/admin/modeles/${modeleId}/activer`);
      logTest('Activer modèle', { 
        success: activerResponse.data.success, 
        data: activerResponse.data.data 
      });
      
      // Dupliquer un modèle
      const dupliquerResponse = await api.post(`/admin/modeles/${modeleId}/dupliquer`, {
        nom: 'Modèle Test Copie'
      });
      logTest('Dupliquer modèle', { 
        success: dupliquerResponse.data.success, 
        data: dupliquerResponse.data.data 
      });
      
      // Supprimer un modèle
      const deleteResponse = await api.delete(`/admin/modeles/${modeleId}`);
      logTest('Supprimer modèle', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests gestion modèles', { success: false, error: error.message });
  }
}

// Tests de gestion des documents
async function testDocumentManagement() {
  console.log('\n📄 Tests de gestion des documents');
  
  try {
    // Lister les documents
    const listResponse = await api.get('/admin/documents');
    logTest('Liste des documents', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Rechercher des documents
    const searchResponse = await api.get('/admin/documents/search?q=test');
    logTest('Recherche documents', { 
      success: searchResponse.data.success, 
      data: searchResponse.data.data 
    });
    
    // Générer un document
    const generateResponse = await api.post('/admin/documents', {
      type: 'attestation_test',
      utilisateur_id: 1,
      donnees: {
        nom: 'Test',
        prenom: 'User'
      }
    });
    logTest('Génération document', { 
      success: generateResponse.data.success, 
      data: generateResponse.data.data 
    });
    
  } catch (error) {
    logTest('Tests gestion documents', { success: false, error: error.message });
  }
}

// Fonction principale
async function runAdminTests() {
  console.log('🚀 Démarrage des tests API Administrateur');
  console.log('==========================================');
  
  try {
    await testAdminAuth();
    
    if (authToken) {
      await testAdminDashboard();
      await testUserManagement();
      await testModelManagement();
      await testDocumentManagement();
    } else {
      console.log('❌ Impossible de continuer sans authentification administrateur');
    }
    
    console.log('\n✅ Tests administrateur terminés');
    
  } catch (error) {
    console.error('❌ Erreur lors des tests:', error.message);
  }
}

// Exécuter les tests
runAdminTests();
