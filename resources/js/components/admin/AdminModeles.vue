<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <h2 class="text-2xl font-bold mb-4">Gestion des Modèles de Documents</h2>

    <div class="flex justify-between mb-4">
      <button @click="openModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Ajouter un modèle
      </button>
      <button @click="openUpload" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
        Importer un modèle
      </button>
      <button @click="seedDefaults" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800">
        Installer modèles par défaut
      </button>
    </div>

    <!-- Tableau -->
    <table class="min-w-full bg-white border border-gray-200 shadow-md rounded">
      <thead>
        <tr class="bg-gray-100 text-left">
          <th class="p-2 border">Nom</th>
          <th class="p-2 border">Type</th>
          <th class="p-2 border">Chemin</th>
          <th class="p-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="modele in modeles" :key="modele.id" class="hover:bg-gray-50">
          <td class="p-2 border">{{ modele.nom }}</td>
          <td class="p-2 border">{{ modele.type_document }}</td>
          <td class="p-2 border">{{ modele.chemin_modele }}</td>
          <td class="p-2 border">
            <button @click="editModele(modele)" class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">
              Modifier
            </button>
            <button @click="deleteModele(modele.id)" class="ml-2 px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">
              Supprimer
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal ajout / modification -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded w-1/2 shadow-lg">
        <h3 class="text-xl font-semibold mb-4">{{ editing ? 'Modifier le modèle' : 'Ajouter un modèle' }}</h3>

        <form @submit.prevent="saveModele">
          <div class="mb-3">
            <label class="block text-gray-700 font-medium">Nom :</label>
            <input v-model="form.nom" type="text" class="border p-2 rounded w-full" required />
          </div>

          <div class="mb-3">
            <label class="block text-gray-700 font-medium">Type de document :</label>
            <input v-model="form.type_document" type="text" class="border p-2 rounded w-full" required />
          </div>

          <div class="mb-3">
            <label class="block text-gray-700 font-medium">Description :</label>
            <textarea v-model="form.description" class="border p-2 rounded w-full"></textarea>
          </div>

          <div class="flex justify-end mt-4">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
              Annuler
            </button>
            <button type="submit" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              {{ editing ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal upload -->
    <div v-if="showUpload" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white p-6 rounded w-1/2 shadow-lg">
        <h3 class="text-xl font-semibold mb-4">Importer un modèle</h3>
        <form @submit.prevent="uploadModele">
          <div class="mb-3">
            <label class="block text-gray-700 font-medium">Nom :</label>
            <input v-model="uploadForm.nom" type="text" class="border p-2 rounded w-full" required />
          </div>

          <div class="mb-3">
            <label class="block text-gray-700 font-medium">Type de document :</label>
            <input v-model="uploadForm.type_document" type="text" class="border p-2 rounded w-full" required />
          </div>

          <div class="mb-3">
            <label class="block text-gray-700 font-medium">Fichier :</label>
            <input @change="handleFileUpload" type="file" accept=".docx,.pdf,.txt" class="border p-2 rounded w-full" />
          </div>

          <div class="flex justify-end mt-4">
            <button type="button" @click="closeUpload" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">
              Annuler
            </button>
            <button type="submit" class="ml-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              Importer
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AdminModeles',
  data() {
    return {
      modeles: [],
      showModal: false,
      showUpload: false,
      showEditHtml: false,
      editing: false,
      form: {
        id: null,
        nom: '',
        description: '',
        type_document: '',
        chemin_modele: ''
      },
      editContent: '',
      editingType: '',
      uploadForm: {
        nom: '',
        type_document: '',
        description: '',
        activer: true,
        fichier: null
      }
    };
  },
  methods: {
    async fetchModeles() {
      try {
        const response = await axios.get('/api/admin/modeles');
        this.modeles = response.data;
      } catch (error) {
        console.error(error);
      }
    },

    openModal() {
      this.showModal = true;
      this.editing = false;
      this.form = { id: null, nom: '', description: '', type_document: '', chemin_modele: '' };
    },

    closeModal() {
      this.showModal = false;
    },

    async saveModele() {
      try {
        if (this.editing) {
          await axios.put(`/api/admin/modeles/${this.form.id}`, this.form);
        } else {
          await axios.post('/api/admin/modeles', this.form);
        }
        this.fetchModeles();
        this.closeModal();
      } catch (error) {
        console.error(error);
      }
    },

    editModele(modele) {
      this.form = { ...modele };
      this.showModal = true;
      this.editing = true;
    },

    async deleteModele(id) {
      if (!confirm('Supprimer ce modèle ?')) return;
      try {
        await axios.delete(`/api/admin/modeles/${id}`);
        this.fetchModeles();
      } catch (error) {
        console.error(error);
      }
    },

    openUpload() {
      this.showUpload = true;
    },

    closeUpload() {
      this.showUpload = false;
    },

    handleFileUpload(event) {
      this.uploadForm.fichier = event.target.files[0];
    },

    async uploadModele() {
      try {
        const formData = new FormData();
        for (let key in this.uploadForm) {
          formData.append(key, this.uploadForm[key]);
        }
        await axios.post('/api/admin/modeles/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('Modèle importé avec succès');
        this.fetchModeles();
        this.closeUpload();
      } catch (error) {
        console.error(error);
      }
    },

    async seedDefaults() {
      try {
        await axios.post('/api/admin/modeles/seed');
        this.fetchModeles();
        alert('Modèles par défaut installés');
      } catch (e) {
        console.error(e);
        alert("Erreur lors de l'installation des modèles par défaut");
      }
    }
  },
  mounted() {
    this.fetchModeles();
  }
};
</script>

<style scoped>
table {
  border-collapse: collapse;
  width: 100%;
}
th,
td {
  border: 1px solid #ddd;
  padding: 8px;
}
</style>
