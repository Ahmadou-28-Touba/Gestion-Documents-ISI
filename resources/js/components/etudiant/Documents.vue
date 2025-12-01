<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="fas fa-file-alt me-2"></i>Mes Documents</h2>
      <div>
        <select class="form-select" v-model="filters.type" @change="loadDocuments">
          <option value="">Tous les types</option>
          <option value="attestation_scolarite">Attestation de scolarité</option>
          <option value="bulletin_notes">Bulletin de notes</option>
          <option value="attestation_reussite">Attestation de réussite</option>
          <option value="emploi_temps">Emploi du temps</option>
        </select>
      </div>
    </div>
    
    <div class="card">
      <div class="card-body">
        <div v-if="documents.length === 0" class="text-center py-4">
          <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
          <p class="text-muted">Aucun document disponible</p>
        </div>
        
        <div v-else class="row">
          <div v-for="document in documents" :key="document.id" class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center mb-2">
                  <i :class="getDocumentIcon(document.type)" class="text-primary me-2"></i>
                  <h6 class="card-title mb-0">{{ document.nom }}</h6>
                </div>
                
                <p class="card-text text-muted small">
                  {{ getDocumentTypeLabel(document.type) }}
                </p>
                
                <p class="card-text small text-muted">
                  <i class="fas fa-calendar me-1"></i>
                  {{ formatDate(document.date_generation) }}
                </p>
                
                <div class="mt-auto">
                  <button @click="downloadDocument(document.id)" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-download me-1"></i>
                    Télécharger
                  </button>
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
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'EtudiantDocuments',
  setup() {
    const documents = ref([]);
    const loading = ref(false);
    
    const filters = reactive({
      type: ''
    });
    
    const loadDocuments = async () => {
      loading.value = true;
      
      try {
        const params = new URLSearchParams();
        if (filters.type) {
          params.append('type', filters.type);
        }
        
        const response = await axios.get(`etudiant/documents?${params}`);
        if (response.data.success) {
          documents.value = response.data.data.data || [];
        }
      } catch (error) {
        console.error('Erreur lors du chargement des documents:', error);
      } finally {
        loading.value = false;
      }
    };
    
    const downloadDocument = async (documentId) => {
      try {
        const response = await axios.get(`etudiant/documents/${documentId}/telecharger`, {
          responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `document_${documentId}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        alert('Erreur lors du téléchargement du document');
        console.error(error);
      }
    };
    
    const getDocumentIcon = (type) => {
      const icons = {
        'attestation_scolarite': 'fas fa-certificate',
        'bulletin_notes': 'fas fa-chart-line',
        'attestation_reussite': 'fas fa-trophy',
        'emploi_temps': 'fas fa-calendar-alt',
        'certificat_scolarite': 'fas fa-graduation-cap'
      };
      return icons[type] || 'fas fa-file';
    };
    
    const getDocumentTypeLabel = (type) => {
      const labels = {
        'attestation_scolarite': 'Attestation de scolarité',
        'bulletin_notes': 'Bulletin de notes',
        'attestation_reussite': 'Attestation de réussite',
        'emploi_temps': 'Emploi du temps',
        'certificat_scolarite': 'Certificat de scolarité'
      };
      return labels[type] || type;
    };
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };
    
    onMounted(() => {
      loadDocuments();
    });
    
    return {
      documents,
      loading,
      filters,
      loadDocuments,
      downloadDocument,
      getDocumentIcon,
      getDocumentTypeLabel,
      formatDate
    };
  }
};
</script>













