#!/usr/bin/env node

/**
 * Test simple pour vérifier les fonctionnalités
 */

const axios = require('axios');

const BASE_URL = 'http://localhost:8000/api';

async function testLogin() {
  try {
    console.log('🔐 Test de connexion...');
    
    const response = await axios.post(`${BASE_URL}/auth/login`, {
      email: 'etudiant@isi.com',
      password: 'password123'
    });
    
    if (response.data.success) {
      console.log('✅ Connexion réussie');
      console.log('Token:', response.data.data.token.substring(0, 20) + '...');
      return response.data.data.token;
    } else {
      console.log('❌ Échec de connexion:', response.data.message);
      return null;
    }
  } catch (error) {
    console.log('❌ Erreur de connexion:', error.response?.data?.message || error.message);
    return null;
  }
}

async function testDashboard(token) {
  try {
    console.log('\n📊 Test du dashboard...');
    
    const response = await axios.get(`${BASE_URL}/etudiant/dashboard`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (response.data.success) {
      console.log('✅ Dashboard accessible');
      console.log('Données:', JSON.stringify(response.data.data, null, 2));
    } else {
      console.log('❌ Dashboard inaccessible:', response.data.message);
    }
  } catch (error) {
    console.log('❌ Erreur dashboard:', error.response?.data?.message || error.message);
  }
}

async function testAbsences(token) {
  try {
    console.log('\n📅 Test des absences...');
    
    const response = await axios.get(`${BASE_URL}/etudiant/absences`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (response.data.success) {
      console.log('✅ Absences accessibles');
      console.log('Nombre d\'absences:', response.data.data.total || 0);
    } else {
      console.log('❌ Absences inaccessibles:', response.data.message);
    }
  } catch (error) {
    console.log('❌ Erreur absences:', error.response?.data?.message || error.message);
  }
}

async function testDocuments(token) {
  try {
    console.log('\n📄 Test des documents...');
    
    const response = await axios.get(`${BASE_URL}/etudiant/documents`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (response.data.success) {
      console.log('✅ Documents accessibles');
      console.log('Nombre de documents:', response.data.data.total || 0);
    } else {
      console.log('❌ Documents inaccessibles:', response.data.message);
    }
  } catch (error) {
    console.log('❌ Erreur documents:', error.response?.data?.message || error.message);
  }
}

async function testDeclareAbsence(token) {
  try {
    console.log('\n📝 Test déclaration d\'absence...');
    
    const response = await axios.post(`${BASE_URL}/etudiant/absences`, {
      date_debut: '2024-01-20',
      date_fin: '2024-01-21',
      motif: 'Test de déclaration d\'absence'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (response.data.success) {
      console.log('✅ Absence déclarée avec succès');
      console.log('ID absence:', response.data.data.id);
    } else {
      console.log('❌ Échec déclaration:', response.data.message);
    }
  } catch (error) {
    console.log('❌ Erreur déclaration:', error.response?.data?.message || error.message);
  }
}

async function runTests() {
  console.log('🚀 Démarrage des tests simples');
  console.log('==============================');
  
  const token = await testLogin();
  
  if (token) {
    await testDashboard(token);
    await testAbsences(token);
    await testDocuments(token);
    await testDeclareAbsence(token);
  }
  
  console.log('\n✅ Tests terminés');
}

runTests().catch(console.error);
