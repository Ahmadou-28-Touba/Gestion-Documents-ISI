<template>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow-lg border-0">
          <div class="card-body p-5">
            <div class="text-center mb-4">
              <i class="fas fa-graduation-cap fa-3x text-primary mb-3"></i>
              <h2 class="card-title">Gestion Documents ISI</h2>
              <p class="text-muted">Connectez-vous à votre espace</p>
            </div>
            
            <form @submit.prevent="login">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                  </span>
                  <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    v-model="form.email"
                    :class="{ 'is-invalid': errors.email }"
                    placeholder="votre.email@isi.com"
                    required
                  >
                </div>
                <div v-if="errors.email" class="invalid-feedback d-block">
                  {{ errors.email[0] }}
                </div>
              </div>
              
              <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                  </span>
                  <input 
                    :type="showPassword ? 'text' : 'password'" 
                    class="form-control" 
                    id="password" 
                    v-model="form.password"
                    :class="{ 'is-invalid': errors.password }"
                    placeholder="Votre mot de passe"
                    required
                  >
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary" 
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <div v-if="errors.password" class="invalid-feedback d-block">
                  {{ errors.password[0] }}
                </div>
              </div>
              
              <div class="d-grid">
                <button 
                  type="submit" 
                  class="btn btn-primary btn-lg"
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="fas fa-sign-in-alt me-2"></i>
                  {{ loading ? 'Connexion...' : 'Se connecter' }}
                </button>
              </div>
            </form>
            
            <!-- Comptes de test -->
            <div class="mt-4">
              <div class="card bg-light">
                <div class="card-header">
                  <h6 class="mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Comptes de test
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-6">
                      <small class="text-muted">Étudiant</small><br>
                      <button @click="fillCredentials('alice.student@isi.com', 'password')" 
                              class="btn btn-sm btn-outline-primary">
                        Alice Student
                      </button>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">Enseignant</small><br>
                      <button @click="fillCredentials('jean.dupont@isi.com', 'password')" 
                              class="btn btn-sm btn-outline-success">
                        Jean Dupont
                      </button>
                    </div>
                  </div>
                  <div class="row mt-2">
                    <div class="col-6">
                      <small class="text-muted">Admin</small><br>
                      <button @click="fillCredentials('admin@isi.com', 'password')" 
                              class="btn btn-sm btn-outline-warning">
                        Admin
                      </button>
                    </div>
                    <div class="col-6">
                      <small class="text-muted">Directeur</small><br>
                      <button @click="fillCredentials('directeur@isi.com', 'password')" 
                              class="btn btn-sm btn-outline-info">
                        Directeur
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'Login',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const showPassword = ref(false);
    const errors = ref({});
    
    const form = reactive({
      email: '',
      password: ''
    });
    
    const login = async () => {
      loading.value = true;
      errors.value = {};
      
      try {
        const response = await axios.post('/auth/login', form);
        
        if (response.data.success) {
          // Stocker le token et les données utilisateur
          localStorage.setItem('auth_token', response.data.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.data.utilisateur));
          // Définir l'en-tête Authorization par défaut
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;
          
          // Rediriger selon le rôle
          const role = response.data.data.utilisateur?.role;
          let target = '/dashboard';
          if (role === 'etudiant') target = '/etudiant-dashboard';
          else if (role === 'enseignant') target = '/enseignant-dashboard';
          else if (role === 'administrateur') target = '/admin/dashboard';
          else if (role === 'directeur') target = '/directeur-dashboard';
          router.push(target);
        }
      } catch (error) {
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors;
        } else {
          alert(error.response?.data?.message || 'Erreur de connexion');
        }
      } finally {
        loading.value = false;
      }
    };
    
    const fillCredentials = (email, password) => {
      form.email = email;
      form.password = password;
    };
    
    return {
      form,
      loading,
      showPassword,
      errors,
      login,
      fillCredentials
    };
  }
};
</script>

<style scoped>
.card {
  border-radius: 15px;
}

.btn {
  border-radius: 10px;
}

.input-group-text {
  background-color: #f8f9fa;
  border-right: none;
}

.form-control {
  border-left: none;
}

.form-control:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.input-group:focus-within .input-group-text {
  border-color: #0d6efd;
}
</style>











