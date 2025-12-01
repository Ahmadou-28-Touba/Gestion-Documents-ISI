<template>
  <div class="login-wrapper min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container px-3 px-md-0">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card login-card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
              <div class="text-center mb-4">
                <div class="brand mb-2">
                  <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                </div>
                <h2 class="card-title h3 fw-bold mb-1">Gestion Documents ISI</h2>
                <p class="text-muted mb-0">Connectez-vous à votre espace</p>
              </div>

              <form @submit.prevent="login" novalidate>
                <div class="mb-3">
                  <label for="email" class="form-label">Adresse e-mail</label>
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

                <div class="mb-2">
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
                      :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                  <div v-if="errors.password" class="invalid-feedback d-block">
                    {{ errors.password[0] }}
                  </div>
                </div>

                <div class="d-flex justify-content-center mb-4">
                  <button type="button" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fas fa-key me-1"></i>
                    Mot de passe oublié ?
                  </button>
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


              <div class="text-center mt-3 small text-muted">
                © {{ new Date().getFullYear() }} Institut Supérieur d'Informatique
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
    
    
    return {
      form,
      loading,
      showPassword,
      errors,
      login,
    };
  }
};
</script>

<style scoped>
.login-wrapper {
  background: linear-gradient(135deg, #0d6efd, #6f42c1);
}

.login-card {
  border-radius: 16px;
  backdrop-filter: saturate(140%) blur(2px);
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
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.input-group:focus-within .input-group-text {
  border-color: #0d6efd;
}
</style>













