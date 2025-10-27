<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="fas fa-box-archive me-2"></i> Archives des documents</h1>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form @submit.prevent="archiver">
          <div class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label">Année universitaire</label>
              <input type="number" v-model.number="annee" class="form-control" placeholder="ex: 2024" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Promotion (optionnel)</label>
              <input type="text" v-model="promotion" class="form-control" placeholder="ex: L3, M1" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Filière (optionnel)</label>
              <input type="text" v-model="filiere" class="form-control" placeholder="ex: informatique" />
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-warning" :disabled="submitting">
                <span v-if="!submitting">Archiver</span>
                <span v-else><span class="spinner-border spinner-border-sm me-1"></span>Traitement...</span>
              </button>
              <button type="button" class="btn btn-outline-secondary" @click="rechercher">Rechercher</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header bg-light">Résultats</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Étudiant</th>
                <th>Date</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in resultats" :key="d.id">
                <td>{{ d.id }}</td>
                <td>{{ d.nom || '-' }}</td>
                <td>{{ d.type }}</td>
                <td>{{ d.etudiant?.utilisateur?.nom || '-' }}</td>
                <td>{{ formatDate(d.date_generation) }}</td>
                <td>
                  <span :class="d.est_public ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ d.est_public ? 'Public' : 'Archivé' }}
                  </span>
                </td>
              </tr>
              <tr v-if="!loading && !resultats.length">
                <td colspan="6" class="text-center text-muted">Aucun document</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminArchives',
  data() {
    const currentYear = new Date().getFullYear()
    return {
      annee: currentYear,
      promotion: '',
      filiere: '',
      submitting: false,
      loading: false,
      resultats: []
    }
  },
  methods: {
    async archiver() {
      this.submitting = true
      try {
        await axios.post('admin/documents/archiver', {
          annee: this.annee,
          promotion: this.promotion || null,
          filiere: this.filiere || null
        })
        alert('Archivage lancé/terminé')
        await this.rechercher()
      } catch (e) {
        console.error(e)
        alert(e.response?.data?.message || 'Erreur lors de l\'archivage')
      } finally {
        this.submitting = false
      }
    },
    async rechercher() {
      this.loading = true
      try {
        const params = new URLSearchParams()
        if (this.annee) params.append('annee', this.annee)
        if (this.promotion) params.append('promotion', this.promotion)
        if (this.filiere) params.append('filiere', this.filiere)
        // Convention actuelle: archives = est_public = false
        params.append('est_public', '0')
        const res = await axios.get(`admin/documents/rechercher?${params.toString()}`)
        this.resultats = res.data?.data || []
      } catch (e) {
        console.error(e)
      } finally {
        this.loading = false
      }
    },
    formatDate(d) {
      return d ? new Date(d).toLocaleString('fr-FR') : '-'
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.rechercher()
  }
}
</script>
