<template>
  <div class="p-4 bg-white rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Publication des bulletins de notes</h2>
    
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Année scolaire
      </label>
      <input 
        v-model="formData.annee_scolaire" 
        type="text" 
        class="w-full p-2 border rounded"
        placeholder="Ex: 2023-2024"
      >
    </div>
    
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Trimestre
      </label>
      <select v-model="formData.trimestre" class="w-full p-2 border rounded">
        <option value="1er Trimestre">1er Trimestre</option>
        <option value="2ème Trimestre">2ème Trimestre</option>
        <option value="3ème Trimestre">3ème Trimestre</option>
      </select>
    </div>
    
    <button
      @click="publierBulletins"
      :disabled="isLoading"
      class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 flex items-center"
    >
      <span v-if="isLoading" class="mr-2">
        <i class="fas fa-spinner fa-spin"></i>
      </span>
      {{ isLoading ? 'Publication en cours...' : 'Publier les bulletins pour tous les étudiants' }}
    </button>
    
    <div v-if="message" class="mt-4 p-3 rounded" :class="messageClass">
      {{ message }}
    </div>
    
    <div v-if="resultats" class="mt-6">
      <h3 class="font-semibold mb-2">Résultats :</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-green-50 p-3 rounded">
          <h4 class="font-medium text-green-800">Succès ({{ resultats.success.length }})</h4>
          <ul class="text-sm text-green-700 mt-1 max-h-40 overflow-y-auto">
            <li v-for="(item, index) in resultats.success" :key="'success-'+index" class="truncate">
              {{ item.etudiant_nom }} - ID: {{ item.document_id }}
            </li>
          </ul>
        </div>
        <div v-if="resultats.errors.length > 0" class="bg-red-50 p-3 rounded">
          <h4 class="font-medium text-red-800">Erreurs ({{ resultats.errors.length }})</h4>
          <ul class="text-sm text-red-700 mt-1 max-h-40 overflow-y-auto">
            <li v-for="(error, index) in resultats.errors" :key="'error-'+index" class="truncate">
              {{ error.etudiant_nom }}: {{ error.message }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PublierBulletins',
  
  data() {
    return {
      isLoading: false,
      message: '',
      messageClass: '',
      resultats: null,
      formData: {
        annee_scolaire: '',
        trimestre: '1er Trimestre'
      }
    };
  },
  
  created() {
    // Définir l'année scolaire actuelle par défaut
    const date = new Date();
    const annee = date.getFullYear();
    const mois = date.getMonth() + 1; // Les mois commencent à 0
    
    if (mois >= 9) { // Septembre à Décembre
      this.formData.annee_scolaire = `${annee}-${annee + 1}`;
    } else { // Janvier à Août
      this.formData.annee_scolaire = `${annee - 1}-${annee}`;
    }
  },
  
  methods: {
    async publierBulletins() {
      if (!confirm('Êtes-vous sûr de vouloir publier les bulletins pour tous les étudiants ? Cette action peut prendre du temps.')) {
        return;
      }
      
      this.isLoading = true;
      this.message = 'Publication des bulletins en cours...';
      this.messageClass = 'bg-blue-50 text-blue-800';
      this.resultats = null;
      
      try {
        const response = await axios.post('/api/documents/publier-bulletins', this.formData);
        
        if (response.data.success) {
          this.message = 'Publication terminée avec succès !';
          this.messageClass = 'bg-green-50 text-green-800';
          this.resultats = response.data.resultats;
          
          // Émettre un événement pour rafraîchir la liste des documents
          this.$emit('documents-updated');
        } else {
          throw new Error(response.data.message || 'Une erreur est survenue');
        }
      } catch (error) {
        console.error('Erreur lors de la publication des bulletins :', error);
        this.message = error.response?.data?.message || 'Une erreur est survenue lors de la publication des bulletins';
        this.messageClass = 'bg-red-50 text-red-800';
      } finally {
        this.isLoading = false;
      }
    }
  }
};
</script>
