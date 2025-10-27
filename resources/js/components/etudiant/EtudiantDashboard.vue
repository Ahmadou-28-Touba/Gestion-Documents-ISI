<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="fas fa-graduation-cap me-2"></i>
          Tableau de bord Étudiant
        </h2>
      </div>
    </div>

    <!-- Informations étudiant -->
    <div class="row mb-4" v-if="etudiant">
      <div class="col-12">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="mb-1">{{ etudiant.utilisateur?.nom }} {{ etudiant.utilisateur?.prenom }}</h4>
                <p class="mb-0">
                  <i class="fas fa-id-card me-2"></i>
                  {{ etudiant.numero_etudiant }} | {{ etudiant.filiere }} - {{ etudiant.annee }}
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

    <!-- Statistiques -->
    <div class="row mb-4" v-if="statistiques">
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.total_absences }}</h4>
            <p class="mb-0">Total absences</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_validees }}</h4>
            <p class="mb-0">Validées</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-danger text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_refusees }}</h4>
            <p class="mb-0">Refusées</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences_en_attente }}</h4>
            <p class="mb-0">En attente</p>
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
                <button class="btn btn-outline-warning w-100" @click="showAbsenceModal = true">
                  <i class="fas fa-calendar-times me-2"></i>
                  Déclarer une absence
                </button>
              </div>
              <div class="col-md-3 mb-3">
                <router-link to="/absences" class="btn btn-outline-info w-100">
                  <i class="fas fa-list me-2"></i>
                  Mes absences
                </router-link>
              </div>
              <div class="col-md-3 mb-3">
                <router-link to="/documents" class="btn btn-outline-success w-100">
                  <i class="fas fa-file-alt me-2"></i>
                  Mes documents
                </router-link>
              </div>
              <div class="col-md-3 mb-3">
                <button class="btn btn-outline-primary w-100" @click="loadRecentDocuments">
                  <i class="fas fa-refresh me-2"></i>
                  Actualiser
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Absences récentes -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-clock me-2"></i>
              Absences récentes
            </h5>
            <router-link to="/absences" class="btn btn-sm btn-warning">
              Voir tout
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="recentAbsences && recentAbsences.length > 0">
              <div class="list-group list-group-flush">
                <div 
                  v-for="absence in recentAbsences" 
                  :key="absence.id"
                  class="list-group-item d-flex justify-content-between align-items-center"
                >
                  <div>
                    <h6 class="mb-1">{{ formatDate(absence.date_debut) }} - {{ formatDate(absence.date_fin) }}</h6>
                    <p class="mb-1 text-muted">{{ absence.motif }}</p>
                    <small class="text-muted">
                      Enseignant: {{ absence.enseignant?.utilisateur?.nom }} {{ absence.enseignant?.utilisateur?.prenom }}
                    </small>
                  </div>
                  <span class="badge" :class="getStatutBadgeClass(absence.statut)">
                    {{ getStatutLabel(absence.statut) }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucune absence récente
            </div>
          </div>
        </div>
      </div>

      <!-- Documents récents -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-file-alt me-2"></i>
              Documents récents
            </h5>
            <router-link to="/documents" class="btn btn-sm btn-success">
              Voir tout
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="recentDocuments && recentDocuments.length > 0">
              <div class="list-group list-group-flush">
                <div 
                  v-for="document in recentDocuments" 
                  :key="document.id"
                  class="list-group-item d-flex justify-content-between align-items-center"
                >
                  <div>
                    <h6 class="mb-1">{{ document.nom }}</h6>
                    <p class="mb-1 text-muted">{{ document.type }}</p>
                    <small class="text-muted">{{ formatDate(document.date_generation) }}</small>
                  </div>
                  <div>
                    <button 
                      class="btn btn-sm btn-outline-primary me-1" 
                      @click="telechargerDocument(document.id)"
                      title="Télécharger"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                    <span class="badge" :class="document.est_public ? 'badge-success' : 'badge-warning'">
                      {{ document.est_public ? 'Public' : 'Archivé' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucun document récent
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal déclaration d'absence -->
    <div class="modal fade" :class="{ show: showAbsenceModal }" :style="{ display: showAbsenceModal ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Déclarer une absence</h5>
            <button type="button" class="close" @click="showAbsenceModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="declarerAbsence">
              <div class="form-group mb-3">
                <label>Date de début *</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="absenceForm.date_debut" 
                  required
                  :min="today"
                >
              </div>
              
              <div class="form-group mb-3">
                <label>Date de fin *</label>
                <input 
                  type="date" 
                  class="form-control" 
                  v-model="absenceForm.date_fin" 
                  required
                  :min="absenceForm.date_debut"
                >
              </div>
              
              <div class="form-group mb-3">
                <label>Motif *</label>
                <textarea 
                  class="form-control" 
                  rows="3" 
                  v-model="absenceForm.motif" 
                  required
                  placeholder="Décrivez la raison de votre absence..."
                ></textarea>
              </div>
              
              <div class="form-group mb-3">
                <label>Justificatif (optionnel)</label>
                <input 
                  type="file" 
                  class="form-control" 
                  @change="handleFileUpload"
                  accept=".pdf,.jpg,.jpeg,.png"
                >
                <small class="form-text text-muted">
                  Formats acceptés: PDF, JPG, PNG (max 2MB)
                </small>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAbsenceModal = false">
              Annuler
            </button>
            <button type="button" class="btn btn-warning" @click="declarerAbsence">
              Déclarer l'absence
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal profil -->
    <div class="modal fade" :class="{ show: showProfilModal }" :style="{ display: showProfilModal ? 'block' : 'none' }">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Modifier le profil</h5>
            <button type="button" class="close" @click="showProfilModal = false">
              <span>&times;</span>
            </button>
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
                    <label>Filière</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="profilForm.filiere"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label>Année</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="profilForm.annee"
                    >
                  </div>
                </div>
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
  name: 'EtudiantDashboard',
  data() {
    return {
      etudiant: null,
      statistiques: null,
      recentAbsences: [],
      recentDocuments: [],
      showAbsenceModal: false,
      showProfilModal: false,
      absenceForm: {
        date_debut: '',
        date_fin: '',
        motif: '',
        justificatif: null
      },
      profilForm: {
        nom: '',
        prenom: '',
        email: '',
        filiere: '',
        annee: ''
      },
      today: new Date().toISOString().split('T')[0]
    }
  },
  mounted() {
    // S'assurer que le token est bien présent pour l'appel dashboard
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.loadDashboard()
  },
  methods: {
    async loadDashboard() {
      try {
        const response = await axios.get('etudiant/dashboard')
        const data = response.data.data
        
        this.etudiant = data.etudiant
        this.statistiques = data.statistiques
        this.recentAbsences = data.recent_absences
        this.recentDocuments = data.recent_documents
        
        // Initialiser le formulaire de profil
        if (this.etudiant) {
          this.profilForm = {
            nom: this.etudiant.utilisateur?.nom || '',
            prenom: this.etudiant.utilisateur?.prenom || '',
            email: this.etudiant.utilisateur?.email || '',
            filiere: this.etudiant.filiere || '',
            annee: this.etudiant.annee || ''
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement du dashboard:', error)
        const r = error.response
        const msg = r?.data?.message || error.message || 'Erreur lors du chargement du dashboard'
        this.$toast?.error?.(`Dashboard indisponible (HTTP ${r?.status || 'NA'})\n${msg}`)
      }
    },
    
    async declarerAbsence() {
      try {
        const formData = new FormData()
        formData.append('date_debut', this.absenceForm.date_debut)
        formData.append('date_fin', this.absenceForm.date_fin)
        formData.append('motif', this.absenceForm.motif)
        
        if (this.absenceForm.justificatif) {
          formData.append('justificatif', this.absenceForm.justificatif)
        }
        
        await axios.post('etudiant/absences', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        this.$toast.success('Absence déclarée avec succès')
        this.showAbsenceModal = false
        this.resetAbsenceForm()
        this.loadDashboard()
      } catch (error) {
        console.error('Erreur lors de la déclaration:', error)
        const r = error.response
        if (r?.status === 422 && r?.data?.errors) {
          const msgs = Object.values(r.data.errors).flat().join('\n')
          this.$toast?.error?.(`Validation échouée:\n${msgs}`)
        } else {
          const msg = r?.data?.message || error.message || 'Erreur lors de la déclaration de l\'absence'
          this.$toast?.error?.(msg)
        }
      }
    },
    
    async modifierProfil() {
      try {
        await axios.put('etudiant/profil', this.profilForm)
        this.$toast.success('Profil mis à jour avec succès')
        this.showProfilModal = false
        this.loadDashboard()
      } catch (error) {
        console.error('Erreur lors de la modification:', error)
        this.$toast.error('Erreur lors de la modification du profil')
      }
    },
    
    async telechargerDocument(documentId) {
      try {
        const response = await axios.get(`etudiant/documents/${documentId}/telecharger`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `document_${documentId}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        this.$toast.success('Téléchargement démarré')
      } catch (error) {
        console.error('Erreur lors du téléchargement:', error)
        this.$toast.error('Erreur lors du téléchargement')
      }
    },
    
    handleFileUpload(event) {
      this.absenceForm.justificatif = event.target.files[0]
    },
    
    resetAbsenceForm() {
      this.absenceForm = {
        date_debut: '',
        date_fin: '',
        motif: '',
        justificatif: null
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

.card.bg-primary,
.card.bg-warning,
.card.bg-success,
.card.bg-danger,
.card.bg-info {
  border: none;
}

.badge {
  font-size: 0.8em;
}

.list-group-item {
  border: none;
  border-bottom: 1px solid #dee2e6;
}

.list-group-item:last-child {
  border-bottom: none;
}
</style>
