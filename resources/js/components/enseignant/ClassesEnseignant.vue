<template>
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="fas fa-users me-2"></i>
          Classes gérées
        </h2>
        <router-link to="/enseignant-dashboard" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-arrow-left me-1"></i>
          Retour au tableau de bord
        </router-link>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div v-if="classes && classes.length">
              <span
                v-for="c in classes"
                :key="c.id"
                class="badge bg-secondary me-2 mb-2"
              >
                {{ c.label || (c.filiere + ' ' + c.annee + (c.groupe ? ' (' + c.groupe + ')' : '')) }}
              </span>
            </div>
            <div v-else class="text-muted">Aucune classe rattachée</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ClassesEnseignant',
  data() {
    return {
      classes: []
    }
  },
  mounted() {
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.fetchClasses()
  },
  methods: {
    async fetchClasses() {
      try {
        const res = await axios.get('enseignant/classes')
        this.classes = res.data?.data || []
      } catch (e) {
        console.error('Erreur chargement classes:', e)
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
