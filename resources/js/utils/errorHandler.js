// Gestionnaire d'erreurs centralisé
import axios from 'axios'

class ErrorHandler {
  constructor() {
    this.setupAxiosInterceptors()
  }

  setupAxiosInterceptors() {
    // Intercepteur de réponse pour gérer les erreurs globalement
    axios.interceptors.response.use(
      (response) => response,
      (error) => {
        this.handleError(error)
        return Promise.reject(error)
      }
    )
  }

  handleError(error) {
    console.error('Erreur API:', error)

    if (error.response) {
      // Erreur de réponse du serveur
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          this.handleUnauthorized()
          break
        case 403:
          this.handleForbidden(data?.message)
          break
        case 422:
          this.handleValidationError(data?.errors)
          break
        case 404:
          this.handleNotFound(data?.message)
          break
        case 500:
          this.handleServerError(data?.message)
          break
        default:
          this.handleGenericError(data?.message || 'Une erreur est survenue')
      }
    } else if (error.request) {
      // Erreur de réseau
      this.handleNetworkError()
    } else {
      // Autre erreur
      this.handleGenericError(error.message)
    }
  }

  handleUnauthorized() {
    // Rediriger vers la page de connexion
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    window.location.href = '/login'
  }

  handleForbidden(message) {
    this.showError(message || 'Accès refusé. Vous n\'avez pas les permissions nécessaires.')
  }

  handleValidationError(errors) {
    if (errors && typeof errors === 'object') {
      const errorMessages = Object.values(errors).flat()
      this.showError('Erreurs de validation: ' + errorMessages.join(', '))
    } else {
      this.showError('Erreurs de validation')
    }
  }

  handleNotFound(message) {
    this.showError(message || 'Ressource non trouvée')
  }

  handleServerError(message) {
    this.showError(message || 'Erreur interne du serveur. Veuillez réessayer plus tard.')
  }

  handleNetworkError() {
    this.showError('Erreur de connexion. Vérifiez votre connexion internet.')
  }

  handleGenericError(message) {
    this.showError(message || 'Une erreur inattendue est survenue')
  }

  showError(message) {
    // Utiliser le système de toast si disponible
    if (window.$toast) {
      window.$toast.error(message)
    } else {
      // Fallback vers alert
      alert(message)
    }
  }

  showSuccess(message) {
    if (window.$toast) {
      window.$toast.success(message)
    } else {
      alert(message)
    }
  }

  showWarning(message) {
    if (window.$toast) {
      window.$toast.warning(message)
    } else {
      alert(message)
    }
  }

  showInfo(message) {
    if (window.$toast) {
      window.$toast.info(message)
    } else {
      alert(message)
    }
  }
}

// Instance globale
const errorHandler = new ErrorHandler()

// Fonctions utilitaires
export const handleApiError = (error) => {
  errorHandler.handleError(error)
}

export const showSuccess = (message) => {
  errorHandler.showSuccess(message)
}

export const showError = (message) => {
  errorHandler.showError(message)
}

export const showWarning = (message) => {
  errorHandler.showWarning(message)
}

export const showInfo = (message) => {
  errorHandler.showInfo(message)
}

export default errorHandler
