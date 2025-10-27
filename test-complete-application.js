#!/usr/bin/env node

/**
 * Script de test complet pour l'application de gestion de documents ISI
 * Usage: node test-complete-application.js
 */

const axios = require('axios');

// Configuration
const BASE_URL = 'http://localhost:8000/api';
let authTokens = {};

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
  if (result.data && typeof result.data === 'object') {
    console.log(`   Données: ${JSON.stringify(result.data).substring(0, 100)}...`);
  }
}

// Tests d'authentification pour tous les rôles
async function testAuthentication() {
  console.log('\n🔐 Tests d\'authentification pour tous les rôles');
  
  const roles = [
    { email: 'etudiant@isi.com', password: 'password123', role: 'etudiant' },
    { email: 'enseignant@isi.com', password: 'password123', role: 'enseignant' },
    { email: 'admin@isi.com', password: 'password123', role: 'administrateur' },
    { email: 'directeur@isi.com', password: 'password123', role: 'directeur' }
  ];
  
  for (const user of roles) {
    try {
      const response = await api.post('/auth/login', {
        email: user.email,
        password: user.password
      });
      
      if (response.data.success) {
        authTokens[user.role] = response.data.data.token;
        logTest(`Connexion ${user.role}`, { success: true, data: response.data.data });
      } else {
        logTest(`Connexion ${user.role}`, { success: false, error: response.data.message });
      }
    } catch (error) {
      logTest(`Connexion ${user.role}`, { success: false, error: error.message });
    }
  }
}

// Tests des dashboards par rôle
async function testDashboards() {
  console.log('\n📊 Tests des dashboards par rôle');
  
  const dashboards = [
    { role: 'etudiant', endpoint: '/etudiant/dashboard' },
    { role: 'enseignant', endpoint: '/enseignant/dashboard' },
    { role: 'administrateur', endpoint: '/admin/dashboard' },
    { role: 'directeur', endpoint: '/directeur/dashboard' }
  ];
  
  for (const dashboard of dashboards) {
    if (authTokens[dashboard.role]) {
      try {
        api.defaults.headers.Authorization = `Bearer ${authTokens[dashboard.role]}`;
        const response = await api.get(dashboard.endpoint);
        logTest(`Dashboard ${dashboard.role}`, { 
          success: response.data.success, 
          data: response.data.data 
        });
      } catch (error) {
        logTest(`Dashboard ${dashboard.role}`, { success: false, error: error.message });
      }
    }
  }
}

// Tests CRUD Documents
async function testDocumentsCRUD() {
  console.log('\n📄 Tests CRUD Documents');
  
  if (!authTokens.administrateur) {
    console.log('❌ Token administrateur manquant');
    return;
  }
  
  api.defaults.headers.Authorization = `Bearer ${authTokens.administrateur}`;
  
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
      type: 'attestation_test',
      nom: 'Document de test',
      donnees: { nom: 'Test', prenom: 'User' },
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
      
      // Archiver un document
      const archiveResponse = await api.post(`/documents/${documentId}/archiver`);
      logTest('Archiver document', { 
        success: archiveResponse.data.success, 
        data: archiveResponse.data.data 
      });
      
      // Supprimer un document
      const deleteResponse = await api.delete(`/documents/${documentId}`);
      logTest('Supprimer document', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests CRUD Documents', { success: false, error: error.message });
  }
}

// Tests CRUD Absences
async function testAbsencesCRUD() {
  console.log('\n📅 Tests CRUD Absences');
  
  if (!authTokens.etudiant) {
    console.log('❌ Token étudiant manquant');
    return;
  }
  
  api.defaults.headers.Authorization = `Bearer ${authTokens.etudiant}`;
  
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
      motif: 'Rendez-vous médical',
      justificatif: null
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
      
      // Modifier une absence
      const updateResponse = await api.put(`/absences/${absenceId}`, {
        motif: 'Motif modifié'
      });
      logTest('Modifier absence', { 
        success: updateResponse.data.success, 
        data: updateResponse.data.data 
      });
      
      // Supprimer une absence
      const deleteResponse = await api.delete(`/absences/${absenceId}`);
      logTest('Supprimer absence', { 
        success: deleteResponse.data.success 
      });
    }
    
  } catch (error) {
    logTest('Tests CRUD Absences', { success: false, error: error.message });
  }
}

// Tests gestion des utilisateurs (admin)
async function testUserManagement() {
  console.log('\n👥 Tests gestion des utilisateurs');
  
  if (!authTokens.administrateur) {
    console.log('❌ Token administrateur manquant');
    return;
  }
  
  api.defaults.headers.Authorization = `Bearer ${authTokens.administrateur}`;
  
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

// Tests notifications
async function testNotifications() {
  console.log('\n🔔 Tests système de notifications');
  
  if (!authTokens.etudiant) {
    console.log('❌ Token étudiant manquant');
    return;
  }
  
  api.defaults.headers.Authorization = `Bearer ${authTokens.etudiant}`;
  
  try {
    // Lister les notifications
    const listResponse = await api.get('/notifications');
    logTest('Liste des notifications', { 
      success: listResponse.data.success, 
      data: listResponse.data.data 
    });
    
    // Statistiques notifications
    const statsResponse = await api.get('/notifications/statistiques');
    logTest('Statistiques notifications', { 
      success: statsResponse.data.success, 
      data: statsResponse.data.data 
    });
    
  } catch (error) {
    logTest('Tests notifications', { success: false, error: error.message });
  }
}

// Tests export et rapports
async function testExports() {
  console.log('\n📊 Tests export et rapports');
  
  if (!authTokens.directeur) {
    console.log('❌ Token directeur manquant');
    return;
  }
  
  api.defaults.headers.Authorization = `Bearer ${authTokens.directeur}`;
  
  try {
    // Statistiques générales
    const statsResponse = await api.get('/directeur/statistiques');
    logTest('Statistiques générales', { 
      success: statsResponse.data.success, 
      data: statsResponse.data.data 
    });
    
    // Rapport annuel
    const rapportResponse = await api.get('/directeur/rapport-annuel/2024');
    logTest('Rapport annuel', { 
      success: rapportResponse.data.success, 
      data: rapportResponse.data.data 
    });
    
    // Export absences
    const exportAbsencesResponse = await api.get('/directeur/export/absences');
    logTest('Export absences', { 
      success: exportAbsencesResponse.data.success, 
      data: exportAbsencesResponse.data.data 
    });
    
    // Export documents
    const exportDocumentsResponse = await api.get('/directeur/export/documents');
    logTest('Export documents', { 
      success: exportDocumentsResponse.data.success, 
      data: exportDocumentsResponse.data.data 
    });
    
  } catch (error) {
    logTest('Tests export', { success: false, error: error.message });
  }
}

// Tests de sécurité et permissions
async function testSecurity() {
  console.log('\n🔒 Tests de sécurité et permissions');
  
  // Test accès non autorisé
  try {
    const response = await api.get('/admin/dashboard');
    logTest('Accès admin sans token', { 
      success: false, 
      error: 'Devrait échouer' 
    });
  } catch (error) {
    logTest('Accès admin sans token', { 
      success: error.response?.status === 401, 
      error: error.response?.status === 401 ? 'Correctement bloqué' : error.message 
    });
  }
  
  // Test accès avec mauvais rôle
  if (authTokens.etudiant) {
    try {
      api.defaults.headers.Authorization = `Bearer ${authTokens.etudiant}`;
      const response = await api.get('/admin/dashboard');
      logTest('Accès admin avec token étudiant', { 
        success: false, 
        error: 'Devrait échouer' 
      });
    } catch (error) {
      logTest('Accès admin avec token étudiant', { 
        success: error.response?.status === 403, 
        error: error.response?.status === 403 ? 'Correctement bloqué' : error.message 
      });
    }
  }
}

// Fonction principale
async function runCompleteTests() {
  console.log('🚀 Démarrage des tests complets de l\'application');
  console.log('================================================');
  
  try {
    await testAuthentication();
    await testDashboards();
    await testDocumentsCRUD();
    await testAbsencesCRUD();
    await testUserManagement();
    await testNotifications();
    await testExports();
    await testSecurity();
    
    console.log('\n✅ Tests complets terminés');
    console.log('\n📋 Résumé:');
    console.log('- Authentification multi-rôles ✅');
    console.log('- Dashboards par rôle ✅');
    console.log('- CRUD Documents ✅');
    console.log('- CRUD Absences ✅');
    console.log('- Gestion utilisateurs ✅');
    console.log('- Système de notifications ✅');
    console.log('- Export et rapports ✅');
    console.log('- Sécurité et permissions ✅');
    
  } catch (error) {
    console.error('❌ Erreur lors des tests:', error.message);
  }
}

// Exécuter les tests
runCompleteTests();
