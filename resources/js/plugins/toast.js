// Plugin de notifications toast pour Vue.js
import { createApp } from 'vue'

// Composant Toast
const ToastComponent = {
  template: `
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        class="toast show"
        :class="getToastClass(toast.type)"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <div class="toast-header">
          <i :class="getIconClass(toast.type)" class="me-2"></i>
          <strong class="me-auto">{{ getTitle(toast.type) }}</strong>
          <small class="text-muted">{{ formatTime(toast.timestamp) }}</small>
          <button 
            type="button" 
            class="btn-close" 
            @click="removeToast(toast.id)"
          ></button>
        </div>
        <div class="toast-body">
          {{ toast.message }}
        </div>
      </div>
    </div>
  `,
  data() {
    return {
      toasts: [],
      nextId: 1
    }
  },
  methods: {
    addToast(message, type = 'info', duration = 5000) {
      const toast = {
        id: this.nextId++,
        message,
        type,
        timestamp: new Date()
      }
      
      this.toasts.push(toast)
      
      // Auto-remove après la durée spécifiée
      if (duration > 0) {
        setTimeout(() => {
          this.removeToast(toast.id)
        }, duration)
      }
      
      return toast.id
    },
    
    removeToast(id) {
      const index = this.toasts.findIndex(toast => toast.id === id)
      if (index > -1) {
        this.toasts.splice(index, 1)
      }
    },
    
    getToastClass(type) {
      const classes = {
        success: 'bg-success text-white',
        error: 'bg-danger text-white',
        warning: 'bg-warning text-dark',
        info: 'bg-info text-white'
      }
      return classes[type] || classes.info
    },
    
    getIconClass(type) {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      }
      return icons[type] || icons.info
    },
    
    getTitle(type) {
      const titles = {
        success: 'Succès',
        error: 'Erreur',
        warning: 'Attention',
        info: 'Information'
      }
      return titles[type] || titles.info
    },
    
    formatTime(timestamp) {
      return timestamp.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}

// Plugin Vue
const ToastPlugin = {
  install(app) {
    // Créer une instance du composant toast
    const toastApp = createApp(ToastComponent)
    const toastElement = document.createElement('div')
    document.body.appendChild(toastElement)
    const toastInstance = toastApp.mount(toastElement)
    
    // Ajouter les méthodes globales
    app.config.globalProperties.$toast = {
      success: (message, duration) => toastInstance.addToast(message, 'success', duration),
      error: (message, duration) => toastInstance.addToast(message, 'error', duration),
      warning: (message, duration) => toastInstance.addToast(message, 'warning', duration),
      info: (message, duration) => toastInstance.addToast(message, 'info', duration)
    }
    
    // Méthodes de raccourci
    app.config.globalProperties.$toast.success = (message, duration) => 
      toastInstance.addToast(message, 'success', duration)
    app.config.globalProperties.$toast.error = (message, duration) => 
      toastInstance.addToast(message, 'error', duration)
    app.config.globalProperties.$toast.warning = (message, duration) => 
      toastInstance.addToast(message, 'warning', duration)
    app.config.globalProperties.$toast.info = (message, duration) => 
      toastInstance.addToast(message, 'info', duration)
  }
}

export default ToastPlugin
