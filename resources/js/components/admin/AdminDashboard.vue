<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4">
          <i class="fas fa-tachometer-alt me-2"></i>
          Tableau de bord Administrateur
        </h2>
      </div>

    <!-- Documents par type & Utilisateurs par rôle -->
    <div class="row mb-4" v-if="statistics">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-file-alt me-2"></i>
              Documents par type
            </h5>
          </div>
          <div class="card-body">
            <div v-if="statistics.documents_par_type && statistics.documents_par_type.length">
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th class="text-end">Nombre</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="d in statistics.documents_par_type" :key="d.type || 'inconnu'">
                      <td>{{ getDocumentTypeLabel(d.type) }}</td>
                      <td class="text-end"><span class="badge bg-primary">{{ d.total }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">Aucun document trouv\u00e9</div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-users me-2"></i>
              Utilisateurs par role
            </h5>
          </div>
          <div class="card-body">
            <div v-if="statistics.utilisateurs_par_role && statistics.utilisateurs_par_role.length">
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Role</th>
                      <th class="text-end">Nombre</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="r in statistics.utilisateurs_par_role" :key="r.role || 'inconnu'">
                      <td>
                        <span class="badge" :class="getRoleBadgeClass(r.role)">
                          {{ getRoleLabel(r.role) }}
                        </span>
                      </td>
                      <td class="text-end">
                        <span class="badge bg-secondary">{{ r.total }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-muted">Aucun utilisateur trouv\u00e9</div>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Indicateur de chargement -->
    <div v-if="isLoading" class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Chargement...</span>
      </div>
      <p class="mt-2">Chargement des statistiques en cours...</p>
    </div>

    <!-- Message d'erreur -->
    <div v-else-if="errorMessage" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ errorMessage }}
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4" v-else-if="statistics">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h4>{{ statistics.documents || 0 }}</h4>
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
                <h4>{{ statistics.utilisateurs || 0 }}</h4>
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
                <h4>{{ statistics.absences_en_attente || 0 }}</h4>
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
                <h4>{{ statistics.modeles_actifs || 0 }}</h4>
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
              <div class="col-md-4 col-lg-3 mb-3">
                <router-link to="/admin/classes" class="btn btn-outline-dark w-100">
                  <i class="fas fa-chalkboard me-2"></i>
                  Gestion des classes
                </router-link>
              </div>
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
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminDashboard',
  data() {
    return {
      statistics: {
        documents: 0,
        utilisateurs: 0,
        absences_en_attente: 0,
        modeles_actifs: 0,
        documents_par_type: [],
        utilisateurs_par_role: []
      },
      pendingAbsences: [],
      isLoading: true,
      errorMessage: '',
      lastUpdate: null,
      apiResponse: null
    }
  },
  mounted() {
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.loadStatistics()
    this.loadPendingAbsences()
  },
  methods: {
    async loadStatistics() {
      console.log('Début du chargement des statistiques...');
      this.isLoading = true;
      this.errorMessage = '';
      this.lastUpdate = new Date().toLocaleTimeString();
      
      try {
        // Appel de l'API statistiques admin (préfixe /api/ déjà géré par Axios)
        const apiUrl = 'admin/statistiques';
        console.log(`Appel de l'API: ${apiUrl}`);
        const response = await axios.get(apiUrl);
        console.log('Réponse de l\'API:', response);
        
        // Sauvegarder la réponse complète pour le débogage
        this.apiResponse = response.data;
        
        if (response.data && response.data.success) {
          console.log('Données reçues:', response.data.data);
          
          // Vérifier que les données sont bien présentes
          if (response.data.data) {
            // S'assurer que les données sont correctement formatées
            const data = response.data.data;
            this.statistics = {
              documents: data.documents !== undefined ? parseInt(data.documents) : 0,
              utilisateurs: data.utilisateurs !== undefined ? parseInt(data.utilisateurs) : 0,
              absences_en_attente: data.absences_en_attente !== undefined ? parseInt(data.absences_en_attente) : 0,
              modeles_actifs: data.modeles_actifs !== undefined ? parseInt(data.modeles_actifs) : 0,
              documents_par_type: Array.isArray(data.documents_par_type) ? data.documents_par_type : [],
              utilisateurs_par_role: Array.isArray(data.utilisateurs_par_role) ? data.utilisateurs_par_role : []
            };
            console.log('Statistiques mises à jour:', this.statistics);
            
            // Forcer la mise à jour du DOM
            this.$forceUpdate();
          } else {
            console.warn('Aucune donnée dans la réponse');
            this.errorMessage = 'Aucune donnée reçue du serveur';
          }
        } else {
          const errorMsg = response.data?.message || 'Réponse inattendue du serveur';
          console.warn('Réponse API sans succès:', errorMsg);
          this.errorMessage = `Erreur: ${errorMsg}`;
        }
      } catch (error) {
        const errorMsg = error.response?.data?.message || error.message || 'Erreur inconnue';
        console.error('Erreur lors du chargement des statistiques:', errorMsg);
        this.errorMessage = `Erreur de chargement: ${errorMsg}`;
        this.$toast.error('Erreur lors du chargement des statistiques');
      } finally {
        this.isLoading = false;
      }
    },
    loadPendingAbsences() {
      axios.get('absences?statut=en_attente')
        .then(response => {
          this.pendingAbsences = response.data.data;
        })
        .catch(error => {
          console.error('Erreur lors du chargement des absences en attente:', error);
        });
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    getDocumentTypeLabel(type) {
      const labels = {
        attestation_scolarite: 'Attestation de scolarité',
        bulletin_notes: 'Bulletin de notes',
        attestation_reussite: 'Attestation de réussite',
        emploi_temps: 'Emploi du temps',
        certificat_scolarite: 'Certificat de scolarité',
        document_administratif: 'Document administratif'
      }
      if (!type) return 'Type non défini'
      return labels[type] || type
    },
    getRoleLabel(role) {
      const labels = {
        'admin': 'Administrateur',
        'etudiant': 'Étudiant',
        'enseignant': 'Enseignant',
        'directeur': 'Directeur'
      };
      return labels[role] || role;
    },
    getRoleBadgeClass(role) {
      const classes = {
        'admin': 'bg-danger',
        'etudiant': 'bg-primary',
        'enseignant': 'bg-success',
        'directeur': 'bg-warning text-dark'
      };
      return classes[role] || 'bg-secondary';
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
