<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="fas fa-user-tie me-2"></i>
          Tableau de bord Directeur
        </h2>
      </div>
    </div>

    <!-- Informations directeur -->
    <div class="row mb-4" v-if="directeur">
      <div class="col-12">
        <div class="card bg-danger text-white">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="mb-1">{{ directeur.utilisateur?.nom }} {{ directeur.utilisateur?.prenom }}</h4>
                <p class="mb-0">
                  <i class="fas fa-crown me-2"></i>
                  Directeur de l'Institut Supérieur d'Informatique
                </p>
                <p class="mb-0" v-if="directeur.signature">
                  <i class="fas fa-signature me-2"></i>
                  Signature: {{ directeur.signature }}
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

    <!-- Statistiques générales -->
    <div class="row mb-4" v-if="statistiques">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.utilisateurs?.total || 0 }}</h4>
            <p class="mb-0">Utilisateurs</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.documents?.total || 0 }}</h4>
            <p class="mb-0">Documents</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.absences?.total || 0 }}</h4>
            <p class="mb-0">Absences</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body text-center">
            <h4>{{ statistiques.modeles?.total || 0 }}</h4>
            <p class="mb-0">Modèles</p>
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
                <button class="btn btn-outline-primary w-100" @click="goToStatistiques('documents')">
                  <i class="fas fa-chart-bar me-2"></i>
                  Consulter les statistiques sur les documents
                </button>
              </div>
              <div class="col-md-3 mb-3">
                <button class="btn btn-outline-primary w-100" @click="goToStatistiques('absences')">
                  <i class="fas fa-chart-area me-2"></i>
                  Consulter les statistiques sur les absences
                </button>
              </div>
              <div class="col-md-3 mb-3">
                <button class="btn btn-outline-success w-100" @click="genererRapportAnnuel">
                  <i class="fas fa-file-alt me-2"></i>
                  Générer un rapport annuel
                </button>
              </div>
              <div class="col-md-3 mb-3">
                <button class="btn btn-outline-info w-100" @click="goToRapports">
                  <i class="fas fa-file-export me-2"></i>
                  Exporter les données
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Graphiques et répartitions -->
    <div class="row mb-4">
      <!-- Utilisateurs par rôle -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-users me-2"></i>
              Utilisateurs par rôle
            </h5>
          </div>
          <div class="card-body">
            <div v-if="statistiques?.utilisateurs?.par_role">


              <div v-if="statistiques?.utilisateurs?.listes_par_role" class="mt-3">
                <div v-for="(users, role) in statistiques.utilisateurs.listes_par_role" :key="'list-'+role" class="mb-3">
                  <h6 class="mb-2">
                    <span class="badge" :class="getRoleBadgeClass(role)">{{ getRoleLabel(role) }}</span>
                    <small class="text-muted ms-2">(derniers)</small>
                  </h6>
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th>Nom</th>
                          <th>Prénom</th>
                          <th>Rôle</th>
                          <th>Email</th>
                          <th>Créé le</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="u in users" :key="u.id">
                          <td>{{ u.nom }}</td>
                          <td>{{ u.prenom }}</td>
                          <td>
                            <span class="badge" :class="getRoleBadgeClass(u.role)">{{ getRoleLabel(u.role) }}</span>
                          </td>
                          <td><small>{{ u.email }}</small></td>
                          <td><small>{{ formatDate(u.created_at) }}</small></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

            </div>
            <div v-else class="text-muted">
              Aucune donnée disponible
            </div>
          </div>
        </div>
      </div>

      <!-- Documents par type -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-file-alt me-2"></i>
              Documents par type
            </h5>
          </div>
          <div class="card-body">
            <div v-if="statistiques?.documents?.par_type">


              <div v-if="statistiques?.documents?.listes_par_type" class="mt-3">
                <div v-for="(docs, type) in statistiques.documents.listes_par_type" :key="'docs-'+type" class="mb-3">
                  <h6 class="mb-2">
                    <span class="badge bg-primary">{{ type }}</span>
                    <small class="text-muted ms-2">(derniers)</small>
                  </h6>
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th>Nom</th>
                          <th>Type</th>
                          <th>Utilisateur</th>
                          <th>Rôle</th>
                          <th>Date</th>
                          <th>Statut</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="d in docs" :key="d.id">
                          <td>{{ d.nom }}</td>
                          <td><small>{{ (d.type === 'emploi_temps') ? 'emploi_du_temps' : (d.type || '-') }}</small></td>
                          <td><small>{{ d.etudiant?.utilisateur?.nom }} {{ d.etudiant?.utilisateur?.prenom }}</small></td>
                          <td>
                            <span class="badge" :class="getRoleBadgeClass(d.etudiant?.utilisateur?.role)">{{ getRoleLabel(d.etudiant?.utilisateur?.role) }}</span>
                          </td>
                          <td><small>{{ formatDate(d.date_generation) }}</small></td>
                          <td>
                            <span class="badge" :class="d.est_public ? 'bg-success' : 'bg-warning'">
                              {{ d.est_public ? 'Public' : 'Archivé' }}
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

            </div>
            <div v-else class="text-muted">
              Aucune donnée disponible
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Documents récents -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-clock me-2"></i>
              Documents récents
            </h5>
            <button class="btn btn-sm btn-primary" @click="loadRecentDocuments">
              Actualiser
            </button>
          </div>
          <div class="card-body">
            <div v-if="documentsRecents && documentsRecents.length > 0">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Nom</th>
                      <th>Type</th>
                      <th>Utilisateur</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="doc in documentsRecents" :key="doc.id">
                      <td>{{ doc.nom }}</td>
                      <td>
                        <span class="badge bg-info">{{ ((doc.type || doc.modele_document?.type) === 'emploi_temps') ? 'emploi_du_temps' : (doc.type || doc.modele_document?.type || '-') }}</span>
                      </td>
                      <td>{{ doc.etudiant?.utilisateur?.nom }} {{ doc.etudiant?.utilisateur?.prenom }}</td>
                      <td>{{ formatDate(doc.date_generation) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucun document récent
            </div>
          </div>
        </div>
      </div>

      <!-- Absences récentes -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-calendar-times me-2"></i>
              Absences récentes
            </h5>
            <button class="btn btn-sm btn-warning" @click="loadRecentAbsences">
              Actualiser
            </button>
          </div>
          <div class="card-body">
            <div v-if="absencesRecentes && absencesRecentes.length > 0">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Étudiant</th>
                      <th>Période</th>
                      <th>Statut</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="absence in absencesRecentes" :key="absence.id">
                      <td>
                        <strong>{{ absence.etudiant?.utilisateur?.nom }} {{ absence.etudiant?.utilisateur?.prenom }}</strong>
                        <br>
                        <small class="text-muted">{{ absence.etudiant?.numero_etudiant }}</small>
                      </td>
                      <td>
                        {{ formatDate(absence.date_debut) }} - {{ formatDate(absence.date_fin) }}
                      </td>
                      <td>
                        <span class="badge" :class="absence.statut === 'validee' ? 'bg-success' : (absence.statut === 'refusee' ? 'bg-danger' : 'bg-warning')">
                          {{ absence.statut === 'validee' ? 'Validée' : (absence.statut === 'refusee' ? 'Refusée' : 'En attente') }}
                        </span>
                      </td>
                      <td>{{ formatDate(absence.date_declaration) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucune absence récente
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal statistiques détaillées -->
    <div class="modal fade" :class="{ show: showStatistiquesModal }" :style="{ display: showStatistiquesModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Statistiques détaillées</h5>
            <button type="button" class="close" @click="showStatistiquesModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div v-if="statistiques">
              <div class="row">
                <div class="col-md-6">
                  <h6>Utilisateurs</h6>
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Total</span>
                      <span class="badge badge-primary">{{ statistiques.utilisateurs?.total || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Nouveaux ce mois</span>
                      <span class="badge badge-success">{{ statistiques.utilisateurs?.nouveaux_ce_mois || 0 }}</span>
                    </li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <h6>Documents</h6>
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Total</span>
                      <span class="badge badge-primary">{{ statistiques.documents?.total || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Publics</span>
                      <span class="badge badge-success">{{ statistiques.documents?.publics || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Archivés</span>
                      <span class="badge badge-warning">{{ statistiques.documents?.archives || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Ce mois</span>
                      <span class="badge badge-info">{{ statistiques.documents?.ce_mois || 0 }}</span>
                    </li>
                  </ul>
                </div>
              </div>
              
              <div class="row mt-3">
                <div class="col-md-6">
                  <h6>Absences</h6>
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Total</span>
                      <span class="badge badge-primary">{{ statistiques.absences?.total || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>En attente</span>
                      <span class="badge badge-warning">{{ statistiques.absences?.en_attente || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Validées</span>
                      <span class="badge badge-success">{{ statistiques.absences?.validees || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Refusées</span>
                      <span class="badge bg-danger">{{ statistiques.absences?.refusees || 0 }}</span>
                    </li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <h6>Modèles</h6>
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Total</span>
                      <span class="badge badge-primary">{{ statistiques.modeles?.total || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Actifs</span>
                      <span class="badge badge-success">{{ statistiques.modeles?.actifs || 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                      <span>Inactifs</span>
                      <span class="badge badge-warning">{{ statistiques.modeles?.inactifs || 0 }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showStatistiquesModal = false">
              Fermer
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
              
              <div class="form-group mb-3">
                <label>Signature</label>
                <textarea 
                  class="form-control" 
                  rows="3" 
                  v-model="profilForm.signature"
                  placeholder="Signature du directeur..."
                ></textarea>
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
  name: 'DirecteurDashboard',
  data() {
    return {
      directeur: null,
      statistiques: null,
      documentsRecents: [],
      absencesRecentes: [],
      showStatistiquesModal: false,
      showProfilModal: false,
      showDetailsUsers: true,
      showDetailsDocs: true,
      profilForm: {
        nom: '',
        prenom: '',
        email: '',
        signature: ''
      }
    }
  },
  mounted() {
    this.loadDashboard()
  },
  methods: {
    async loadDashboard() {
      try {
        const response = await axios.get('directeur/dashboard')
        const data = response.data.data
        
        this.directeur = data.directeur
        this.statistiques = data.statistiques
        this.documentsRecents = data.documents_recents || []
        this.absencesRecentes = data.absences_recentes || []
        
        // Initialiser le formulaire de profil
        if (this.directeur) {
          this.profilForm = {
            nom: this.directeur.utilisateur?.nom || '',
            prenom: this.directeur.utilisateur?.prenom || '',
            email: this.directeur.utilisateur?.email || '',
            signature: this.directeur.signature || ''
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement du dashboard:', error)
        this.$toast.error('Erreur lors du chargement du dashboard')
      }
    },
    
    async loadRecentDocuments() {
      try {
        const response = await axios.get('directeur/dashboard')
        this.documentsRecents = response.data.data.documents_recents || []
        this.$toast.success('Documents actualisés')
      } catch (error) {
        console.error('Erreur lors du chargement des documents:', error)
        this.$toast.error('Erreur lors du chargement des documents')
      }
    },
    
    async loadRecentAbsences() {
      try {
        const response = await axios.get('directeur/dashboard')
        this.absencesRecentes = response.data.data.absences_recentes || []
        this.$toast.success('Absences actualisées')
      } catch (error) {
        console.error('Erreur lors du chargement des absences:', error)
        this.$toast.error('Erreur lors du chargement des absences')
      }
    },
    
    async genererRapportAnnuel() {
      try {
        const annee = new Date().getFullYear()
        const response = await axios.get(`directeur/rapport-annuel/${annee}`)
        const rapport = response.data.data
        
        // Afficher le rapport dans une nouvelle fenêtre ou télécharger
        console.log('Rapport annuel:', rapport)
        this.$toast.success('Rapport annuel généré')
      } catch (error) {
        console.error('Erreur lors de la génération du rapport:', error)
        this.$toast.error('Erreur lors de la génération du rapport')
      }
    },
    
    goToStatistiques(focus) {
      this.$router.push({ name: 'DirecteurStatistiques', query: { focus } })
    },
    goToRapports() {
      this.$router.push({ name: 'DirecteurRapports' })
    },
    
    downloadCSV(data, filename) {
      if (!data || data.length === 0) return
      
      const headers = Object.keys(data[0])
      const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => row[header] || '').join(','))
      ].join('\n')
      
      const blob = new Blob([csvContent], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      link.click()
      window.URL.revokeObjectURL(url)
    },
    
    async modifierProfil() {
      try {
        await axios.put('/api/directeur/profil', this.profilForm)
        this.$toast.success('Profil mis à jour avec succès')
        this.showProfilModal = false
        this.loadDashboard()
      } catch (error) {
        console.error('Erreur lors de la modification:', error)
        this.$toast.error('Erreur lors de la modification du profil')
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    getRoleLabel(role) {
      const labels = {
        etudiant: 'Étudiant',
        enseignant: 'Enseignant',
        administrateur: 'Administrateur',
        directeur: 'Directeur'
      }
      return labels[role] || role
    },
    
    getRoleBadgeClass(role) {
      const classes = {
        etudiant: 'badge-info',
        enseignant: 'badge-success',
        administrateur: 'badge-warning',
        directeur: 'badge-danger'
      }
      return classes[role] || 'badge-secondary'
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

.list-group-item {
  border: none;
  border-bottom: 1px solid #dee2e6;
}

.list-group-item:last-child {
  border-bottom: none;
}
</style>
