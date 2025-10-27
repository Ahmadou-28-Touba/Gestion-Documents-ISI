#!/usr/bin/env node

/**
 * Script de test pour les API CRUD
 * Usage: node test-api.js
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

// Tests d'authentification
async function testAuth() {
  console.log('\n🔐 Tests d\'authentification');
  
  try {
    // Test de connexion
    const loginResponse = await api.post('/auth/login', {
      email: 'admin@isi.com',
      password: 'password123'
    });
    
    if (loginResponse.data.success) {
      authToken = loginResponse.data.data.token;
      api.defaults.headers.Authorization = `Bearer ${authToken}`;
      logTest('Connexion', { success: true, data: loginResponse.data.data });
    } else {
      logTest('Connexion', { success: false, error: loginResponse.data.message });
    }
  } catch (error) {
    logTest('Connexion', { success: false, error: error.message });
  }
}

// Tests des documents
async function testDocuments() {
  console.log('\n📄 Tests des documents');
  
  try {
    // Lister les documents
    const listResponse = await api.get('/documents');
    logTest('Liste des documents', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Créer un document
    const createResponse = await api.post('/documents', {
      modele_document_id: 1,
      type: 'test',
      nom: 'Document de test',
      donnees: { test: 'valeur' },
      est_public: true
    });
    logTest('Création document', { 
      success: createResponse.data.success, 
      data: createResponse.data.data 
    });
    
    const documentId = createResponse.data.data?.id;
    
    if (documentId) {
      // Voir un document
      const viewResponse = await api.get(`/documents/${documentId}`);
      logTest('Voir document', { 
        success: viewResponse.data.success, 
        data: viewResponse.data.data 
      });
      
      // Modifier un document
      const updateResponse = await api.put(`/documents/${documentId}`, {
        nom: 'Document modifié'
      });
      logTest('Modifier document', { 
        success: updateResponse.data.success, 
        data: updateResponse.data.data 
      });
      
      // Supprimer un document
      const deleteResponse = await api.delete(`/documents/${documentId}`);
      logTest('Supprimer document', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests documents', { success: false, error: error.message });
  }
}

// Tests des absences
async function testAbsences() {
  console.log('\n📅 Tests des absences');
  
  try {
    // Lister les absences
    const listResponse = await api.get('/absences');
    logTest('Liste des absences', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Créer une absence
    const createResponse = await api.post('/absences', {
      date_debut: '2024-01-15',
      date_fin: '2024-01-16',
      motif: 'Absence de test'
    });
    logTest('Création absence', { 
      success: createResponse.data.success, 
      data: createResponse.data.data 
    });
    
    const absenceId = createResponse.data.data?.id;
    
    if (absenceId) {
      // Voir une absence
      const viewResponse = await api.get(`/absences/${absenceId}`);
      logTest('Voir absence', { 
        success: viewResponse.data.success, 
        data: viewResponse.data.data 
      });
      
      // Supprimer une absence
      const deleteResponse = await api.delete(`/absences/${absenceId}`);
      logTest('Supprimer absence', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests absences', { success: false, error: error.message });
  }
}

// Tests des modèles de documents
async function testModeles() {
  console.log('\n📋 Tests des modèles de documents');
  
  try {
    // Lister les modèles
    const listResponse = await api.get('/admin/modeles');
    logTest('Liste des modèles', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Créer un modèle
    const createResponse = await api.post('/admin/modeles', {
      nom: 'Modèle de test',
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
      // Modifier un modèle
      const updateResponse = await api.put(`/admin/modeles/${modeleId}`, {
        nom: 'Modèle modifié'
      });
      logTest('Modifier modèle', { 
        success: updateResponse.data.success, 
        data: updateResponse.data.data 
      });
      
      // Supprimer un modèle
      const deleteResponse = await api.delete(`/admin/modeles/${modeleId}`);
      logTest('Supprimer modèle', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests modèles', { success: false, error: error.message });
  }
}

// Tests des statistiques
async function testStatistiques() {
  console.log('\n📊 Tests des statistiques');
  
  try {
    // Statistiques des documents
    const docStatsResponse = await api.get('/documents/statistiques');
    logTest('Statistiques documents', { 
      success: docStatsResponse.data.success, 
      data: docStatsResponse.data.data 
    });
    
    // Statistiques des absences
    const absStatsResponse = await api.get('/absences/statistiques');
    logTest('Statistiques absences', { 
      success: absStatsResponse.data.success, 
      data: absStatsResponse.data.data 
    });
    
  } catch (error) {
    logTest('Tests statistiques', { success: false, error: error.message });
  }
}

// Fonction principale
async function runTests() {
  console.log('🚀 Démarrage des tests API CRUD');
  console.log('================================');
  
  try {
    await testAuth();
    
    if (authToken) {
      await testDocuments();
      await testAbsences();
      await testModeles();
      await testStatistiques();
    } else {
      console.log('❌ Impossible de continuer sans authentification');
    }
    
    console.log('\n✅ Tests terminés');
    
  } catch (error) {
    console.error('❌ Erreur lors des tests:', error.message);
  }
}

// Exécuter les tests
runTests();
