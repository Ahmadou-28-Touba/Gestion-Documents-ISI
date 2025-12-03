<template>
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="fas fa-clipboard-list me-2"></i>
          Mes notes
        </h2>
        <button class="btn btn-outline-secondary" @click="$router.push({ name: 'EtudiantDashboard' })">
          <i class="fas fa-arrow-left me-1"></i>
          Retour au tableau de bord
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Récapitulatif par semestre</h5>
        <button class="btn btn-sm btn-outline-primary" @click="chargerNotes">
          <i class="fas fa-sync-alt me-1"></i>
          Actualiser
        </button>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>

        <div v-else-if="groupesParPeriode.length === 0" class="text-center py-4">
          <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
          <p class="text-muted mb-1">Aucune note disponible pour le moment.</p>
          <p class="text-muted small">Les notes apparaîtront ici dès qu'elles seront saisies par vos enseignants.</p>
        </div>

        <div v-else class="row">
          <div
            class="col-md-6 mb-4"
            v-for="groupe in groupesParPeriode"
            :key="groupe.periode || 'sans-periode'"
          >
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                  <i class="fas fa-calendar-alt me-2"></i>
                  {{ groupe.periode || 'Période non renseignée' }}
                </h5>
                <span class="badge bg-primary fs-6">
                  {{ groupe.moyenne.toFixed(2) }}/20
                </span>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Matière</th>
                        <th>Type</th>
                        <th>Note</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="note in groupe.notes" :key="note.id">
                        <td>{{ note.matiere || '-' }}</td>
                        <td>{{ note.type_controle || '-' }}</td>
                        <td>
                          <span class="badge" :class="getNoteBadgeClass(note.valeur)">
                            {{ Number(note.valeur).toFixed(2) }}/20
                          </span>
                        </td>
                        <td>{{ formatDate(note.date) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="card-footer text-end">
                <small class="text-muted">
                  {{ groupe.notes.length }} note(s) • Moyenne du semestre :
                  <strong>{{ groupe.moyenne.toFixed(2) }}/20</strong>
                </small>
              </div>
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
  name: 'EtudiantNotes',
  data() {
    return {
      notes: [],
      loading: false
    }
  },
  computed: {
    groupesParPeriode() {
      if (!this.notes || this.notes.length === 0) return []

      const groupes = {}
      this.notes.forEach(n => {
        const periode = n.periode || 'Autres'
        if (!groupes[periode]) {
          groupes[periode] = {
            periode,
            notes: [],
            somme: 0,
            count: 0
          }
        }
        const valeur = Number(n.valeur || 0)
        groupes[periode].notes.push(n)
        groupes[periode].somme += valeur
        groupes[periode].count += 1
      })

      return Object.values(groupes).map(g => ({
        periode: g.periode,
        notes: g.notes,
        moyenne: g.count > 0 ? g.somme / g.count : 0
      }))
    }
  },
  mounted() {
    this.chargerNotes()
  },
  methods: {
    async chargerNotes() {
      this.loading = true
      try {
        const res = await axios.get('etudiant/notes')
        this.notes = res.data?.data || []
      } catch (error) {
        console.error('Erreur chargement notes étudiant:', error)
        const r = error.response
        const msg = r?.data?.message || error.message || 'Erreur lors du chargement des notes'
        this.$toast?.error?.(msg)
      } finally {
        this.loading = false
      }
    },
    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    getNoteBadgeClass(note) {
      const value = Number(note || 0)
      if (value >= 16) return 'bg-success'
      if (value >= 10) return 'bg-primary'
      if (value >= 8) return 'bg-warning'
      return 'bg-danger'
    }
  }
}
</script>

<style scoped>
.card-header .badge {
  font-size: 0.95rem;
}

.table td {
  vertical-align: middle;
}
</style>
