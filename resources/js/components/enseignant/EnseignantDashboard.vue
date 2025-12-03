<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="fas fa-chalkboard-teacher me-2"></i>
          Tableau de bord Enseignant
        </h2>
      </div>
    </div>

    <!-- Informations enseignant -->
    <div class="row mb-4" v-if="enseignant">
      <div class="col-12">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="mb-1">{{ enseignant.utilisateur?.nom }} {{ enseignant.utilisateur?.prenom }}</h4>
                <p class="mb-0">
                  <i class="fas fa-id-badge me-2"></i>
                  {{ enseignant.matricule }} | {{ enseignant.departement }}
                </p>
                <p class="mb-0">
                  <i class="fas fa-door-open me-2"></i>
                  Bureau: {{ enseignant.bureau }}
                </p>
              </div>
              <div class="col-md-4 text-end">
                <button class="btn btn-light" @click="showProfilModal = true">
                  <i class="fas fa-user-edit me-1"></i>
                  Modifier le profil
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistiques (désactivées car la gestion des absences est passée au directeur) -->
    <div class="row mb-4" v-if="false && statistiques">
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_en_attente || 0 }}</h4>
            <p class="mb-0">En attente</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_validees_par_moi || 0 }}</h4>
            <p class="mb-0">Absences validées (historique)</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-danger text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_refusees_par_moi || 0 }}</h4>
            <p class="mb-0">Absences refusées (historique)</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_traitees_par_moi || 0 }}</h4>
            <p class="mb-0">Absences traitées (historique)</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-bolt me-2"></i>
              Actions rapides
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 mb-3">
                <router-link to="/enseignant-emploi-du-temps" class="btn btn-outline-dark w-100">
                  <i class="fas fa-calendar-alt me-2"></i>
                  Consulter emplois du temps
                </router-link>
              </div>
              <div class="col-md-3 mb-3">
                <router-link to="/enseignant/notes" class="btn btn-outline-primary w-100">
                  <i class="fas fa-pen me-2"></i>
                  Gestion des notes
                </router-link>
              </div>
              <div class="col-md-3 mb-3">
                <router-link to="/enseignant/classes" class="btn btn-outline-secondary w-100">
                  <i class="fas fa-users me-2"></i>
                  Classes gérées
                </router-link>
              </div>
              <div class="col-md-3 mb-3">
                <router-link to="/enseignant/matieres" class="btn btn-outline-secondary w-100">
                  <i class="fas fa-book me-2"></i>
                  Matières enseignées
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Absences en attente -->
    <div class="row mb-4" v-if="false">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Absences en attente de validation
            </h5>
            <router-link to="/absences-validation" class="btn btn-sm btn-warning">
              Voir tout
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="absencesEnAttente && absencesEnAttente.length > 0">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Étudiant</th>
                      <th>Période</th>
                      <th>Motif</th>
                      <th>Date déclaration</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="absence in absencesEnAttente" :key="absence.id">
                      <td>
                        <strong>{{ absence.etudiant?.utilisateur?.nom }} {{ absence.etudiant?.utilisateur?.prenom }}</strong>
                        <br>
                        <small class="text-muted">{{ absence.etudiant?.numero_etudiant }}</small>
                      </td>
                      <td>
                        {{ formatDate(absence.date_debut) }} - {{ formatDate(absence.date_fin) }}
                      </td>
                      <td>
                        <span
                          class="text-truncate d-inline-block"
                          style="max-width: 200px;"
                          :title="absence.motif"
                        >
                          {{ absence.motif }}
                        </span>
                      </td>
                      <td>{{ formatDate(absence.date_declaration) }}</td>
                      <td>
                        <div class="btn-group" role="group">
                          <button
                            class="btn btn-sm btn-success"
                            @click="validerAbsence(absence.id)"
                            title="Valider"
                          >
                            <i class="fas fa-check"></i>
                          </button>
                          <button
                            class="btn btn-sm btn-danger"
                            @click="showRefusModal(absence)"
                            title="Refuser"
                          >
                            <i class="fas fa-times"></i>
                          </button>
                          <button
                            class="btn btn-sm btn-info"
                            @click="voirDetails(absence)"
                            title="Détails"
                          >
                            <i class="fas fa-eye"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucune absence en attente
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Absences traitées récemment -->
    <div class="row mb-4" v-if="false">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-history me-2"></i>
              Absences traitées récemment
            </h5>
            <router-link to="/absences-list" class="btn btn-sm btn-info">
              Voir tout
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="absencesTraitees && absencesTraitees.length > 0">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Étudiant</th>
                      <th>Période</th>
                      <th>Statut</th>
                      <th>Date traitement</th>
                      <th>Commentaire</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="absence in absencesTraitees" :key="absence.id">
                      <td>
                        <strong>{{ absence.etudiant?.utilisateur?.nom }} {{ absence.etudiant?.utilisateur?.prenom }}</strong>
                        <br>
                        <small class="text-muted">{{ absence.etudiant?.numero_etudiant }}</small>
                      </td>
                      <td>
                        {{ formatDate(absence.date_debut) }} - {{ formatDate(absence.date_fin) }}
                      </td>
                      <td>
                        <span class="badge" :class="getStatutBadgeClass(absence.statut)">
                          {{ getStatutLabel(absence.statut) }}
                        </span>
                      </td>
                      <td>{{ formatDate(absence.date_traitement) }}</td>
                      <td>
                        <span
                          v-if="absence.commentaire_enseignant"
                          class="text-truncate d-inline-block"
                          style="max-width: 150px;"
                          :title="absence.commentaire_enseignant"
                        >
                          {{ absence.commentaire_enseignant }}
                        </span>
                        <span v-else class="text-muted">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucune absence traitée récemment
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal refus d'absence -->
    <div
      class="modal fade"
      :class="{ show: showRefusModalFlag }"
      :style="{ display: showRefusModalFlag ? 'block' : 'none' }"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Refuser l'absence</h5>
            <button type="button" class="btn-close" @click="showRefusModalFlag = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedAbsence">
              <p>
                <strong>Étudiant:</strong>
                {{ selectedAbsence.etudiant?.utilisateur?.nom }}
                {{ selectedAbsence.etudiant?.utilisateur?.prenom }}
              </p>
              <p>
                <strong>Période:</strong>
                {{ formatDate(selectedAbsence.date_debut) }} -
                {{ formatDate(selectedAbsence.date_fin) }}
              </p>
              <p><strong>Motif:</strong> {{ selectedAbsence.motif }}</p>

              <div class="form-group mt-3">
                <label>Motif du refus *</label>
                <textarea
                  class="form-control"
                  rows="3"
                  v-model="motifRefus"
                  required
                  placeholder="Expliquez pourquoi cette absence est refusée..."
                ></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showRefusModalFlag = false">
              Annuler
            </button>
            <button type="button" class="btn btn-danger" @click="refuserAbsence">
              Confirmer le refus
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal profil -->
    <div
      class="modal fade"
      :class="{ show: showProfilModal }"
      :style="{ display: showProfilModal ? 'block' : 'none' }"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modifier le profil</h5>
            <button type="button" class="btn-close" @click="showProfilModal = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="modifierProfil">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label>Nom</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="profilForm.nom"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label>Prénom</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="profilForm.prenom"
                    >
                  </div>
                </div>
              </div>

              <div class="form-group mb-3">
                <label>Email</label>
                <input
                  type="email"
                  class="form-control"
                  v-model="profilForm.email"
                >
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label>Matricule</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="profilForm.matricule"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label>Département</label>
                    <input
                      type="text"
                      class="form-control"
                      v-model="profilForm.departement"
                    >
                  </div>
                </div>
              </div>

              <div class="form-group mb-3">
                <label>Bureau</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="profilForm.bureau"
                >
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showProfilModal = false">
              Annuler
            </button>
            <button type="button" class="btn btn-primary" @click="modifierProfil">
              Sauvegarder
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EnseignantDashboard',
  data() {
    return {
      enseignant: null,
      statistiques: null,
      absencesEnAttente: [],
      absencesTraitees: [],
      notes: [],
      etudiants: [],

      showProfilModal: false,
      showRefusModalFlag: false,
      selectedAbsence: null,
      motifRefus: '',
      // Classes (lecture seule)
      classes: [],
      profilForm: {
        nom: '',
        prenom: '',
        email: '',
        matricule: '',
        departement: '',
        bureau: ''
      },
      noteForm: {
        etudiant_id: '',
        matiere: '',
        type_controle: '',
        valeur: null,
        date: new Date().toISOString().split('T')[0],
        periode: '',
        commentaire: ''
      }
    }
  },
  mounted() {
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.loadDashboard()
    this.fetchClasses()
    this.loadNotes()
    this.fetchEtudiants()
  },
  methods: {
    scrollToSection(id) {
      try {
        const el = document.getElementById(id)
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      } catch (e) {
        console.error('Erreur scrollToSection:', e)
      }
    },

    async loadDashboard() {
      try {
        const response = await axios.get('enseignant/dashboard')
        const data = response.data.data
        
        this.enseignant = data.enseignant
        this.statistiques = data.statistiques
        this.absencesEnAttente = data.absences_en_attente || []
        this.absencesTraitees = data.absences_traitees || []
        
        // Initialiser le formulaire de profil
        if (this.enseignant) {
          this.profilForm = {
            nom: this.enseignant.utilisateur?.nom || '',
            prenom: this.enseignant.utilisateur?.prenom || '',
            email: this.enseignant.utilisateur?.email || '',
            matricule: this.enseignant.matricule || '',
            departement: this.enseignant.departement || '',
            bureau: this.enseignant.bureau || ''
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement du dashboard:', error)
        this.$toast.error('Erreur lors du chargement du dashboard')
      }
    },

    async loadNotes() {
      try {
        const res = await axios.get('enseignant/notes')
        this.notes = res.data?.data || []
      } catch (e) {
        console.error('Erreur chargement notes:', e)
        this.$toast?.error?.('Erreur lors du chargement des notes')
      }
    },

    async submitNote() {
      try {
        const payload = { ...this.noteForm }
        // Nettoyage simple
        if (!payload.matiere) delete payload.matiere
        if (!payload.periode) delete payload.periode
        if (!payload.commentaire) delete payload.commentaire

        await axios.post('enseignant/notes', payload)
        this.$toast?.success?.('Note enregistrée avec succès')
        this.loadNotes()

        this.noteForm = {
          etudiant_id: '',
          matiere: this.noteForm.matiere,
          type_controle: this.noteForm.type_controle,
          valeur: null,
          date: new Date().toISOString().split('T')[0],
          periode: this.noteForm.periode,
          commentaire: ''
        }
      } catch (error) {
        console.error('Erreur lors de la saisie de la note:', error)
        const r = error.response
        if (r?.status === 422 && r?.data?.errors) {
          const msgs = Object.values(r.data.errors).flat().join('\n')
          this.$toast?.error?.(`Validation échouée:\n${msgs}`)
        } else {
          const msg = r?.data?.message || error.message || 'Erreur lors de l\'enregistrement de la note'
          this.$toast?.error?.(msg)
        }
      }
    },

    async fetchClasses() {
      try {
        const res = await axios.get('enseignant/classes')
        this.classes = res.data?.data || []
      } catch (e) {
        console.error('Erreur chargement classes:', e)
      }
    },

    async fetchEtudiants() {
      try {
        const res = await axios.get('enseignant/etudiants')
        // On s'attend à un tableau d'étudiants avec relations utilisateur
        this.etudiants = res.data?.data || res.data || []
      } catch (e) {
        console.error('Erreur chargement étudiants:', e)
      }
    },
    
    async loadAbsencesEnAttente() {
      try {
        const response = await axios.get('enseignant/absences/en-attente')
        this.absencesEnAttente = response.data.data
        this.$toast?.success?.('Absences actualisées')
      } catch (error) {
        console.error('Erreur lors du chargement des absences:', error)
        const r = error.response
        this.$toast?.error?.(`Erreur chargement absences (HTTP ${r?.status || 'NA'})`)
      }
    },
    
    async validerAbsence(absenceId) {
      if (confirm('Êtes-vous sûr de vouloir valider cette absence ?')) {
        try {
          await axios.post(`enseignant/absences/${absenceId}/valider`, {
            action: 'valider'
          })
          
          this.$toast?.success?.('Absence validée avec succès')
          this.loadDashboard()
        } catch (error) {
          console.error('Erreur lors de la validation:', error)
          const r = error.response
          this.$toast?.error?.(`Erreur validation (HTTP ${r?.status || 'NA'})`)
        }
      }
    },
    
    showRefusModal(absence) {
      this.selectedAbsence = absence
      this.motifRefus = ''
      this.showRefusModalFlag = true
    },
    
    async refuserAbsence() {
      if (!this.motifRefus.trim()) {
        this.$toast?.error?.('Veuillez saisir un motif de refus')
        return
      }
      
      try {
        await axios.post(`enseignant/absences/${this.selectedAbsence.id}/refuser`, {
          motif_refus: this.motifRefus
        })
        
        this.$toast?.success?.('Absence refusée avec succès')
        this.showRefusModalFlag = false
        this.selectedAbsence = null
        this.motifRefus = ''
        this.loadDashboard()
      } catch (error) {
        console.error('Erreur lors du refus:', error)
        const r = error.response
        this.$toast?.error?.(`Erreur refus (HTTP ${r?.status || 'NA'})`)
      }
    },
    
    voirDetails(absence) {
      // Implémenter la vue des détails
      console.log('Voir détails de l\'absence:', absence)
    },
    
    async modifierProfil() {
      try {
        await axios.put('enseignant/profil', this.profilForm)
        this.$toast?.success?.('Profil mis à jour avec succès')
        this.showProfilModal = false
        this.loadDashboard()
      } catch (error) {
        console.error('Erreur lors de la modification:', error)
        this.$toast?.error?.('Erreur lors de la modification du profil')
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    getStatutLabel(statut) {
      const labels = {
        en_attente: 'En attente',
        validee: 'Validée',
        refusee: 'Refusée',
        annulee: 'Annulée'
      }
      return labels[statut] || statut
    },
    
    getStatutBadgeClass(statut) {
      const classes = {
        en_attente: 'badge-warning',
        validee: 'badge-success',
        refusee: 'badge-danger',
        annulee: 'badge-secondary'
      }
      return classes[statut] || 'badge-secondary'
    }
  }
}
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.card.bg-success,
.card.bg-warning,
.card.bg-danger,
.card.bg-info {
  border: none;
}

.badge {
  font-size: 0.8em;
}

.table th {
  border-top: none;
  font-weight: 600;
}

.btn-group .btn {
  margin-right: 2px;
}
</style>