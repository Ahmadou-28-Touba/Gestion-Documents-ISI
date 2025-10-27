#!/usr/bin/env node

const axios = require('axios');

const BASE_URL = 'http://localhost:8000/api';

async function testRole(role, email, password) {
  console.log(`\n🔐 Test du rôle ${role}...`);
  
  try {
    // Connexion
    const loginResponse = await axios.post(`${BASE_URL}/auth/login`, {
      email: email,
      password: password
    });
    
    if (!loginResponse.data.success) {
      console.log(`❌ Échec de connexion pour ${role}`);
      return false;
    }
    
    const token = loginResponse.data.data.token;
    console.log(`✅ Connexion ${role} réussie`);
    
    // Test dashboard
    try {
      const dashboardUrl = role === 'administrateur' ? `${BASE_URL}/admin/dashboard` : `${BASE_URL}/${role}/dashboard`;
      const dashboardResponse = await axios.get(dashboardUrl, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (dashboardResponse.data.success) {
        console.log(`✅ Dashboard ${role} accessible`);
      } else {
        console.log(`❌ Dashboard ${role} inaccessible`);
      }
    } catch (error) {
      console.log(`❌ Erreur dashboard ${role}:`, error.response?.data?.message || error.message);
    }
    
    // Test spécifique selon le rôle
    if (role === 'etudiant') {
      // Test absences
      try {
        const absencesResponse = await axios.get(`${BASE_URL}/etudiant/absences`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        console.log(`✅ Absences accessibles (${absencesResponse.data.data?.total || 0} absences)`);
      } catch (error) {
        console.log(`❌ Erreur absences:`, error.response?.data?.message || error.message);
      }
      
      // Test documents
      try {
        const documentsResponse = await axios.get(`${BASE_URL}/etudiant/documents`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        console.log(`✅ Documents accessibles (${documentsResponse.data.data?.total || 0} documents)`);
      } catch (error) {
        console.log(`❌ Erreur documents:`, error.response?.data?.message || error.message);
      }
    }
    
    if (role === 'enseignant') {
      // Test absences en attente
      try {
        const absencesResponse = await axios.get(`${BASE_URL}/enseignant/absences/en-attente`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        console.log(`✅ Absences en attente accessibles (${absencesResponse.data.data?.length || 0} absences)`);
      } catch (error) {
        console.log(`❌ Erreur absences en attente:`, error.response?.data?.message || error.message);
      }
    }
    
    if (role === 'administrateur') {
      // Test gestion utilisateurs
      try {
        const usersResponse = await axios.get(`${BASE_URL}/admin/utilisateurs`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        console.log(`✅ Gestion utilisateurs accessible (${usersResponse.data.data?.total || 0} utilisateurs)`);
      } catch (error) {
        console.log(`❌ Erreur gestion utilisateurs:`, error.response?.data?.message || error.message);
      }
      
      // Test gestion documents
      try {
        const documentsResponse = await axios.get(`${BASE_URL}/admin/documents`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        console.log(`✅ Gestion documents accessible (${documentsResponse.data.data?.total || 0} documents)`);
      } catch (error) {
        console.log(`❌ Erreur gestion documents:`, error.response?.data?.message || error.message);
      }
    }
    
    if (role === 'directeur') {
      // Test statistiques
      try {
        const statsResponse = await axios.get(`${BASE_URL}/directeur/statistiques`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        console.log(`✅ Statistiques accessibles`);
      } catch (error) {
        console.log(`❌ Erreur statistiques:`, error.response?.data?.message || error.message);
      }
    }
    
    return true;
    
  } catch (error) {
    console.log(`❌ Erreur générale pour ${role}:`, error.message);
    return false;
  }
}

async function testAllFeatures() {
  console.log('🚀 Test de toutes les fonctionnalités');
  console.log('=====================================');
  
  const roles = [
    { role: 'etudiant', email: 'etudiant@isi.com', password: 'password123' },
    { role: 'enseignant', email: 'enseignant@isi.com', password: 'password123' },
    { role: 'administrateur', email: 'admin@isi.com', password: 'password123' },
    { role: 'directeur', email: 'directeur@isi.com', password: 'password123' }
  ];
  
  let successCount = 0;
  
  for (const roleData of roles) {
    const success = await testRole(roleData.role, roleData.email, roleData.password);
    if (success) successCount++;
  }
  
  console.log(`\n📊 Résumé: ${successCount}/${roles.length} rôles fonctionnels`);
  
  if (successCount === roles.length) {
    console.log('🎉 Toutes les fonctionnalités sont opérationnelles !');
  } else {
    console.log('⚠️  Certaines fonctionnalités nécessitent des corrections');
  }
}

testAllFeatures().catch(console.error);
