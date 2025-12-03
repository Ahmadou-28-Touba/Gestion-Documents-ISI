<template>
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="fas fa-tasks me-2"></i>
          Absences à traiter
        </h2>
        <button class="btn btn-outline-secondary" @click="$router.push({ name: 'DirecteurDashboard' })">
          <i class="fas fa-arrow-left me-1"></i>
          Retour au tableau de bord
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Absences en attente de décision</h5>
        <small class="text-muted" v-if="absences.length">
          {{ absences.length }} absence(s) en attente
        </small>
      </div>
      <div class="card-body">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>

        <div v-else-if="absences.length === 0" class="text-center py-4">
          <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
          <p class="text-muted">Aucune absence en attente de traitement.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Étudiant</th>
                <th>Période</th>
                <th>Motif</th>
                <th>Date déclaration</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="absence in absences" :key="absence.id">
                <td>
                  <strong>{{ absence.etudiant?.utilisateur?.nom }} {{ absence.etudiant?.utilisateur?.prenom }}</strong>
                  <br>
                  <small class="text-muted">{{ absence.etudiant?.numero_etudiant }}</small>
                </td>
                <td>
                  {{ formatDate(absence.date_debut) }} - {{ formatDate(absence.date_fin) }}
                  <br>
                  <small class="text-muted">{{ absence.duree_en_jours }} jour(s)</small>
                </td>
                <td>
                  <span class="text-truncate d-inline-block" style="max-width: 220px;" :title="absence.motif">
                    {{ absence.motif }}
                  </span>
                </td>
                <td>{{ formatDate(absence.date_declaration) }}</td>
                <td class="text-center">
                  <div class="btn-group" role="group">
                    <button
                      class="btn btn-sm btn-outline-success"
                      @click="validerAbsence(absence)"
                      title="Valider"
                    >
                      <i class="fas fa-check"></i>
                    </button>
                    <button
                      class="btn btn-sm btn-outline-danger"
                      @click="ouvrirRefus(absence)"
                      title="Rejeter avec motif"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal de rejet -->
    <div class="modal fade" :class="{ show: showRejectModal }" :style="{ display: showRejectModal ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Rejeter l'absence</h5>
            <button type="button" class="close" @click="fermerRefus">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>
              <strong>{{ absenceSelectionnee?.etudiant?.utilisateur?.nom }} {{ absenceSelectionnee?.etudiant?.utilisateur?.prenom }}</strong>
              <br>
              <small class="text-muted">
                {{ formatDate(absenceSelectionnee?.date_debut) }} - {{ formatDate(absenceSelectionnee?.date_fin) }}
              </small>
            </p>
            <div class="form-group">
              <label>Motif du refus *</label>
              <textarea
                class="form-control"
                rows="3"
                v-model="motifRefus"
                placeholder="Expliquez la raison du refus..."
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="fermerRefus">Annuler</button>
            <button type="button" class="btn btn-danger" @click="confirmerRefus">Rejeter</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'DirecteurAbsencesATraiter',
  data() {
    return {
      absences: [],
      loading: false,
      showRejectModal: false,
      absenceSelectionnee: null,
      motifRefus: ''
    }
  },
  mounted() {
    this.chargerAbsencesEnAttente()
  },
  methods: {
    async chargerAbsencesEnAttente() {
      this.loading = true
      try {
        const response = await axios.get('absences', {
          params: { statut: 'en_attente' }
        })
        if (response.data && response.data.data) {
          const pagination = response.data.data
          this.absences = pagination.data || []
        } else {
          this.absences = []
        }
      } catch (error) {
        console.error('Erreur lors du chargement des absences en attente:', error)
        this.$toast?.error && this.$toast.error('Erreur lors du chargement des absences en attente')
      } finally {
        this.loading = false
      }
    },

    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('fr-FR')
    },

    async validerAbsence(absence) {
      if (!absence) return
      if (!confirm('Valider cette absence ?')) return
      try {
        await axios.post(`absences/${absence.id}/valider`)
        this.$toast?.success && this.$toast.success('Absence validée avec succès')
        await this.chargerAbsencesEnAttente()
      } catch (error) {
        console.error('Erreur lors de la validation:', error)
        this.$toast?.error && this.$toast.error('Erreur lors de la validation')
      }
    },

    ouvrirRefus(absence) {
      this.absenceSelectionnee = absence
      this.motifRefus = ''
      this.showRejectModal = true
    },

    fermerRefus() {
      this.showRejectModal = false
      this.absenceSelectionnee = null
      this.motifRefus = ''
    },

    async confirmerRefus() {
      if (!this.absenceSelectionnee) return
      if (!this.motifRefus.trim()) {
        this.$toast?.error && this.$toast.error('Le motif de refus est obligatoire')
        return
      }
      try {
        await axios.post(`absences/${this.absenceSelectionnee.id}/rejeter`, {
          motif_refus: this.motifRefus
        })
        this.$toast?.success && this.$toast.success('Absence rejetée avec succès')
        this.fermerRefus()
        await this.chargerAbsencesEnAttente()
      } catch (error) {
        console.error('Erreur lors du rejet:', error)
        this.$toast?.error && this.$toast.error('Erreur lors du rejet')
      }
    }
  }
}
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.table td {
  vertical-align: middle;
}
</style>
