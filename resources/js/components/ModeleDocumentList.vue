<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Gestion des Modèles de Documents</h3>
            <button class="btn btn-primary" @click="showCreateModal = true">
              <i class="fas fa-plus"></i> Nouveau Modèle
            </button>
          </div>
          
          <div class="card-body">
            <!-- Filtres et recherche -->
            <div class="row mb-3">
              <div class="col-md-4">
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Rechercher..." 
                  v-model="searchQuery"
                  @input="searchModeles"
                >
              </div>
              <div class="col-md-3">
                <select class="form-control" v-model="filters.type_document" @change="loadModeles">
                  <option value="">Tous les types</option>
                  <option v-for="type in documentTypes" :key="type" :value="type">
                    {{ type }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <select class="form-control" v-model="filters.est_actif" @change="loadModeles">
                  <option value="">Tous</option>
                  <option value="1">Actifs</option>
                  <option value="0">Inactifs</option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary" @click="resetFilters">
                  <i class="fas fa-times"></i> Reset
                </button>
              </div>
            </div>

            <!-- Tableau des modèles -->
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Documents</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="modele in modeles.data" :key="modele.id">
                    <td>
                      <strong>{{ modele.nom }}</strong>
                      <br>
                      <small class="text-muted">{{ modele.type_document }}</small>
                    </td>
                    <td>
                      <span class="badge badge-info">{{ modele.type_document }}</span>
                    </td>
                    <td>
                      <span class="text-truncate d-inline-block" style="max-width: 200px;" :title="modele.description">
                        {{ modele.description || 'Aucune description' }}
                      </span>
                    </td>
                    <td>
                      <span 
                        class="badge" 
                        :class="modele.est_actif ? 'badge-success' : 'badge-warning'"
                      >
                        {{ modele.est_actif ? 'Actif' : 'Inactif' }}
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-primary">{{ modele.documents_count || 0 }}</span>
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button 
                          class="btn btn-sm btn-outline-primary" 
                          @click="viewModele(modele)"
                          title="Voir"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-warning" 
                          @click="editModele(modele)"
                          title="Modifier"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-secondary" 
                          @click="duplicateModele(modele)"
                          title="Dupliquer"
                        >
                          <i class="fas fa-copy"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-info" 
                          @click="toggleActivation(modele)"
                          :title="modele.est_actif ? 'Désactiver' : 'Activer'"
                        >
                          <i :class="modele.est_actif ? 'fas fa-pause' : 'fas fa-play'"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-danger" 
                          @click="deleteModele(modele)"
                          title="Supprimer"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <nav v-if="modeles.last_page > 1">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: modeles.current_page === 1 }">
                  <button class="page-link" @click="changePage(modeles.current_page - 1)">
                    Précédent
                  </button>
                </li>
                <li 
                  v-for="page in getPageNumbers()" 
                  :key="page" 
                  class="page-item" 
                  :class="{ active: page === modeles.current_page }"
                >
                  <button class="page-link" @click="changePage(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: modeles.current_page === modeles.last_page }">
                  <button class="page-link" @click="changePage(modeles.current_page + 1)">
                    Suivant
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de création/édition -->
    <div class="modal fade" :class="{ show: showCreateModal || showEditModal }" :style="{ display: showCreateModal || showEditModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ showCreateModal ? 'Nouveau Modèle' : 'Modifier Modèle' }}
            </h5>
            <button type="button" class="close" @click="closeModals">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveModele">
              <div class="form-group">
                <label>Nom du modèle *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="modeleForm.nom" 
                  required
                  placeholder="Ex: Attestation de scolarité"
                >
              </div>
              <div class="form-group">
                <label>Type de document *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="modeleForm.type_document" 
                  required
                  placeholder="Ex: attestation, certificat, diplome"
                >
              </div>
              <div class="form-group">
                <label>Chemin du modèle *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="modeleForm.chemin_modele" 
                  required
                  placeholder="Ex: templates/attestation.docx"
                >
                <small class="form-text text-muted">
                  Chemin relatif vers le fichier modèle dans le dossier storage
                </small>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea 
                  class="form-control" 
                  rows="3" 
                  v-model="modeleForm.description"
                  placeholder="Décrivez ce modèle de document..."
                ></textarea>
              </div>
              <div class="form-group">
                <label>Champs requis (JSON)</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="modeleForm.champs_requis_json"
                  placeholder='["nom", "prenom", "date_naissance", "filiere"]'
                ></textarea>
                <small class="form-text text-muted">
                  Liste des champs requis au format JSON
                </small>
              </div>
              <div class="form-group">
                <div class="form-check">
                  <input 
                    type="checkbox" 
                    class="form-check-input" 
                    v-model="modeleForm.est_actif"
                    id="est_actif"
                  >
                  <label class="form-check-label" for="est_actif">
                    Modèle actif
                  </label>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModals">
              Annuler
            </button>
            <button type="button" class="btn btn-primary" @click="saveModele">
              {{ showCreateModal ? 'Créer' : 'Modifier' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de visualisation -->
    <div class="modal fade" :class="{ show: showViewModal }" :style="{ display: showViewModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Détails du Modèle</h5>
            <button type="button" class="close" @click="showViewModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body" v-if="selectedModele">
            <div class="row">
              <div class="col-md-6">
                <h6>Informations générales</h6>
                <p><strong>Nom:</strong> {{ selectedModele.nom }}</p>
                <p><strong>Type:</strong> {{ selectedModele.type_document }}</p>
                <p><strong>Chemin:</strong> {{ selectedModele.chemin_modele }}</p>
                <p><strong>Statut:</strong> 
                  <span class="badge" :class="selectedModele.est_actif ? 'badge-success' : 'badge-warning'">
                    {{ selectedModele.est_actif ? 'Actif' : 'Inactif' }}
                  </span>
                </p>
                <p><strong>Documents générés:</strong> {{ selectedModele.documents_count || 0 }}</p>
              </div>
              <div class="col-md-6">
                <h6>Description</h6>
                <p v-if="selectedModele.description">{{ selectedModele.description }}</p>
                <p v-else class="text-muted">Aucune description</p>
              </div>
            </div>
            <div class="row mt-3" v-if="selectedModele.champs_requis">
              <div class="col-12">
                <h6>Champs requis</h6>
                <div class="row">
                  <div 
                    v-for="champ in selectedModele.champs_requis" 
                    :key="champ" 
                    class="col-md-3 mb-2"
                  >
                    <span class="badge badge-info">{{ champ }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="row mt-3" v-if="selectedModele.statistiques">
              <div class="col-12">
                <h6>Statistiques</h6>
                <div class="row">
                  <div class="col-md-4">
                    <div class="card bg-light">
                      <div class="card-body text-center">
                        <h5>{{ selectedModele.statistiques.total_documents }}</h5>
                        <p class="mb-0">Total documents</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card bg-light">
                      <div class="card-body text-center">
                        <h5>{{ selectedModele.statistiques.documents_ce_mois }}</h5>
                        <p class="mb-0">Ce mois</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card bg-light">
                      <div class="card-body text-center">
                        <h5>{{ formatDate(selectedModele.statistiques.derniere_utilisation) }}</h5>
                        <p class="mb-0">Dernière utilisation</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showViewModal = false">
              Fermer
            </button>
            <button type="button" class="btn btn-primary" @click="editModele(selectedModele)">
              <i class="fas fa-edit"></i> Modifier
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
  name: 'ModeleDocumentList',
  data() {
    return {
      modeles: { data: [], current_page: 1, last_page: 1 },
      documentTypes: [],
      searchQuery: '',
      filters: {
        type_document: '',
        est_actif: ''
      },
      showCreateModal: false,
      showEditModal: false,
      showViewModal: false,
      selectedModele: null,
      modeleForm: {
        nom: '',
        type_document: '',
        chemin_modele: '',
        description: '',
        champs_requis_json: '',
        est_actif: true
      }
    }
  },
  mounted() {
    // Auth header
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.loadModeles()
    this.loadDocumentTypes()
  },
  methods: {
    async loadModeles() {
      try {
        const params = new URLSearchParams()
        if (this.filters.type_document) params.append('type_document', this.filters.type_document)
        if (this.filters.est_actif !== '') params.append('est_actif', this.filters.est_actif)
        if (this.searchQuery) params.append('q', this.searchQuery)
        
        const response = await axios.get(`admin/modeles?${params.toString()}`)
        this.modeles = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des modèles:', error)
        this.$toast.error('Erreur lors du chargement des modèles')
      }
    },
    
    async loadDocumentTypes() {
      try {
        const response = await axios.get('documents/types')
        this.documentTypes = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des types:', error)
      }
    },
    
    searchModeles() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.loadModeles()
      }, 500)
    },
    
    resetFilters() {
      this.filters = { type_document: '', est_actif: '' }
      this.searchQuery = ''
      this.loadModeles()
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.modeles.last_page) {
        this.loadModeles()
      }
    },
    
    getPageNumbers() {
      const pages = []
      const start = Math.max(1, this.modeles.current_page - 2)
      const end = Math.min(this.modeles.last_page, start + 4)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    },
    
    viewModele(modele) {
      this.selectedModele = modele
      this.showViewModal = true
    },
    
    editModele(modele) {
      this.selectedModele = modele
      this.modeleForm = {
        nom: modele.nom,
        type_document: modele.type_document,
        chemin_modele: modele.chemin_modele,
        description: modele.description || '',
        champs_requis_json: JSON.stringify(modele.champs_requis || [], null, 2),
        est_actif: modele.est_actif
      }
      this.showEditModal = true
    },
    
    async saveModele() {
      try {
        const formData = { ...this.modeleForm }
        if (formData.champs_requis_json) {
          try {
            formData.champs_requis = JSON.parse(formData.champs_requis_json)
          } catch (e) {
            this.$toast.error('Format JSON invalide pour les champs requis')
            return
          }
        }
        delete formData.champs_requis_json
        
        if (this.showCreateModal) {
          await axios.post('admin/modeles', formData)
          this.$toast.success('Modèle créé avec succès')
        } else {
          await axios.put(`admin/modeles/${this.selectedModele.id}`, formData)
          this.$toast.success('Modèle modifié avec succès')
        }
        
        this.closeModals()
        this.loadModeles()
      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        this.$toast.error('Erreur lors de la sauvegarde')
      }
    },
    
    async duplicateModele(modele) {
      try {
        await axios.post(`admin/modeles/${modele.id}/dupliquer`, {
          nom: modele.nom + '_copie'
        })
        this.$toast.success('Modèle dupliqué avec succès')
        this.loadModeles()
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
        this.$toast.error('Erreur lors de la duplication')
      }
    },
    
    async toggleActivation(modele) {
      try {
        const action = modele.est_actif ? 'desactiver' : 'activer'
        await axios.post(`admin/modeles/${modele.id}/${action}`)
        this.$toast.success(`Modèle ${action === 'desactiver' ? 'désactivé' : 'activé'} avec succès`)
        this.loadModeles()
      } catch (error) {
        console.error('Erreur lors du changement de statut:', error)
        this.$toast.error('Erreur lors du changement de statut')
      }
    },
    
    async deleteModele(modele) {
      if (confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')) {
        try {
          await axios.delete(`admin/modeles/${modele.id}`)
          this.$toast.success('Modèle supprimé avec succès')
          this.loadModeles()
        } catch (error) {
          console.error('Erreur lors de la suppression:', error)
          this.$toast.error('Erreur lors de la suppression')
        }
      }
    },
    
    closeModals() {
      this.showCreateModal = false
      this.showEditModal = false
      this.showViewModal = false
      this.selectedModele = null
      this.modeleForm = {
        nom: '',
        type_document: '',
        chemin_modele: '',
        description: '',
        champs_requis_json: '',
        est_actif: true
      }
    },
    
    formatDate(date) {
      if (!date) return 'Jamais'
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}

.btn-group .btn {
  margin-right: 2px;
}

.table th {
  border-top: none;
}

.badge {
  font-size: 0.8em;
}

.card.bg-light {
  border: 1px solid #dee2e6;
}
</style>
