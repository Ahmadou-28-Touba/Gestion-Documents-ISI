<template>
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="fas fa-check-circle me-2"></i>
          Validation des Absences
        </h2>
        <router-link to="/enseignant-dashboard" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-arrow-left me-1"></i>
          Retour au tableau de bord
        </router-link>
      </div>
    </div>
    
    <div class="card">
      <div class="card-body">
        <div v-if="absences.length === 0" class="text-center py-4">
          <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
          <p class="text-muted">Aucune absence en attente de validation</p>
        </div>
        
        <div v-else class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Étudiant</th>
                <th>Période</th>
                <th>Motif</th>
                <th>Date déclaration</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="absence in absences" :key="absence.id">
                <td>
                  <div>
                    <strong>{{ absence.etudiant?.utilisateur?.prenom }} {{ absence.etudiant?.utilisateur?.nom }}</strong>
                    <br>
                    <small class="text-muted">{{ absence.etudiant?.numero_etudiant }}</small>
                  </div>
                </td>
                <td>{{ formatDateRange(absence.date_debut, absence.date_fin) }}</td>
                <td>{{ absence.motif }}</td>
                <td>{{ formatDate(absence.date_declaration) }}</td>
                <td>
                  <div class="btn-group" role="group">
                    <button @click="validerAbsence(absence.id)" class="btn btn-success btn-sm">
                      <i class="fas fa-check me-1"></i>
                      Valider
                    </button>
                    <button @click="showRefuseModal(absence)" class="btn btn-danger btn-sm">
                      <i class="fas fa-times me-1"></i>
                      Refuser
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Modal de refus -->
    <div v-if="showRefuse" class="modal show d-block" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Refuser l'absence</h5>
            <button type="button" class="btn-close" @click="closeRefuseModal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="motif_refus" class="form-label">Motif du refus</label>
              <textarea class="form-control" id="motif_refus" v-model="refuseForm.motif_refus" rows="3" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeRefuseModal">Annuler</button>
            <button type="button" class="btn btn-danger" @click="refuserAbsence" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Refuser
            </button>
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
  name: 'EnseignantValidation',
  setup() {
    const absences = ref([]);
    const loading = ref(false);
    const showRefuse = ref(false);
    const selectedAbsence = ref(null);
    
    const refuseForm = reactive({
      motif_refus: ''
    });
    
    const loadAbsences = async () => {
      try {
        const response = await axios.get('enseignant/absences/en-attente');
        if (response.data.success) {
          absences.value = response.data.data || [];
        }
      } catch (error) {
        const r = error.response
        alert(`Erreur chargement absences (HTTP ${r?.status || 'NA'})\n${r?.data?.message || error.message}`)
        console.error('Erreur lors du chargement des absences:', error);
      }
    };
    
    const validerAbsence = async (absenceId) => {
      if (confirm('Êtes-vous sûr de vouloir valider cette absence ?')) {
        try {
          const response = await axios.post(`enseignant/absences/${absenceId}/valider`, { action: 'valider' });
          if (response.data.success) {
            alert('Absence validée avec succès !');
            loadAbsences();
          }
        } catch (error) {
          const r = error.response
          alert(`Erreur lors de la validation de l'absence (HTTP ${r?.status || 'NA'})\n${r?.data?.message || error.message}`);
          console.error(error);
        }
      }
    };
    
    const showRefuseModal = (absence) => {
      selectedAbsence.value = absence;
      showRefuse.value = true;
      refuseForm.motif_refus = '';
    };
    
    const closeRefuseModal = () => {
      showRefuse.value = false;
      selectedAbsence.value = null;
      refuseForm.motif_refus = '';
    };
    
    const refuserAbsence = async () => {
      if (!refuseForm.motif_refus.trim()) {
        alert('Veuillez saisir un motif de refus');
        return;
      }
      
      loading.value = true;
      
      try {
        const response = await axios.post(`enseignant/absences/${selectedAbsence.value.id}/refuser`, {
          motif_refus: refuseForm.motif_refus
        });
        
        if (response.data.success) {
          alert('Absence refusée avec succès !');
          closeRefuseModal();
          loadAbsences();
        }
      } catch (error) {
        const r = error.response
        alert(`Erreur lors du refus de l'absence (HTTP ${r?.status || 'NA'})\n${r?.data?.message || error.message}`);
        console.error(error);
      } finally {
        loading.value = false;
      }
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
      try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
      loadAbsences();
    });
    
    return {
      absences,
      loading,
      showRefuse,
      selectedAbsence,
      refuseForm,
      loadAbsences,
      validerAbsence,
      showRefuseModal,
      closeRefuseModal,
      refuserAbsence,
      formatDate,
      formatDateRange
    };
  }
};
</script>













