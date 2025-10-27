import { createStore } from 'vuex'

const store = createStore({
  state: {
    user: null,
    token: null,
    isLoading: false,
    notifications: []
  },

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,
    userName: (state) => state.user ? `${state.user.nom} ${state.user.prenom}` : '',
    isAdmin: (state) => state.user?.role === 'administrateur',
    isStudent: (state) => state.user?.role === 'etudiant',
    isTeacher: (state) => state.user?.role === 'enseignant',
    isDirector: (state) => state.user?.role === 'directeur'
  },

  mutations: {
    SET_USER(state, user) {
      state.user = user
    },
    
    SET_TOKEN(state, token) {
      state.token = token
    },
    
    SET_LOADING(state, loading) {
      state.isLoading = loading
    },
    
    ADD_NOTIFICATION(state, notification) {
      state.notifications.push({
        id: Date.now(),
        ...notification,
        timestamp: new Date()
      })
    },
    
    REMOVE_NOTIFICATION(state, id) {
      state.notifications = state.notifications.filter(n => n.id !== id)
    },
    
    CLEAR_NOTIFICATIONS(state) {
      state.notifications = []
    },
    
    LOGOUT(state) {
      state.user = null
      state.token = null
      state.notifications = []
    }
  },

  actions: {
    // Initialiser l'état depuis le localStorage
    initAuth({ commit }) {
      const token = localStorage.getItem('auth_token')
      const user = JSON.parse(localStorage.getItem('user') || 'null')
      
      if (token && user) {
        commit('SET_TOKEN', token)
        commit('SET_USER', user)
      }
    },

    // Connexion
    async login({ commit }, { email, password }) {
      try {
        commit('SET_LOADING', true)
        
        const response = await fetch('/api/auth/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ email, password })
        })
        
        const data = await response.json()
        
        if (data.success) {
          const { utilisateur, token } = data.data
          
          // Sauvegarder dans le localStorage
          localStorage.setItem('auth_token', token)
          localStorage.setItem('user', JSON.stringify(utilisateur))
          
          // Mettre à jour le store
          commit('SET_TOKEN', token)
          commit('SET_USER', utilisateur)
          
          return { success: true, data: utilisateur }
        } else {
          return { success: false, message: data.message }
        }
      } catch (error) {
        console.error('Erreur de connexion:', error)
        return { success: false, message: 'Erreur de connexion' }
      } finally {
        commit('SET_LOADING', false)
      }
    },

    // Inscription
    async register({ commit }, userData) {
      try {
        commit('SET_LOADING', true)
        
        const response = await fetch('/api/auth/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(userData)
        })
        
        const data = await response.json()
        
        if (data.success) {
          const { utilisateur, token } = data.data
          
          // Sauvegarder dans le localStorage
          localStorage.setItem('auth_token', token)
          localStorage.setItem('user', JSON.stringify(utilisateur))
          
          // Mettre à jour le store
          commit('SET_TOKEN', token)
          commit('SET_USER', utilisateur)
          
          return { success: true, data: utilisateur }
        } else {
          return { success: false, message: data.message, errors: data.errors }
        }
      } catch (error) {
        console.error('Erreur d\'inscription:', error)
        return { success: false, message: 'Erreur d\'inscription' }
      } finally {
        commit('SET_LOADING', false)
      }
    },

    // Déconnexion
    async logout({ commit }) {
      try {
        const token = localStorage.getItem('auth_token')
        
        if (token) {
          await fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            }
          })
        }
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error)
      } finally {
        // Nettoyer le localStorage et le store
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        commit('LOGOUT')
      }
    },

    // Mettre à jour le profil utilisateur
    async updateProfile({ commit, state }, profileData) {
      try {
        commit('SET_LOADING', true)
        
        const token = localStorage.getItem('auth_token')
        const response = await fetch('/api/auth/me', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(profileData)
        })
        
        const data = await response.json()
        
        if (data.success) {
          const updatedUser = data.data
          
          // Mettre à jour le localStorage
          localStorage.setItem('user', JSON.stringify(updatedUser))
          
          // Mettre à jour le store
          commit('SET_USER', updatedUser)
          
          return { success: true, data: updatedUser }
        } else {
          return { success: false, message: data.message }
        }
      } catch (error) {
        console.error('Erreur lors de la mise à jour:', error)
        return { success: false, message: 'Erreur lors de la mise à jour' }
      } finally {
        commit('SET_LOADING', false)
      }
    },

    // Ajouter une notification
    addNotification({ commit }, notification) {
      commit('ADD_NOTIFICATION', notification)
      
      // Auto-supprimer après 5 secondes
      setTimeout(() => {
        commit('REMOVE_NOTIFICATION', notification.id)
      }, 5000)
    },

    // Supprimer une notification
    removeNotification({ commit }, id) {
      commit('REMOVE_NOTIFICATION', id)
    },

    // Nettoyer toutes les notifications
    clearNotifications({ commit }) {
      commit('CLEAR_NOTIFICATIONS')
    }
  }
})

export default store
