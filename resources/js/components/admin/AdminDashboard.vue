<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="fas fa-tachometer-alt me-2"></i>
          Tableau de bord Administrateur
        </h2>
      </div>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4" v-if="statistics">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h4>{{ statistics.documents?.total || 0 }}</h4>
                <p class="mb-0">Documents</p>
              </div>
              <div class="align-self-center">
                <i class="fas fa-file-alt fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h4>{{ statistics.utilisateurs?.total || 0 }}</h4>
                <p class="mb-0">Utilisateurs</p>
              </div>
              <div class="align-self-center">
                <i class="fas fa-users fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h4>{{ statistics.absences?.en_attente || 0 }}</h4>
                <p class="mb-0">Absences en attente</p>
              </div>
              <div class="align-self-center">
                <i class="fas fa-clock fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h4>{{ statistics.modeles?.actifs || 0 }}</h4>
                <p class="mb-0">Modèles actifs</p>
              </div>
              <div class="align-self-center">
                <i class="fas fa-file-code fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Graphiques et détails -->
    <div class="row">
      <!-- Documents par type -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-chart-pie me-2"></i>
              Documents par type
            </h5>
          </div>
          <div class="card-body">
            <div v-if="statistics?.documents?.par_type">
              <div
                v-for="(count, type) in statistics.documents.par_type"
                :key="type"
                class="d-flex justify-content-between align-items-center mb-2"
              >
                <span class="badge bg-primary">{{ type }}</span>
                <span class="fw-bold">{{ count }}</span>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucune donnée disponible
            </div>
          </div>
        </div>
      </div>

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
            <div v-if="statistics?.utilisateurs?.par_role">
              <div
                v-for="(count, role) in statistics.utilisateurs.par_role"
                :key="role"
                class="d-flex justify-content-between align-items-center mb-2"
              >
                <span class="badge" :class="getRoleBadgeClass(role)">
                  {{ getRoleLabel(role) }}
                </span>
                <span class="fw-bold">{{ count }}</span>
              </div>
            </div>
            <div v-else class="text-muted">
              Aucune donnée disponible
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="row mt-4">
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
              <div class="col-md-4 col-lg-3 mb-3">
                <router-link to="/admin/generer-publier" class="btn btn-outline-primary w-100">
                  <i class="fas fa-file-upload me-2"></i>
                  Générer & Publier
                </router-link>
              </div>
              <div class="col-md-4 col-lg-3 mb-3">
                <router-link to="/admin/emplois" class="btn btn-outline-warning w-100">
                  <i class="fas fa-calendar-alt me-2"></i>
                  Emplois du temps
                </router-link>
              </div>
              <div class="col-md-4 col-lg-3 mb-3">
                <router-link to="/admin/archivage" class="btn btn-outline-secondary w-100">
                  <i class="fas fa-archive me-2"></i>
                  Archiver les documents
                </router-link>
              </div>
              <div class="col-md-4 col-lg-3 mb-3">
                <router-link to="/admin/modeles" class="btn btn-outline-info w-100">
                  <i class="fas fa-file-code me-2"></i>
                  Modèles de documents
                </router-link>
              </div>
              <div class="col-md-4 col-lg-3 mb-3">
                <router-link to="/admin/utilisateurs" class="btn btn-outline-success w-100">
                  <i class="fas fa-users me-2"></i>
                  Gérer les utilisateurs
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Documents récents -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-clock me-2"></i>
              Documents récents
            </h5>
            <router-link to="/documents-list" class="btn btn-sm btn-primary">
              Voir tout
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="recentDocuments && recentDocuments.length > 0">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Nom</th>
                      <th>Type</th>
                      <th>Étudiant</th>
                      <th>Date</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="doc in recentDocuments" :key="doc.id">
                      <td>{{ doc.nom }}</td>
                      <td>
                        <span class="badge bg-info">{{ doc.type }}</span>
                      </td>
                      <td>
                        <span v-if="doc.utilisateur">{{ doc.utilisateur?.nom }} {{ doc.utilisateur?.prenom }}</span>
                        <span v-else>#{{ doc.etudiant_id || '-' }}</span>
                      </td>
                      <td>{{ formatDate(doc.date_generation) }}</td>
                      <td>
                        <span class="badge" :class="doc.est_public ? 'bg-success' : 'bg-warning'">
                          {{ doc.est_public ? 'Public' : 'Archivé' }}
                        </span>
                      </td>
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
    </div>

    <!-- Absences en attente -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Absences en attente
            </h5>
            <router-link to="/absences-list" class="btn btn-sm btn-warning">
              Voir tout
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="pendingAbsences && pendingAbsences.length > 0">
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Étudiant</th>
                      <th>Période</th>
                      <th>Motif</th>
                      <th>Date déclaration</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="absence in pendingAbsences" :key="absence.id">
                      <td>
                        <strong>{{ absence.etudiant?.utilisateur?.nom }} {{ absence.etudiant?.utilisateur?.prenom }}</strong>
                        <br>
                        <small class="text-muted">{{ absence.etudiant?.numero_etudiant }}</small>
                      </td>
                      <td>
                        {{ formatDate(absence.date_debut) }} - {{ formatDate(absence.date_fin) }}
                      </td>
                      <td>
                        <span class="text-truncate d-inline-block" style="max-width: 200px;" :title="absence.motif">
                          {{ absence.motif }}
                        </span>
                      </td>
                      <td>{{ formatDate(absence.date_declaration) }}</td>
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

    <!-- Modal de gestion des utilisateurs -->
    <div class="modal fade" :class="{ show: showUserManagement }" :style="{ display: showUserManagement ? 'block' : 'none' }">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Gestion des Utilisateurs</h5>
            <button type="button" class="btn-close" @click="showUserManagement = false" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <UserManagement />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import AdminModeles from './AdminModeles.vue'

export default {
  name: 'AdminDashboard',
  components: {
    UserManagement: AdminModeles
  },
  data() {
    return {
      statistics: null,
      recentDocuments: [],
      pendingAbsences: [],
      showUserManagement: false
    }
  },
  mounted() {
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.loadStatistics()
    this.loadRecentDocuments()
    this.loadPendingAbsences()
  },
  methods: {
    async loadStatistics() {
      try {
        const response = await axios.get('admin/statistiques')
        this.statistics = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error)
        this.$toast.error('Erreur lors du chargement des statistiques')
      }
    },

    async loadRecentDocuments() {
      try {
        const response = await axios.get('admin/documents/recent?limit=5')
        this.recentDocuments = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des documents récents:', error)
      }
    },

    async loadPendingAbsences() {
      try {
        const response = await axios.get('absences?statut=en_attente&per_page=5')
        this.pendingAbsences = response.data.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des absences en attente:', error)
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
        etudiant: 'bg-info',
        enseignant: 'bg-success',
        administrateur: 'bg-warning',
        directeur: 'bg-danger'
      }
      return classes[role] || 'bg-secondary'
    }
  }
}
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card.bg-primary,
.card.bg-success,
.card.bg-warning,
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

.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
</style>
