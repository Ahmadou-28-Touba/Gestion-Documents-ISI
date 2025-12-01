<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="fas fa-calendar-times me-2"></i>Mes Absences</h2>
      <button class="btn btn-primary" @click="showDeclareForm = true">
        <i class="fas fa-plus me-1"></i>
        Déclarer une absence
      </button>
    </div>
    
    <!-- Formulaire de déclaration -->
    <div v-if="showDeclareForm" class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Déclarer une nouvelle absence</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="declareAbsence">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="date_debut" class="form-label">Date de début</label>
              <input type="date" class="form-control" id="date_debut" v-model="absenceForm.date_debut" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="date_fin" class="form-label">Date de fin</label>
              <input type="date" class="form-control" id="date_fin" v-model="absenceForm.date_fin" required>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="motif" class="form-label">Motif</label>
            <textarea class="form-control" id="motif" v-model="absenceForm.motif" rows="3" required></textarea>
          </div>
          
          <div class="mb-3">
            <label for="justificatif" class="form-label">Justificatif (optionnel)</label>
            <input type="file" class="form-control" id="justificatif" @change="handleFileUpload">
          </div>
          
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" @click="cancelDeclare">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Déclarer
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Liste des absences -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Historique des absences</h5>
      </div>
      <div class="card-body">
        <div v-if="absences.length === 0" class="text-center py-4">
          <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
          <p class="text-muted">Aucune absence déclarée</p>
        </div>
        
        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Période</th>
                <th>Motif</th>
                <th>Statut</th>
                <th>Date déclaration</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="absence in absences" :key="absence.id">
                <td>{{ formatDateRange(absence.date_debut, absence.date_fin) }}</td>
                <td>{{ absence.motif }}</td>
                <td>
                  <span :class="getStatusBadgeClass(absence.statut)" class="badge">
                    {{ getStatusLabel(absence.statut) }}
                  </span>
                </td>
                <td>{{ formatDate(absence.date_declaration) }}</td>
                <td>
                  <button v-if="absence.justificatif_chemin" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-download"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

export default {
  name: 'EtudiantAbsences',
  setup() {
    const absences = ref([]);
    const loading = ref(false);
    const showDeclareForm = ref(false);
    
    const absenceForm = reactive({
      date_debut: '',
      date_fin: '',
      motif: '',
      justificatif: null
    });
    
    const loadAbsences = async () => {
      try {
        const response = await axios.get('/etudiant/absences');
        if (response.data.success) {
          absences.value = response.data.data.data || [];
        }
      } catch (error) {
        console.error('Erreur lors du chargement des absences:', error);
      }
    };
    
    const declareAbsence = async () => {
      loading.value = true;
      
      try {
        const formData = new FormData();
        formData.append('date_debut', absenceForm.date_debut);
        formData.append('date_fin', absenceForm.date_fin);
        formData.append('motif', absenceForm.motif);
        
        if (absenceForm.justificatif) {
          formData.append('justificatif', absenceForm.justificatif);
        }
        
        const response = await axios.post('/etudiant/absences', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        if (response.data.success) {
          alert('Absence déclarée avec succès !');
          showDeclareForm.value = false;
          resetForm();
          loadAbsences();
        }
      } catch (error) {
        alert('Erreur lors de la déclaration de l\'absence');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };
    
    const handleFileUpload = (event) => {
      absenceForm.justificatif = event.target.files[0];
    };
    
    const cancelDeclare = () => {
      showDeclareForm.value = false;
      resetForm();
    };
    
    const resetForm = () => {
      absenceForm.date_debut = '';
      absenceForm.date_fin = '';
      absenceForm.motif = '';
      absenceForm.justificatif = null;
    };
    
    const getStatusLabel = (statut) => {
      const labels = {
        'en_attente': 'En attente',
        'validee': 'Validée',
        'refusee': 'Refusée'
      };
      return labels[statut] || statut;
    };
    
    const getStatusBadgeClass = (statut) => {
      const classes = {
        'en_attente': 'bg-warning',
        'validee': 'bg-success',
        'refusee': 'bg-danger'
      };
      return classes[statut] || 'bg-secondary';
    };
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('fr-FR');
    };
    
    const formatDateRange = (dateDebut, dateFin) => {
      const debut = new Date(dateDebut).toLocaleDateString('fr-FR');
      const fin = new Date(dateFin).toLocaleDateString('fr-FR');
      return `${debut} - ${fin}`;
    };
    
    onMounted(() => {
      loadAbsences();
    });
    
    return {
      absences,
      loading,
      showDeclareForm,
      absenceForm,
      declareAbsence,
      handleFileUpload,
      cancelDeclare,
      getStatusLabel,
      getStatusBadgeClass,
      formatDate,
      formatDateRange
    };
  }
};
</script>













