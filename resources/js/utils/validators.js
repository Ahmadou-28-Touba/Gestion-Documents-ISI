// Utilitaires de validation côté client
export const validators = {
  // Validation d'email
  email: (value) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return {
      isValid: emailRegex.test(value),
      message: 'Veuillez entrer une adresse email valide'
    }
  },

  // Validation de mot de passe
  password: (value, minLength = 8) => {
    const hasMinLength = value.length >= minLength
    const hasUpperCase = /[A-Z]/.test(value)
    const hasLowerCase = /[a-z]/.test(value)
    const hasNumbers = /\d/.test(value)
    
    if (!hasMinLength) {
      return {
        isValid: false,
        message: `Le mot de passe doit contenir au moins ${minLength} caractères`
      }
    }
    
    if (!hasUpperCase) {
      return {
        isValid: false,
        message: 'Le mot de passe doit contenir au moins une majuscule'
      }
    }
    
    if (!hasLowerCase) {
      return {
        isValid: false,
        message: 'Le mot de passe doit contenir au moins une minuscule'
      }
    }
    
    if (!hasNumbers) {
      return {
        isValid: false,
        message: 'Le mot de passe doit contenir au moins un chiffre'
      }
    }
    
    return { isValid: true, message: '' }
  },

  // Validation de nom/prénom
  name: (value, fieldName = 'Nom') => {
    if (!value || value.trim().length === 0) {
      return {
        isValid: false,
        message: `${fieldName} est requis`
      }
    }
    
    if (value.trim().length < 2) {
      return {
        isValid: false,
        message: `${fieldName} doit contenir au moins 2 caractères`
      }
    }
    
    if (!/^[a-zA-ZÀ-ÿ\s'-]+$/.test(value)) {
      return {
        isValid: false,
        message: `${fieldName} ne peut contenir que des lettres, espaces, apostrophes et tirets`
      }
    }
    
    return { isValid: true, message: '' }
  },

  // Validation de date
  date: (value, options = {}) => {
    if (!value) {
      return {
        isValid: false,
        message: 'Date requise'
      }
    }
    
    const date = new Date(value)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    
    if (isNaN(date.getTime())) {
      return {
        isValid: false,
        message: 'Format de date invalide'
      }
    }
    
    if (options.required && !value) {
      return {
        isValid: false,
        message: 'Date requise'
      }
    }
    
    if (options.minDate && date < new Date(options.minDate)) {
      return {
        isValid: false,
        message: `La date doit être postérieure au ${new Date(options.minDate).toLocaleDateString('fr-FR')}`
      }
    }
    
    if (options.maxDate && date > new Date(options.maxDate)) {
      return {
        isValid: false,
        message: `La date doit être antérieure au ${new Date(options.maxDate).toLocaleDateString('fr-FR')}`
      }
    }
    
    if (options.futureOnly && date < today) {
      return {
        isValid: false,
        message: 'La date doit être dans le futur'
      }
    }
    
    return { isValid: true, message: '' }
  },

  // Validation de période (date début < date fin)
  dateRange: (startDate, endDate) => {
    if (!startDate || !endDate) {
      return {
        isValid: false,
        message: 'Les deux dates sont requises'
      }
    }
    
    const start = new Date(startDate)
    const end = new Date(endDate)
    
    if (start >= end) {
      return {
        isValid: false,
        message: 'La date de fin doit être postérieure à la date de début'
      }
    }
    
    return { isValid: true, message: '' }
  },

  // Validation de fichier
  file: (file, options = {}) => {
    if (!file) {
      if (options.required) {
        return {
          isValid: false,
          message: 'Fichier requis'
        }
      }
      return { isValid: true, message: '' }
    }
    
    // Vérifier la taille
    if (options.maxSize && file.size > options.maxSize) {
      const maxSizeMB = options.maxSize / (1024 * 1024)
      return {
        isValid: false,
        message: `Le fichier ne doit pas dépasser ${maxSizeMB}MB`
      }
    }
    
    // Vérifier le type
    if (options.allowedTypes && options.allowedTypes.length > 0) {
      const fileExtension = file.name.split('.').pop().toLowerCase()
      const mimeType = file.type
      
      const isAllowed = options.allowedTypes.some(type => {
        if (type.startsWith('.')) {
          return fileExtension === type.substring(1)
        }
        return mimeType.startsWith(type)
      })
      
      if (!isAllowed) {
        return {
          isValid: false,
          message: `Type de fichier non autorisé. Types acceptés: ${options.allowedTypes.join(', ')}`
        }
      }
    }
    
    return { isValid: true, message: '' }
  },

  // Validation de JSON
  json: (value, fieldName = 'JSON') => {
    if (!value) {
      return { isValid: true, message: '' }
    }
    
    try {
      JSON.parse(value)
      return { isValid: true, message: '' }
    } catch (error) {
      return {
        isValid: false,
        message: `${fieldName} invalide: ${error.message}`
      }
    }
  },

  // Validation de numéro de téléphone
  phone: (value) => {
    if (!value) {
      return { isValid: true, message: '' }
    }
    
    const phoneRegex = /^(\+33|0)[1-9](\d{8})$/
    return {
      isValid: phoneRegex.test(value.replace(/\s/g, '')),
      message: 'Numéro de téléphone invalide'
    }
  },

  // Validation de numéro étudiant
  studentNumber: (value) => {
    if (!value) {
      return {
        isValid: false,
        message: 'Numéro étudiant requis'
      }
    }
    
    const studentNumberRegex = /^[A-Z]{2,3}\d{4,6}$/
    return {
      isValid: studentNumberRegex.test(value),
      message: 'Format de numéro étudiant invalide (ex: ETU2024001)'
    }
  },

  // Validation de matricule enseignant
  teacherMatricule: (value) => {
    if (!value) {
      return {
        isValid: false,
        message: 'Matricule requis'
      }
    }
    
    const matriculeRegex = /^[A-Z]{2,3}\d{4,6}$/
    return {
      isValid: matriculeRegex.test(value),
      message: 'Format de matricule invalide (ex: ENS2024001)'
    }
  }
}

// Classe de validation de formulaire
export class FormValidator {
  constructor() {
    this.errors = {}
    this.rules = {}
  }

  // Ajouter une règle de validation
  addRule(field, validator, options = {}) {
    if (!this.rules[field]) {
      this.rules[field] = []
    }
    this.rules[field].push({ validator, options })
  }

  // Valider un champ
  validateField(field, value) {
    const fieldRules = this.rules[field] || []
    const errors = []

    for (const rule of fieldRules) {
      const result = rule.validator(value, rule.options)
      if (!result.isValid) {
        errors.push(result.message)
      }
    }

    if (errors.length > 0) {
      this.errors[field] = errors[0] // Prendre la première erreur
      return false
    } else {
      delete this.errors[field]
      return true
    }
  }

  // Valider tout le formulaire
  validateForm(data) {
    this.errors = {}
    let isValid = true

    for (const field in this.rules) {
      const fieldValid = this.validateField(field, data[field])
      if (!fieldValid) {
        isValid = false
      }
    }

    return isValid
  }

  // Obtenir les erreurs
  getErrors() {
    return this.errors
  }

  // Obtenir l'erreur d'un champ spécifique
  getFieldError(field) {
    return this.errors[field]
  }

  // Vérifier si un champ a des erreurs
  hasFieldError(field) {
    return !!this.errors[field]
  }

  // Nettoyer les erreurs
  clearErrors() {
    this.errors = {}
  }
}

export default validators
