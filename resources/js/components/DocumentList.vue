<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Gestion des Documents</h3>
            <button class="btn btn-primary" @click="showCreateModal = true">
              <i class="fas fa-plus"></i> Nouveau Document
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
                  @input="searchDocuments"
                >
              </div>
              <div class="col-md-3">
                <select class="form-control" v-model="filters.type" @change="loadDocuments">
                  <option value="">Tous les types</option>
                  <option v-for="type in documentTypes" :key="type" :value="type">
                    {{ type }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <select class="form-control" v-model="filters.est_public" @change="loadDocuments">
                  <option value="">Tous</option>
                  <option value="1">Publics</option>
                  <option value="0">Archivés</option>
                </select>
              </div>
              <div class="col-md-2">
                <button class="btn btn-outline-secondary" @click="resetFilters">
                  <i class="fas fa-times"></i> Reset
                </button>
              </div>
            </div>

            <!-- Tableau des documents -->
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Utilisateur</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Taille</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="document in documents.data" :key="document.id">
                    <td>{{ document.nom }}</td>
                    <td>
                      <span class="badge badge-info">{{ document.type }}</span>
                    </td>
                    <td>{{ document.utilisateur.nom }} {{ document.utilisateur.prenom }}</td>
                    <td>{{ formatDate(document.date_generation) }}</td>
                    <td>
                      <span 
                        class="badge" 
                        :class="document.est_public ? 'badge-success' : 'badge-warning'"
                      >
                        {{ document.est_public ? 'Public' : 'Archivé' }}
                      </span>
                    </td>
                    <td>{{ formatFileSize(document.taille_fichier) }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button 
                          class="btn btn-sm btn-outline-primary" 
                          @click="viewDocument(document)"
                          title="Voir"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-success" 
                          @click="downloadDocument(document)"
                          title="Télécharger"
                        >
                          <i class="fas fa-download"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-warning" 
                          @click="editDocument(document)"
                          title="Modifier"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-secondary" 
                          @click="duplicateDocument(document)"
                          title="Dupliquer"
                        >
                          <i class="fas fa-copy"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-info" 
                          @click="toggleArchive(document)"
                          :title="document.est_public ? 'Archiver' : 'Désarchiver'"
                        >
                          <i :class="document.est_public ? 'fas fa-archive' : 'fas fa-box-open'"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-danger" 
                          @click="deleteDocument(document)"
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
            <nav v-if="documents.last_page > 1">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: documents.current_page === 1 }">
                  <button class="page-link" @click="changePage(documents.current_page - 1)">
                    Précédent
                  </button>
                </li>
                <li 
                  v-for="page in getPageNumbers()" 
                  :key="page" 
                  class="page-item" 
                  :class="{ active: page === documents.current_page }"
                >
                  <button class="page-link" @click="changePage(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: documents.current_page === documents.last_page }">
                  <button class="page-link" @click="changePage(documents.current_page + 1)">
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
              {{ showCreateModal ? 'Nouveau Document' : 'Modifier Document' }}
            </h5>
            <button type="button" class="close" @click="closeModals">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveDocument">
              <div class="form-group">
                <label>Nom du document</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="documentForm.nom" 
                  required
                >
              </div>
              <div class="form-group">
                <label>Type</label>
                <select class="form-control" v-model="documentForm.type" required>
                  <option value="">Sélectionner un type</option>
                  <option v-for="type in documentTypes" :key="type" :value="type">
                    {{ type }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Modèle de document</label>
                <select class="form-control" v-model="documentForm.modele_document_id" required>
                  <option value="">Sélectionner un modèle</option>
                  <option v-for="modele in modeles" :key="modele.id" :value="modele.id">
                    {{ modele.nom }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <div class="form-check">
                  <input 
                    type="checkbox" 
                    class="form-check-input" 
                    v-model="documentForm.est_public"
                    id="est_public"
                  >
                  <label class="form-check-label" for="est_public">
                    Document public
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label>Données (JSON)</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="documentForm.donnees_json"
                  placeholder='{"champ1": "valeur1", "champ2": "valeur2"}'
                ></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModals">
              Annuler
            </button>
            <button type="button" class="btn btn-primary" @click="saveDocument">
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
            <h5 class="modal-title">Détails du Document</h5>
            <button type="button" class="close" @click="showViewModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body" v-if="selectedDocument">
            <div class="row">
              <div class="col-md-6">
                <h6>Informations générales</h6>
                <p><strong>Nom:</strong> {{ selectedDocument.nom }}</p>
                <p><strong>Type:</strong> {{ selectedDocument.type }}</p>
                <p><strong>Utilisateur:</strong> {{ selectedDocument.utilisateur.nom }} {{ selectedDocument.utilisateur.prenom }}</p>
                <p><strong>Date de génération:</strong> {{ formatDate(selectedDocument.date_generation) }}</p>
                <p><strong>Statut:</strong> 
                  <span class="badge" :class="selectedDocument.est_public ? 'badge-success' : 'badge-warning'">
                    {{ selectedDocument.est_public ? 'Public' : 'Archivé' }}
                  </span>
                </p>
              </div>
              <div class="col-md-6">
                <h6>Informations techniques</h6>
                <p><strong>Taille:</strong> {{ formatFileSize(selectedDocument.taille_fichier) }}</p>
                <p><strong>Chemin:</strong> {{ selectedDocument.chemin_fichier }}</p>
                <p><strong>Modèle:</strong> {{ selectedDocument.modele_document?.nom || 'N/A' }}</p>
              </div>
            </div>
            <div class="row mt-3" v-if="selectedDocument.donnees">
              <div class="col-12">
                <h6>Données</h6>
                <pre class="bg-light p-3">{{ JSON.stringify(selectedDocument.donnees, null, 2) }}</pre>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showViewModal = false">
              Fermer
            </button>
            <button type="button" class="btn btn-primary" @click="downloadDocument(selectedDocument)">
              <i class="fas fa-download"></i> Télécharger
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
  name: 'DocumentList',
  data() {
    return {
      documents: { data: [], current_page: 1, last_page: 1 },
      documentTypes: [],
      modeles: [],
      searchQuery: '',
      filters: {
        type: '',
        est_public: ''
      },
      showCreateModal: false,
      showEditModal: false,
      showViewModal: false,
      selectedDocument: null,
      documentForm: {
        nom: '',
        type: '',
        modele_document_id: '',
        est_public: true,
        donnees_json: ''
      }
    }
  },
  mounted() {
    this.loadDocuments()
    this.loadDocumentTypes()
    this.loadModeles()
  },
  methods: {
    async loadDocuments() {
      try {
        const params = new URLSearchParams()
        if (this.filters.type) params.append('type', this.filters.type)
        if (this.filters.est_public !== '') params.append('est_public', this.filters.est_public)
        if (this.searchQuery) params.append('q', this.searchQuery)
        
        const response = await axios.get(`/api/documents?${params.toString()}`)
        this.documents = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des documents:', error)
        this.$toast.error('Erreur lors du chargement des documents')
      }
    },
    
    async loadDocumentTypes() {
      try {
        const response = await axios.get('/api/documents/types')
        this.documentTypes = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des types:', error)
      }
    },
    
    async loadModeles() {
      try {
        const response = await axios.get('/api/admin/modeles')
        this.modeles = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des modèles:', error)
      }
    },
    
    searchDocuments() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.loadDocuments()
      }, 500)
    },
    
    resetFilters() {
      this.filters = { type: '', est_public: '' }
      this.searchQuery = ''
      this.loadDocuments()
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.documents.last_page) {
        this.loadDocuments()
      }
    },
    
    getPageNumbers() {
      const pages = []
      const start = Math.max(1, this.documents.current_page - 2)
      const end = Math.min(this.documents.last_page, start + 4)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    },
    
    viewDocument(document) {
      this.selectedDocument = document
      this.showViewModal = true
    },
    
    editDocument(document) {
      this.selectedDocument = document
      this.documentForm = {
        nom: document.nom,
        type: document.type,
        modele_document_id: document.modele_document_id,
        est_public: document.est_public,
        donnees_json: JSON.stringify(document.donnees || {}, null, 2)
      }
      this.showEditModal = true
    },
    
    async saveDocument() {
      try {
        const formData = { ...this.documentForm }
        if (formData.donnees_json) {
          try {
            formData.donnees = JSON.parse(formData.donnees_json)
          } catch (e) {
            this.$toast.error('Format JSON invalide pour les données')
            return
          }
        }
        delete formData.donnees_json
        
        if (this.showCreateModal) {
          await axios.post('/api/documents', formData)
          this.$toast.success('Document créé avec succès')
        } else {
          await axios.put(`/api/documents/${this.selectedDocument.id}`, formData)
          this.$toast.success('Document modifié avec succès')
        }
        
        this.closeModals()
        this.loadDocuments()
      } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        this.$toast.error('Erreur lors de la sauvegarde')
      }
    },
    
    async downloadDocument(document) {
      try {
        const response = await axios.get(`/api/documents/${document.id}/telecharger`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `${document.nom}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        this.$toast.success('Téléchargement démarré')
      } catch (error) {
        console.error('Erreur lors du téléchargement:', error)
        this.$toast.error('Erreur lors du téléchargement')
      }
    },
    
    async duplicateDocument(document) {
      try {
        await axios.post(`/api/documents/${document.id}/dupliquer`, {
          nom: document.nom + '_copie'
        })
        this.$toast.success('Document dupliqué avec succès')
        this.loadDocuments()
      } catch (error) {
        console.error('Erreur lors de la duplication:', error)
        this.$toast.error('Erreur lors de la duplication')
      }
    },
    
    async toggleArchive(document) {
      try {
        const action = document.est_public ? 'archiver' : 'desarchiver'
        await axios.post(`/api/documents/${document.id}/${action}`)
        this.$toast.success(`Document ${action === 'archiver' ? 'archivé' : 'désarchivé'} avec succès`)
        this.loadDocuments()
      } catch (error) {
        console.error('Erreur lors de l\'archivage:', error)
        this.$toast.error('Erreur lors de l\'archivage')
      }
    },
    
    async deleteDocument(document) {
      if (confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) {
        try {
          await axios.delete(`/api/documents/${document.id}`)
          this.$toast.success('Document supprimé avec succès')
          this.loadDocuments()
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
      this.selectedDocument = null
      this.documentForm = {
        nom: '',
        type: '',
        modele_document_id: '',
        est_public: true,
        donnees_json: ''
      }
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    formatFileSize(bytes) {
      if (!bytes) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
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
</style>
