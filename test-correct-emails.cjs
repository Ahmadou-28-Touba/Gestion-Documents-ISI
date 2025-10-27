#!/usr/bin/env node

const axios = require('axios');

const BASE_URL = 'http://localhost:8000/api';

async function testCorrectEmails() {
  console.log('🔐 Test avec les bons emails...');
  
  const users = [
    { role: 'etudiant', email: 'etudiant@isi.com', password: 'password123' },
    { role: 'enseignant', email: 'jean.dupont@isi.com', password: 'password123' },
    { role: 'administrateur', email: 'admin@isi.com', password: 'password123' },
    { role: 'directeur', email: 'directeur@isi.com', password: 'password123' }
  ];
  
  for (const user of users) {
    try {
      console.log(`\n🔐 Test ${user.role} (${user.email})...`);
      
      const loginResponse = await axios.post(`${BASE_URL}/auth/login`, {
        email: user.email,
        password: user.password
      });
      
      if (loginResponse.data.success) {
        console.log(`✅ Connexion ${user.role} réussie`);
        
        const token = loginResponse.data.data.token;
        
        // Test dashboard
        try {
          const dashboardResponse = await axios.get(`${BASE_URL}/${user.role}/dashboard`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (dashboardResponse.data.success) {
            console.log(`✅ Dashboard ${user.role} accessible`);
          }
        } catch (error) {
          console.log(`❌ Erreur dashboard ${user.role}:`, error.response?.data?.message || error.message);
        }
        
      } else {
        console.log(`❌ Échec de connexion ${user.role}:`, loginResponse.data.message);
      }
      
    } catch (error) {
      console.log(`❌ Erreur ${user.role}:`, error.response?.data?.message || error.message);
    }
  }
}

testCorrectEmails().catch(console.error);
