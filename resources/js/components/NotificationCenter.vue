<template>
  <div class="notification-center">
    <!-- Bouton de notification avec badge -->
    <div class="dropdown">
      <button 
        class="btn btn-outline-light position-relative" 
        type="button" 
        data-bs-toggle="dropdown"
        aria-expanded="false"
      >
        <i class="fas fa-bell"></i>
        <span 
          v-if="unreadCount > 0" 
          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
        >
          {{ unreadCount }}
        </span>
      </button>
      
      <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
        <li class="dropdown-header d-flex justify-content-between align-items-center">
          <span>Notifications</span>
          <div>
            <button 
              v-if="unreadCount > 0"
              class="btn btn-sm btn-outline-primary me-2" 
              @click="marquerToutesCommeLues"
            >
              Tout marquer comme lu
            </button>
            <button 
              class="btn btn-sm btn-outline-danger" 
              @click="supprimerToutes"
            >
              Tout supprimer
            </button>
          </div>
        </li>
        
        <li v-if="notifications.length === 0" class="dropdown-item text-muted text-center">
          Aucune notification
        </li>
        
        <li v-else>
          <div class="notification-list" style="max-height: 400px; overflow-y: auto;">
            <div 
              v-for="notification in notifications" 
              :key="notification.id"
              class="dropdown-item notification-item"
              :class="{ 'notification-unread': !notification.lue }"
              @click="voirNotification(notification)"
            >
              <div class="d-flex align-items-start">
                <div class="notification-icon me-3">
                  <i :class="getNotificationIcon(notification.type)" :style="{ color: getNotificationColor(notification.type) }"></i>
                </div>
                <div class="notification-content flex-grow-1">
                  <div class="notification-title fw-bold">{{ notification.titre }}</div>
                  <div class="notification-message text-muted small">{{ notification.message }}</div>
                  <div class="notification-time small text-muted">{{ formatTime(notification.created_at) }}</div>
                </div>
                <div class="notification-actions">
                  <button 
                    class="btn btn-sm btn-outline-danger" 
                    @click.stop="supprimerNotification(notification.id)"
                    title="Supprimer"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </li>
        
        <li class="dropdown-footer text-center">
          <router-link to="/notifications" class="btn btn-sm btn-primary">
            Voir toutes les notifications
          </router-link>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'NotificationCenter',
  data() {
    return {
      notifications: [],
      unreadCount: 0,
      loading: false
    }
  },
  mounted() {
    this.loadNotifications()
    this.startPolling()
  },
  beforeUnmount() {
    this.stopPolling()
  },
  methods: {
    async loadNotifications() {
      try {
        this.loading = true
        const response = await axios.get('/api/notifications?per_page=10')
        this.notifications = response.data.data.data
        this.updateUnreadCount()
      } catch (error) {
        console.error('Erreur lors du chargement des notifications:', error)
      } finally {
        this.loading = false
      }
    },
    
    async loadStatistics() {
      try {
        const response = await axios.get('/api/notifications/statistiques')
        this.unreadCount = response.data.data.non_lues || 0
      } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error)
      }
    },
    
    async voirNotification(notification) {
      try {
        // Marquer comme lue si ce n'est pas déjà fait
        if (!notification.lue) {
          await axios.post(`/api/notifications/${notification.id}/marquer-lue`)
          notification.lue = true
          this.updateUnreadCount()
        }
        
        // Rediriger selon le type de notification
        this.handleNotificationAction(notification)
      } catch (error) {
        console.error('Erreur lors de la lecture de la notification:', error)
      }
    },
    
    handleNotificationAction(notification) {
      const donnees = notification.donnees || {}
      
      switch (notification.type) {
        case 'absence_declaree':
        case 'absence_validee':
        case 'absence_refusee':
          this.$router.push('/absences-list')
          break
        case 'document_genere':
          this.$router.push('/documents-list')
          break
        case 'nouvel_utilisateur':
          this.$router.push('/admin-users')
          break
        default:
          // Afficher les détails de la notification
          this.$toast.info(notification.message, notification.titre)
      }
    },
    
    async marquerToutesCommeLues() {
      try {
        await axios.post('/api/notifications/marquer-toutes-lues')
        this.notifications.forEach(notification => {
          notification.lue = true
        })
        this.updateUnreadCount()
        this.$toast.success('Toutes les notifications ont été marquées comme lues')
      } catch (error) {
        console.error('Erreur lors du marquage:', error)
        this.$toast.error('Erreur lors du marquage des notifications')
      }
    },
    
    async supprimerNotification(notificationId) {
      if (confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
        try {
          await axios.delete(`/api/notifications/${notificationId}`)
          this.notifications = this.notifications.filter(n => n.id !== notificationId)
          this.updateUnreadCount()
          this.$toast.success('Notification supprimée')
        } catch (error) {
          console.error('Erreur lors de la suppression:', error)
          this.$toast.error('Erreur lors de la suppression')
        }
      }
    },
    
    async supprimerToutes() {
      if (confirm('Êtes-vous sûr de vouloir supprimer toutes les notifications ?')) {
        try {
          await axios.delete('/api/notifications/toutes')
          this.notifications = []
          this.unreadCount = 0
          this.$toast.success('Toutes les notifications ont été supprimées')
        } catch (error) {
          console.error('Erreur lors de la suppression:', error)
          this.$toast.error('Erreur lors de la suppression')
        }
      }
    },
    
    updateUnreadCount() {
      this.unreadCount = this.notifications.filter(n => !n.lue).length
    },
    
    formatTime(date) {
      const now = new Date()
      const notificationDate = new Date(date)
      const diffInMinutes = Math.floor((now - notificationDate) / (1000 * 60))
      
      if (diffInMinutes < 1) return 'À l\'instant'
      if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`
      if (diffInMinutes < 1440) return `Il y a ${Math.floor(diffInMinutes / 60)}h`
      return notificationDate.toLocaleDateString('fr-FR')
    },
    
    getNotificationIcon(type) {
      const icons = {
        'absence_declaree': 'fas fa-calendar-times',
        'absence_validee': 'fas fa-check-circle',
        'absence_refusee': 'fas fa-times-circle',
        'document_genere': 'fas fa-file-alt',
        'nouvel_utilisateur': 'fas fa-user-plus',
        'systeme': 'fas fa-cog',
        'info': 'fas fa-info-circle',
        'warning': 'fas fa-exclamation-triangle',
        'error': 'fas fa-exclamation-circle'
      }
      return icons[type] || 'fas fa-bell'
    },
    
    getNotificationColor(type) {
      const colors = {
        'absence_declaree': '#ffc107',
        'absence_validee': '#28a745',
        'absence_refusee': '#dc3545',
        'document_genere': '#17a2b8',
        'nouvel_utilisateur': '#007bff',
        'systeme': '#6c757d',
        'info': '#17a2b8',
        'warning': '#ffc107',
        'error': '#dc3545'
      }
      return colors[type] || '#6c757d'
    },
    
    startPolling() {
      // Vérifier les nouvelles notifications toutes les 30 secondes
      this.pollingInterval = setInterval(() => {
        this.loadStatistics()
      }, 30000)
    },
    
    stopPolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval)
      }
    }
  }
}
</script>

<style scoped>
.notification-dropdown {
  width: 400px;
  max-height: 500px;
}

.notification-item {
  border-bottom: 1px solid #dee2e6;
  cursor: pointer;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: #f8f9fa;
}

.notification-unread {
  background-color: #e3f2fd;
  border-left: 3px solid #2196f3;
}

.notification-icon {
  font-size: 1.2em;
  width: 20px;
  text-align: center;
}

.notification-content {
  min-width: 0;
}

.notification-title {
  font-size: 0.9em;
  line-height: 1.2;
  margin-bottom: 2px;
}

.notification-message {
  font-size: 0.8em;
  line-height: 1.3;
  margin-bottom: 4px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-time {
  font-size: 0.75em;
}

.notification-actions {
  margin-left: 8px;
}

.dropdown-footer {
  padding: 10px;
  border-top: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.badge {
  font-size: 0.7em;
  min-width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
