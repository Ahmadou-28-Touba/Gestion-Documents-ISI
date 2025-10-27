<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow">
          <div class="card-header">
            <h4 class="mb-0">
              <i class="fas fa-user-edit me-2"></i>
              Mon Profil
            </h4>
          </div>
          <div class="card-body">
            <form @submit.prevent="updateProfile">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="nom" class="form-label">Nom</label>
                  <input type="text" class="form-control" id="nom" v-model="form.nom" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="prenom" class="form-label">Prénom</label>
                  <input type="text" class="form-control" id="prenom" v-model="form.prenom" required>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" v-model="form.email" required>
              </div>
              
              <!-- Champs spécifiques selon le rôle -->
              <template v-if="user?.role === 'etudiant'">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="filiere" class="form-label">Filière</label>
                    <input type="text" class="form-control" id="filiere" v-model="form.filiere">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="annee" class="form-label">Année</label>
                    <input type="text" class="form-control" id="annee" v-model="form.annee">
                  </div>
                </div>
              </template>
              
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" @click="goBack">
                  <i class="fas fa-arrow-left me-1"></i>
                  Retour
                </button>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="fas fa-save me-1"></i>
                  {{ loading ? 'Sauvegarde...' : 'Sauvegarder' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'Profil',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
    
    const form = reactive({
      nom: user.value.nom || '',
      prenom: user.value.prenom || '',
      email: user.value.email || '',
      filiere: user.value.etudiant?.filiere || '',
      annee: user.value.etudiant?.annee || ''
    });
    
    const updateProfile = async () => {
      loading.value = true;
      
      try {
        const role = user.value.role;
        let endpoint = '';
        
        switch (role) {
          case 'etudiant':
            endpoint = '/etudiant/profil';
            break;
          case 'enseignant':
            endpoint = '/enseignant/profil';
            break;
          case 'administrateur':
            endpoint = '/admin/profil';
            break;
          case 'directeur':
            endpoint = '/directeur/profil';
            break;
        }
        
        if (endpoint) {
          const response = await axios.put(endpoint, form);
          if (response.data.success) {
            // Mettre à jour les données utilisateur
            const updatedUser = response.data.data.utilisateur || response.data.data;
            localStorage.setItem('user', JSON.stringify(updatedUser));
            user.value = updatedUser;
            
            alert('Profil mis à jour avec succès !');
          }
        }
      } catch (error) {
        alert('Erreur lors de la mise à jour du profil');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };
    
    const goBack = () => {
      router.go(-1);
    };
    
    return {
      user,
      form,
      loading,
      updateProfile,
      goBack
    };
  }
};
</script>











