<template>
  <div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="h3 mb-0">
              <i class="fas fa-tachometer-alt me-2"></i>
              Tableau de bord
            </h1>
            <p class="text-muted">Bienvenue, {{ user?.prenom }} {{ user?.nom }}</p>
          </div>
          <div>
            <span class="badge bg-primary fs-6">
              <i class="fas fa-user-tag me-1"></i>
              {{ getRoleLabel(user?.role) }}
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row mb-4" v-if="statistiques">
      <div class="col-md-3 mb-3" v-for="stat in getStatsForRole()" :key="stat.title">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <div class="rounded-circle p-3" :class="stat.bgClass">
                  <i :class="stat.icon" class="text-white"></i>
                </div>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="card-title text-muted mb-1">{{ stat.title }}</h6>
                <h3 class="mb-0">{{ stat.value }}</h3>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0">
              <i class="fas fa-bolt me-2"></i>
              Actions rapides
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Actions pour étudiant -->
              <template v-if="user?.role === 'etudiant'">
                <div class="col-md-4 mb-3">
                  <router-link to="/absences" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                    <span>Déclarer une absence</span>
                  </router-link>
                </div>
                <div class="col-md-4 mb-3">
                  <router-link to="/documents" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <span>Consulter mes documents</span>
                  </router-link>
                </div>
                <div class="col-md-4 mb-3">
                  <router-link to="/profil" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-user-edit fa-2x mb-2"></i>
                    <span>Modifier mon profil</span>
                  </router-link>
                </div>
              </template>
              
              <!-- Actions pour enseignant -->
              <template v-if="user?.role === 'enseignant'">
                <div class="col-md-6 mb-3">
                  <router-link to="/absences-validation" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <span>Valider les absences</span>
                    <small class="text-muted">{{ pendingAbsences }} en attente</small>
                  </router-link>
                </div>
                <div class="col-md-6 mb-3">
                  <router-link to="/profil" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-user-edit fa-2x mb-2"></i>
                    <span>Mon profil</span>
                  </router-link>
                </div>
              </template>
              
              <!-- Actions pour administrateur -->
              <template v-if="user?.role === 'administrateur'">
                <div class="col-md-4 mb-3">
                  <router-link to="/gestion-documents" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-file-upload fa-2x mb-2"></i>
                    <span>Gérer les documents</span>
                  </router-link>
                </div>
                <div class="col-md-4 mb-3">
                  <router-link to="/gestion-utilisateurs" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <span>Gérer les utilisateurs</span>
                  </router-link>
                </div>
                <div class="col-md-4 mb-3">
                  <router-link to="/profil" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-user-edit fa-2x mb-2"></i>
                    <span>Mon profil</span>
                  </router-link>
                </div>
              </template>
              
              <!-- Actions pour directeur -->
              <template v-if="user?.role === 'directeur'">
                <div class="col-md-4 mb-3">
                  <router-link to="/statistiques" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                    <span>Consulter les statistiques</span>
                  </router-link>
                </div>
                <div class="col-md-4 mb-3">
                  <router-link to="/rapports" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <span>Générer des rapports</span>
                  </router-link>
                </div>
                <div class="col-md-4 mb-3">
                  <router-link to="/profil" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center p-3">
                    <i class="fas fa-user-edit fa-2x mb-2"></i>
                    <span>Mon profil</span>
                  </router-link>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Activité récente -->
    <div class="row" v-if="recentActivity && recentActivity.length > 0">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0">
              <i class="fas fa-history me-2"></i>
              Activité récente
            </h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <div v-for="activity in recentActivity" :key="activity.id" class="list-group-item border-0 px-0">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <i :class="getActivityIcon(activity.type)" class="text-primary"></i>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="mb-1">{{ activity.title }}</h6>
                    <p class="text-muted mb-0">{{ activity.description }}</p>
                    <small class="text-muted">{{ formatDate(activity.created_at) }}</small>
                  </div>
                  <div class="flex-shrink-0">
                    <span :class="getActivityBadgeClass(activity.status)" class="badge">
                      {{ activity.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'Dashboard',
  setup() {
    const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
    const statistiques = ref(null);
    const recentActivity = ref([]);
    const pendingAbsences = ref(0);
    
    const getRoleLabel = (role) => {
      const labels = {
        'etudiant': 'Étudiant',
        'enseignant': 'Enseignant',
        'administrateur': 'Administrateur',
        'directeur': 'Directeur'
      };
      return labels[role] || role;
    };
    
    const getStatsForRole = () => {
      if (!statistiques.value) return [];
      
      const role = user.value?.role;
      const stats = statistiques.value;
      
      switch (role) {
        case 'etudiant':
          return [
            { title: 'Mes absences', value: stats.total_absences || 0, icon: 'fas fa-calendar-times', bgClass: 'bg-primary' },
            { title: 'Absences validées', value: stats.absences_validees || 0, icon: 'fas fa-check-circle', bgClass: 'bg-success' },
            { title: 'Mes documents', value: stats.total_documents || 0, icon: 'fas fa-file-alt', bgClass: 'bg-info' },
            { title: 'En attente', value: stats.absences_en_attente || 0, icon: 'fas fa-clock', bgClass: 'bg-warning' }
          ];
        case 'enseignant':
          return [
            { title: 'Absences à traiter', value: pendingAbsences.value, icon: 'fas fa-tasks', bgClass: 'bg-warning' },
            { title: 'Classes gérées', value: '3', icon: 'fas fa-chalkboard-teacher', bgClass: 'bg-primary' },
            { title: 'Matières enseignées', value: user.value?.enseignant?.matieres_enseignees?.length || 0, icon: 'fas fa-book', bgClass: 'bg-success' }
          ];
        case 'administrateur':
          return [
            { title: 'Documents générés', value: '0', icon: 'fas fa-file-upload', bgClass: 'bg-primary' },
            { title: 'Utilisateurs actifs', value: '0', icon: 'fas fa-users', bgClass: 'bg-success' },
            { title: 'Modèles disponibles', value: '4', icon: 'fas fa-file-code', bgClass: 'bg-info' }
          ];
        case 'directeur':
          return [
            { title: 'Total étudiants', value: '0', icon: 'fas fa-graduation-cap', bgClass: 'bg-primary' },
            { title: 'Absences ce mois', value: '0', icon: 'fas fa-calendar-times', bgClass: 'bg-warning' },
            { title: 'Documents générés', value: '0', icon: 'fas fa-file-alt', bgClass: 'bg-success' }
          ];
        default:
          return [];
      }
    };
    
    const getActivityIcon = (type) => {
      const icons = {
        'absence': 'fas fa-calendar-times',
        'document': 'fas fa-file-alt',
        'validation': 'fas fa-check-circle',
        'generation': 'fas fa-file-upload'
      };
      return icons[type] || 'fas fa-info-circle';
    };
    
    const getActivityBadgeClass = (status) => {
      const classes = {
        'en_attente': 'bg-warning',
        'validee': 'bg-success',
        'refusee': 'bg-danger',
        'generé': 'bg-info'
      };
      return classes[status] || 'bg-secondary';
    };
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };
    
    const loadDashboardData = async () => {
      try {
        const role = user.value?.role;
        let endpoint = '';
        
        switch (role) {
          case 'etudiant':
            endpoint = '/etudiant/dashboard';
            break;
          case 'enseignant':
            endpoint = '/enseignant/dashboard';
            break;
          case 'administrateur':
            endpoint = '/admin/dashboard';
            break;
          case 'directeur':
            endpoint = '/directeur/dashboard';
            break;
        }
        
        if (endpoint) {
          const response = await axios.get(endpoint);
          if (response.data.success) {
            statistiques.value = response.data.data.statistiques;
            recentActivity.value = response.data.data.recent_absences || response.data.data.recent_documents || [];
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement du tableau de bord:', error);
      }
    };
    
    onMounted(() => {
      loadDashboardData();
    });
    
    return {
      user,
      statistiques,
      recentActivity,
      pendingAbsences,
      getRoleLabel,
      getStatsForRole,
      getActivityIcon,
      getActivityBadgeClass,
      formatDate
    };
  }
};
</script>

<style scoped>
.card {
  border-radius: 10px;
}

.btn {
  border-radius: 10px;
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.rounded-circle {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.list-group-item {
  border-radius: 8px !important;
  margin-bottom: 8px;
}
</style>













