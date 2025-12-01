<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="fas fa-file-import me-2"></i>Demander un document</h2>
      <router-link to="/etudiant/documents" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Retour
      </router-link>
    </div>

    <div class="card">
      <div class="card-body">
        <form @submit.prevent="submitForm">
          <div class="mb-3">
            <label for="type_document" class="form-label">Type de document</label>
            <select 
              id="type_document" 
              class="form-select" 
              v-model="form.type_document"
              required
              :disabled="loading"
            >
              <option value="">Sélectionnez un type de document</option>
              <option value="attestation_scolarite">Attestation de scolarité</option>
              <option value="bulletin_notes">Bulletin de notes</option>
              <option value="attestation_reussite">Attestation de réussite</option>
              <option value="certificat_scolarite">Certificat de scolarité</option>
            </select>
          </div>

          <div v-if="form.type_document === 'bulletin_notes'" class="mb-3">
            <label for="annee_scolaire" class="form-label">Année scolaire</label>
            <select 
              id="annee_scolaire" 
              class="form-select" 
              v-model="form.annee_scolaire"
              :disabled="loading"
            >
              <option v-for="annee in anneesScolaires" :key="annee" :value="annee">
                {{ annee }}
              </option>
            </select>
          </div>

          <div v-if="form.type_document === 'attestation_scolarite'" class="mb-3">
            <label for="motif" class="form-label">Motif de la demande (facultatif)</label>
            <textarea 
              id="motif" 
              class="form-control" 
              v-model="form.motif"
              rows="3"
              :disabled="loading"
              placeholder="Précisez si nécessaire le motif de votre demande..."
            ></textarea>
          </div>

          <div v-if="form.type_document === 'attestation_reussite'" class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            L'attestation de réussite n'est disponible qu'après la validation de votre année.
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="loading || !form.type_document || (form.type_document === 'attestation_reussite')"
            >
              <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
              <i v-else class="fas fa-file-download me-1"></i>
              {{ loading ? 'Génération en cours...' : 'Générer le document' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'DemanderDocument',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const etudiantData = ref(null);
    
    const form = reactive({
      type_document: '',
      annee_scolaire: '',
      motif: ''
    });

    // Générer les 5 dernières années scolaires
    const currentYear = new Date().getFullYear();
    const anneesScolaires = ref([]);
    
    for (let i = 0; i < 5; i++) {
      const year = currentYear - i;
      anneesScolaires.value.push(`${year-1}-${year}`);
    }
    
    // Définir l'année scolaire en cours par défaut
    const mois = new Date().getMonth() + 1;
    if (mois >= 9) { // Septembre à Décembre
      form.annee_scolaire = `${currentYear}-${currentYear + 1}`;
    } else { // Janvier à Août
      form.annee_scolaire = `${currentYear - 1}-${currentYear}`;
    }

    // Charger les données de l'étudiant
    const loadStudentData = async () => {
      try {
        const response = await axios.get('etudiant/donnees-document');
        if (response.data.success) {
          etudiantData.value = response.data.data;
          console.log('Données étudiant chargées:', etudiantData.value);
        }
      } catch (error) {
        console.error('Erreur lors du chargement des données étudiant:', error);
      }
    };

    // Soumettre le formulaire
    const submitForm = async () => {
      if (!form.type_document) return;
      
      loading.value = true;
      
      try {
        // Préparer les données du document
        const documentData = {
          type: form.type_document,
          nom: `${form.type_document}_${new Date().getTime()}`,
          donnees: {
            ...(form.annee_scolaire && { annee_scolaire: form.annee_scolaire }),
            ...(form.motif && { motif: form.motif }),
            ...(etudiantData.value?.etudiant && etudiantData.value.etudiant),
            date_emission: new Date().toISOString().split('T')[0]
          },
          etudiant_id: etudiantData.value?.etudiant?.id,
          modele_document_id: 1, // À remplacer par l'ID du modèle approprié
          est_public: true
        };
        
        console.log('Données du document à envoyer:', documentData);
        
        // Appeler l'API pour générer le document
        const response = await axios.post('documents', documentData);
        
        if (response.data.success) {
          // Télécharger le document généré
          const documentId = response.data.data.id;
          const downloadResponse = await axios.get(`documents/${documentId}/telecharger`, {
            responseType: 'blob'
          });
          
          // Créer un lien de téléchargement
          const url = window.URL.createObjectURL(new Blob([downloadResponse.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `${form.type_document}.pdf`);
          document.body.appendChild(link);
          link.click();
          link.remove();
          window.URL.revokeObjectURL(url);
          
          // Rediriger vers la liste des documents
          router.push('/etudiant/documents');
        }
      } catch (error) {
        console.error('Erreur lors de la génération du document:', error);
        alert('Une erreur est survenue lors de la génération du document: ' + (error.response?.data?.message || error.message));
      } finally {
        loading.value = false;
      }
    };

    // Charger les données au montage du composant
    onMounted(() => {
      loadStudentData();
    });

    return {
      form,
      loading,
      anneesScolaires,
      submitForm
    };
  }
};
</script>

<style scoped>
.container-fluid {
  max-width: 800px;
  margin: 0 auto;
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-label {
  font-weight: 600;
}

.btn-primary {
  min-width: 200px;
}

.alert {
  border-left: 4px solid #0d6efd;
}
</style>
