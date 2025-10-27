<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Gestion des Absences</h3>
            <button 
              class="btn btn-primary" 
              @click="showCreateModal = true"
              v-if="canCreateAbsence"
            >
              <i class="fas fa-plus"></i> Nouvelle Absence
            </button>
          </div>
          
          <div class="card-body">
            <!-- Filtres et recherche -->
            <div class="row mb-3">
              <div class="col-md-3">
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Rechercher..." 
                  v-model="searchQuery"
                  @input="searchAbsences"
                >
              </div>
              <div class="col-md-2">
                <select class="form-control" v-model="filters.statut" @change="loadAbsences">
                  <option value="">Tous les statuts</option>
                  <option v-for="(label, value) in statuts" :key="value" :value="value">
                    {{ label }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="filters.date_debut"
                  @change="loadAbsences"
                  placeholder="Date début"
                >
              </div>
              <div class="col-md-2">
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="filters.date_fin"
                  @change="loadAbsences"
                  placeholder="Date fin"
                >
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary" @click="resetFilters">
                  <i class="fas fa-times"></i> Reset
                </button>
              </div>
            </div>

            <!-- Statistiques -->
            <div class="row mb-3" v-if="statistiques">
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body text-center">
                    <h4>{{ statistiques.total_absences || statistiques.absences_en_attente || 0 }}</h4>
                    <p class="mb-0">Total</p>
                  </div>
                </div>
              </div>
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
                    <h4>{{ statistiques.absences_validees || 0 }}</h4>
                    <p class="mb-0">Validées</p>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-danger text-white">
                  <div class="card-body text-center">
                    <h4>{{ statistiques.absences_refusees || 0 }}</h4>
                    <p class="mb-0">Refusées</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tableau des absences -->
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Étudiant</th>
                    <th>Période</th>
                    <th>Motif</th>
                    <th>Statut</th>
                    <th>Date déclaration</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="absence in absences.data" :key="absence.id">
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
                      <span class="text-truncate d-inline-block" style="max-width: 200px;" :title="absence.motif">
                        {{ absence.motif }}
                      </span>
                    </td>
                    <td>
                      <span 
                        class="badge" 
                        :class="getStatusBadgeClass(absence.statut)"
                      >
                        {{ getStatusLabel(absence.statut) }}
                      </span>
                    </td>
                    <td>{{ formatDate(absence.date_declaration) }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button 
                          class="btn btn-sm btn-outline-primary" 
                          @click="viewAbsence(absence)"
                          title="Voir"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-warning" 
                          @click="editAbsence(absence)"
                          title="Modifier"
                          v-if="canEditAbsence(absence)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-success" 
                          @click="validateAbsence(absence)"
                          title="Valider"
                          v-if="canValidateAbsence(absence)"
                        >
                          <i class="fas fa-check"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-danger" 
                          @click="rejectAbsence(absence)"
                          title="Rejeter"
                          v-if="canRejectAbsence(absence)"
                        >
                          <i class="fas fa-times"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-secondary" 
                          @click="cancelAbsence(absence)"
                          title="Annuler"
                          v-if="canCancelAbsence(absence)"
                        >
                          <i class="fas fa-ban"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-danger" 
                          @click="deleteAbsence(absence)"
                          title="Supprimer"
                          v-if="canDeleteAbsence(absence)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <nav v-if="absences.last_page > 1">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: absences.current_page === 1 }">
                  <button class="page-link" @click="changePage(absences.current_page - 1)">
                    Précédent
                  </button>
                </li>
                <li 
                  v-for="page in getPageNumbers()" 
                  :key="page" 
                  class="page-item" 
                  :class="{ active: page === absences.current_page }"
                >
                  <button class="page-link" @click="changePage(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: absences.current_page === absences.last_page }">
                  <button class="page-link" @click="changePage(absences.current_page + 1)">
                    Suivant
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de création/édition -->
    <div class="modal fade" :class="{ show: showCreateModal || showEditModal }" :style="{ display: showCreateModal || showEditModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ showCreateModal ? 'Nouvelle Absence' : 'Modifier Absence' }}
            </h5>
            <button type="button" class="close" @click="closeModals">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveAbsence">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Date de début *</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      v-model="absenceForm.date_debut" 
                      required
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Date de fin *</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      v-model="absenceForm.date_fin" 
                      required
                    >
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Motif *</label>
                <textarea 
                  class="form-control" 
                  rows="3" 
                  v-model="absenceForm.motif" 
                  required
                  placeholder="Décrivez la raison de votre absence..."
                ></textarea>
              </div>
              <div class="form-group">
                <label>Justificatif (optionnel)</label>
                <input 
                  type="file" 
                  class="form-control-file" 
                  @change="handleFileUpload"
                  accept=".pdf,.jpg,.jpeg,.png"
                >
                <small class="form-text text-muted">
                  Formats acceptés: PDF, JPG, JPEG, PNG (max 2MB)
                </small>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModals">
              Annuler
            </button>
            <button type="button" class="btn btn-primary" @click="saveAbsence">
              {{ showCreateModal ? 'Déclarer' : 'Modifier' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de visualisation -->
    <div class="modal fade" :class="{ show: showViewModal }" :style="{ display: showViewModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Détails de l'Absence</h5>
            <button type="button" class="close" @click="showViewModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body" v-if="selectedAbsence">
            <div class="row">
              <div class="col-md-6">
                <h6>Informations générales</h6>
                <p><strong>Étudiant:</strong> {{ selectedAbsence.etudiant?.utilisateur?.nom }} {{ selectedAbsence.etudiant?.utilisateur?.prenom }}</p>
                <p><strong>Numéro étudiant:</strong> {{ selectedAbsence.etudiant?.numero_etudiant }}</p>
                <p><strong>Période:</strong> {{ formatDate(selectedAbsence.date_debut) }} - {{ formatDate(selectedAbsence.date_fin) }}</p>
                <p><strong>Durée:</strong> {{ selectedAbsence.duree_en_jours }} jour(s)</p>
                <p><strong>Statut:</strong> 
                  <span class="badge" :class="getStatusBadgeClass(selectedAbsence.statut)">
                    {{ getStatusLabel(selectedAbsence.statut) }}
                  </span>
                </p>
              </div>
              <div class="col-md-6">
                <h6>Détails</h6>
                <p><strong>Motif:</strong></p>
                <p class="bg-light p-2">{{ selectedAbsence.motif }}</p>
                <p><strong>Date de déclaration:</strong> {{ formatDate(selectedAbsence.date_declaration) }}</p>
                <p v-if="selectedAbsence.date_traitement">
                  <strong>Date de traitement:</strong> {{ formatDate(selectedAbsence.date_traitement) }}
                </p>
                <p v-if="selectedAbsence.motif_refus">
                  <strong>Motif de refus:</strong> {{ selectedAbsence.motif_refus }}
                </p>
              </div>
            </div>
            <div class="row mt-3" v-if="selectedAbsence.justificatif_chemin">
              <div class="col-12">
                <h6>Justificatif</h6>
                <a 
                  :href="`/storage/${selectedAbsence.justificatif_chemin}`" 
                  target="_blank" 
                  class="btn btn-outline-primary"
                >
                  <i class="fas fa-download"></i> Télécharger le justificatif
                </a>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showViewModal = false">
              Fermer
            </button>
            <button 
              type="button" 
              class="btn btn-success" 
              @click="validateAbsence(selectedAbsence)"
              v-if="canValidateAbsence(selectedAbsence)"
            >
              <i class="fas fa-check"></i> Valider
            </button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="rejectAbsence(selectedAbsence)"
              v-if="canRejectAbsence(selectedAbsence)"
            >
              <i class="fas fa-times"></i> Rejeter
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de rejet -->
    <div class="modal fade" :class="{ show: showRejectModal }" :style="{ display: showRejectModal ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Rejeter l'Absence</h5>
            <button type="button" class="close" @click="showRejectModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Motif du refus *</label>
              <textarea 
                class="form-control" 
                rows="3" 
                v-model="rejectForm.motif_refus" 
                required
                placeholder="Expliquez pourquoi cette absence est rejetée..."
              ></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showRejectModal = false">
              Annuler
            </button>
            <button type="button" class="btn btn-danger" @click="confirmReject">
              Rejeter
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
  name: 'AbsenceList',
  data() {
    return {
      absences: { data: [], current_page: 1, last_page: 1 },
      statuts: {},
      statistiques: null,
      searchQuery: '',
      filters: {
        statut: '',
        date_debut: '',
        date_fin: ''
      },
      showCreateModal: false,
      showEditModal: false,
      showViewModal: false,
      showRejectModal: false,
      selectedAbsence: null,
      absenceForm: {
        date_debut: '',
        date_fin: '',
        motif: '',
        justificatif: null
      },
      rejectForm: {
        motif_refus: ''
      }
    }
  },
  computed: {
    canCreateAbsence() {
      // Seuls les étudiants peuvent créer des absences
      return this.$store.getters.userRole === 'etudiant'
    }
  },
  mounted() {
    this.loadAbsences()
    this.loadStatuts()
    this.loadStatistiques()
  },
  methods: {
    async loadAbsences() {
      try {
        const params = new URLSearchParams()
        if (this.filters.statut) params.append('statut', this.filters.statut)
        if (this.filters.date_debut) params.append('date_debut', this.filters.date_debut)
        if (this.filters.date_fin) params.append('date_fin', this.filters.date_fin)
        if (this.searchQuery) params.append('q', this.searchQuery)
        
        const response = await axios.get(`absences?${params.toString()}`)
        this.absences = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des absences:', error)
        this.$toast.error('Erreur lors du chargement des absences')
      }
    },
    
    async loadStatuts() {
      try {
        const response = await axios.get('absences/statuts')
        this.statuts = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des statuts:', error)
      }
    },
    
    async loadStatistiques() {
      try {
        const response = await axios.get('absences/statistiques')
        this.statistiques = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error)
      }
    },
    
    searchAbsences() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.loadAbsences()
      }, 500)
    },
    
    resetFilters() {
      this.filters = { statut: '', date_debut: '', date_fin: '' }
      this.searchQuery = ''
      this.loadAbsences()
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.absences.last_page) {
        this.loadAbsences()
      }
    },
    
    getPageNumbers() {
      const pages = []
      const start = Math.max(1, this.absences.current_page - 2)
      const end = Math.min(this.absences.last_page, start + 4)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    },
    
    viewAbsence(absence) {
      this.selectedAbsence = absence
      this.showViewModal = true
    },
    
    editAbsence(absence) {
      this.selectedAbsence = absence
      this.absenceForm = {
        date_debut: absence.date_debut,
        date_fin: absence.date_fin,
        motif: absence.motif,
        justificatif: null
      }
      this.showEditModal = true
    },
    
    handleFileUpload(event) {
      this.absenceForm.justificatif = event.target.files[0]
    },
    
    async saveAbsence() {
      try {
        const formData = new FormData()
        formData.append('date_debut', this.absenceForm.date_debut)
        formData.append('date_fin', this.absenceForm.date_fin)
        formData.append('motif', this.absenceForm.motif)
        if (this.absenceForm.justificatif) {
          formData.append('justificatif', this.absenceForm.justificatif)
        }
        
        if (this.showCreateModal) {
          await axios.post('absences', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
          this.$toast.success('Absence déclarée avec succès')
        } else {
          await axios.put(`absences/${this.selectedAbsence.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
          this.$toast.success('Absence modifiée avec succès')
        }
        
        this.closeModals()
        this.loadAbsences()
        this.loadStatistiques()
      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        this.$toast.error('Erreur lors de la sauvegarde')
      }
    },
    
    async validateAbsence(absence) {
      try {
        await axios.post(`absences/${absence.id}/valider`)
        this.$toast.success('Absence validée avec succès')
        this.loadAbsences()
        this.loadStatistiques()
        this.showViewModal = false
      } catch (error) {
        console.error('Erreur lors de la validation:', error)
        this.$toast.error('Erreur lors de la validation')
      }
    },
    
    rejectAbsence(absence) {
      this.selectedAbsence = absence
      this.rejectForm.motif_refus = ''
      this.showRejectModal = true
    },
    
    async confirmReject() {
      try {
        await axios.post(`absences/${this.selectedAbsence.id}/rejeter`, {
          motif_refus: this.rejectForm.motif_refus
        })
        this.$toast.success('Absence rejetée avec succès')
        this.loadAbsences()
        this.loadStatistiques()
        this.showRejectModal = false
        this.showViewModal = false
      } catch (error) {
        console.error('Erreur lors du rejet:', error)
        this.$toast.error('Erreur lors du rejet')
      }
    },
    
    async cancelAbsence(absence) {
      if (confirm('Êtes-vous sûr de vouloir annuler cette absence ?')) {
        try {
          await axios.post(`absences/${absence.id}/annuler`)
          this.$toast.success('Absence annulée avec succès')
          this.loadAbsences()
          this.loadStatistiques()
        } catch (error) {
          console.error('Erreur lors de l\'annulation:', error)
          this.$toast.error('Erreur lors de l\'annulation')
        }
      }
    },
    
    async deleteAbsence(absence) {
      if (confirm('Êtes-vous sûr de vouloir supprimer cette absence ?')) {
        try {
          await axios.delete(`absences/${absence.id}`)
          this.$toast.success('Absence supprimée avec succès')
          this.loadAbsences()
          this.loadStatistiques()
        } catch (error) {
          console.error('Erreur lors de la suppression:', error)
          this.$toast.error('Erreur lors de la suppression')
        }
      }
    },
    
    canEditAbsence(absence) {
      return absence.statut === 'en_attente' && this.$store.getters.userRole === 'etudiant'
    },
    
    canValidateAbsence(absence) {
      return absence.statut === 'en_attente' && this.$store.getters.userRole === 'enseignant'
    },
    
    canRejectAbsence(absence) {
      return absence.statut === 'en_attente' && this.$store.getters.userRole === 'enseignant'
    },
    
    canCancelAbsence(absence) {
      return absence.statut === 'en_attente' && this.$store.getters.userRole === 'etudiant'
    },
    
    canDeleteAbsence(absence) {
      return absence.statut === 'en_attente' && this.$store.getters.userRole === 'etudiant'
    },
    
    closeModals() {
      this.showCreateModal = false
      this.showEditModal = false
      this.showViewModal = false
      this.showRejectModal = false
      this.selectedAbsence = null
      this.absenceForm = {
        date_debut: '',
        date_fin: '',
        motif: '',
        justificatif: null
      }
      this.rejectForm = {
        motif_refus: ''
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR')
    },
    
    getStatusLabel(statut) {
      return this.statuts[statut] || statut
    },
    
    getStatusBadgeClass(statut) {
      const classes = {
        'en_attente': 'badge-warning',
        'validee': 'badge-success',
        'refusee': 'badge-danger',
        'annulee': 'badge-secondary'
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

.btn-group .btn {
  margin-right: 2px;
}

.table th {
  border-top: none;
}

.badge {
  font-size: 0.8em;
}

.card.bg-primary,
.card.bg-warning,
.card.bg-success,
.card.bg-danger {
  border: none;
}
</style>
