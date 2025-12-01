<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="fas fa-chart-bar me-2"></i> Rapports / Statistiques</h1>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Utilisateurs</div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-4">
                <div class="h5 mb-0">{{ stats.utilisateurs.total || 0 }}</div>
                <div class="text-muted small">Total</div>
              </div>
              <div class="col-4">
                <div class="h5 mb-0">{{ stats.utilisateurs.nouveaux_ce_mois || 0 }}</div>
                <div class="text-muted small">Nouveaux (mois)</div>
              </div>
              <div class="col-4">
                <div class="h5 mb-0">{{ (stats.utilisateurs.par_role?.etudiant) || 0 }}</div>
                <div class="text-muted small">Étudiants</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6" id="documents-section" v-if="isVisible('documents')">
        <div class="card shadow-sm h-100 focusable" :class="{ 'focus-ring': highlight === 'documents' }">
          <div class="card-header bg-light">Documents</div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.documents.total || 0 }}</div>
                <div class="text-muted small">Total</div>
              </div>
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.documents.publics || 0 }}</div>
                <div class="text-muted small">Publics</div>
              </div>
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.documents.archives || 0 }}</div>
                <div class="text-muted small">Archives</div>
              </div>
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.documents.ce_mois || 0 }}</div>
                <div class="text-muted small">Ce mois</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6" id="absences-section" v-if="isVisible('absences')">
        <div class="card shadow-sm h-100 focusable" :class="{ 'focus-ring': highlight === 'absences' }">
          <div class="card-header bg-light">Absences</div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.absences.total || 0 }}</div>
                <div class="text-muted small">Total</div>
              </div>
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.absences.en_attente || 0 }}</div>
                <div class="text-muted small">En attente</div>
              </div>
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.absences.validees || 0 }}</div>
                <div class="text-muted small">Validées</div>
              </div>
              <div class="col-3">
                <div class="h5 mb-0">{{ stats.absences.refusees || 0 }}</div>
                <div class="text-muted small">Refusées</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Modèles</div>
          <div class="card-body">
            <div class="row text-center">
              <div class="col-4">
                <div class="h5 mb-0">{{ stats.modeles.total || 0 }}</div>
                <div class="text-muted small">Total</div>
              </div>
              <div class="col-4">
                <div class="h5 mb-0">{{ stats.modeles.actifs || 0 }}</div>
                <div class="text-muted small">Actifs</div>
              </div>
              <div class="col-4">
                <div class="h5 mb-0">{{ stats.modeles.inactifs || 0 }}</div>
                <div class="text-muted small">Inactifs</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Documents récents</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Étudiant</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in recent.documents_recents" :key="d.id">
                    <td>{{ d.nom || 'Document' }}</td>
                    <td>{{ d.type }}</td>
                    <td>{{ d.etudiant?.utilisateur?.nom || '-' }}</td>
                    <td>{{ formatDate(d.date_generation) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Absences récentes</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Étudiant</th>
                    <th>Enseignant</th>
                    <th>Début</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in recent.absences_recentes" :key="a.id">
                    <td>{{ a.etudiant?.utilisateur?.nom }}</td>
                    <td>{{ a.enseignant?.utilisateur?.nom }}</td>
                    <td>{{ formatDate(a.date_declaration) }}</td>
                    <td>
                      <span class="badge" :class="badgeFor(a.statut)">{{ a.statut }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
</template>

<script>
import axios from 'axios'

export default {
  name: 'DirecteurStatistiques',
  data() {
    return {
      stats: { utilisateurs: { par_role: {} }, documents: {}, absences: {}, modeles: {} },
      recent: { documents_recents: [], absences_recentes: [] },
      loading: false,
      highlight: null
    }
  },
  methods: {
    async load() {
      this.loading = true
      try {
        const [s, d] = await Promise.all([
          axios.get('directeur/statistiques'),
          axios.get('directeur/dashboard')
        ])
        this.stats = s.data?.data || this.stats
        this.recent = d.data?.data || this.recent
      } catch (e) {
        console.error(e)
      } finally {
        this.loading = false
      }
    },
    isVisible(section) {
      const focus = this.$route?.query?.focus
      if (!focus) return true
      return focus === section
    },
    applyFocus() {
      const focus = this.$route?.query?.focus
      this.highlight = null
      if (focus === 'documents') {
        this.highlight = 'documents'
        document.getElementById('documents-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      } else if (focus === 'absences') {
        this.highlight = 'absences'
        document.getElementById('absences-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
      if (this.highlight) {
        setTimeout(() => { this.highlight = null }, 1500)
      }
    },
    formatDate(d) {
      return d ? new Date(d).toLocaleString('fr-FR') : '-'
    },
    badgeFor(statut) {
      const map = { en_attente: 'bg-warning', validee: 'bg-success', refusee: 'bg-danger' }
      return map[statut] || 'bg-secondary'
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.load().then(() => this.applyFocus())
  },
  watch: {
    '$route.query.focus'() {
      this.applyFocus()
    }
  }
}
</script>

<style scoped>
.focus-ring { box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); transition: box-shadow .3s ease }
</style>













