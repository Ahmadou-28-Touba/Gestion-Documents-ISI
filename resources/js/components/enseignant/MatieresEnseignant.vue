<template>
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="fas fa-book me-2"></i>
          Matières enseignées
        </h2>
        <router-link to="/enseignant-dashboard" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-arrow-left me-1"></i>
          Retour au tableau de bord
        </router-link>
      </div>
    </div>

    <div class="row mb-4" v-if="enseignant">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div v-if="Array.isArray(enseignant.matieres_enseignees) && enseignant.matieres_enseignees.length">
              <span
                v-for="(m, idx) in enseignant.matieres_enseignees"
                :key="idx"
                class="badge bg-primary me-2 mb-2"
              >
                {{ m }}
              </span>
            </div>
            <div v-else class="text-muted">Aucune matière renseignée</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-muted">Chargement des informations...</div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'MatieresEnseignant',
  data() {
    return {
      enseignant: null
    }
  },
  mounted() {
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.loadEnseignant()
  },
  methods: {
    async loadEnseignant() {
      try {
        const response = await axios.get('enseignant/dashboard')
        const data = response.data.data
        this.enseignant = data.enseignant
      } catch (e) {
        console.error('Erreur chargement enseignant:', e)
      }
    }
  }
}
</script>

<style scoped>
.badge {
  font-size: 0.9em;
}
</style>
