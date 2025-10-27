#!/usr/bin/env node

const axios = require('axios');

const BASE_URL = 'http://localhost:8000/api';

async function testAbsenceDeclaration() {
  try {
    console.log('🔐 Connexion...');
    
    const loginResponse = await axios.post(`${BASE_URL}/auth/login`, {
      email: 'etudiant@isi.com',
      password: 'password123'
    });
    
    if (!loginResponse.data.success) {
      console.log('❌ Échec de connexion');
      return;
    }
    
    const token = loginResponse.data.data.token;
    console.log('✅ Connexion réussie');
    
    console.log('\n📝 Test déclaration d\'absence...');
    
    // Utiliser une date future
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dayAfter = new Date();
    dayAfter.setDate(dayAfter.getDate() + 2);
    
    const absenceResponse = await axios.post(`${BASE_URL}/etudiant/absences`, {
      date_debut: tomorrow.toISOString().split('T')[0],
      date_fin: dayAfter.toISOString().split('T')[0],
      motif: 'Test de déclaration d\'absence'
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    console.log('Réponse complète:', JSON.stringify(absenceResponse.data, null, 2));
    
  } catch (error) {
    console.log('❌ Erreur:', error.response?.data || error.message);
  }
}

testAbsenceDeclaration();
