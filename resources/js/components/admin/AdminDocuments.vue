<template>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Gestion des Documents</h1>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form @submit.prevent="soumettreDocument">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Nom du document</label>
                            <input v-model="form.nom" type="text" class="form-control" placeholder="Nom du document">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type de document</label>
                            <select v-model="form.type" class="form-select">
                                <option value="" disabled>Choisir...</option>
                                <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Modèle</label>
                            <select v-model="form.modele_document_id" class="form-select">
                                <option value="" disabled>Choisir...</option>
                                <option v-for="modele in modeles" :key="modele.id" :value="modele.id">{{ modele.nom }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-primary">
                                {{ form.id ? 'Modifier' : 'Ajouter' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Filtrer par type</label>
                <select v-model="filtres.type" @change="chargerDocuments" class="form-select">
                    <option value="">Tous les types</option>
                    <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Visibilité</label>
                <select v-model="filtres.est_public" @change="chargerDocuments" class="form-select">
                    <option value="">Tous</option>
                    <option :value="true">Public</option>
                    <option :value="false">Privé</option>
                </select>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Utilisateur</th>
                        <th>Date</th>
                        <th>Public</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="doc in documents.data" :key="doc.id">
                        <td>{{ doc.nom }}</td>
                        <td>{{ doc.type }}</td>
                        <td>{{ (doc.etudiant && doc.etudiant.utilisateur) ? (doc.etudiant.utilisateur.prenom + ' ' + doc.etudiant.utilisateur.nom) : '-' }}</td>
                        <td>{{ formatDate(doc.date_generation) }}</td>
                        <td>
                            <span :class="doc.est_public ? 'badge bg-success' : 'badge bg-secondary'">{{ doc.est_public ? 'Oui' : 'Non' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button @click="telecharger(doc.id)" class="btn btn-outline-success">Télécharger</button>
                                <button @click="editDocument(doc)" class="btn btn-outline-warning">Modifier</button>
                                <button @click="archiver(doc)" class="btn btn-outline-secondary">
                                    {{ doc.est_public ? 'Archiver' : 'Désarchiver' }}
                                </button>
                                <button @click="dupliquer(doc)" class="btn btn-outline-primary">Dupliquer</button>
                                <button @click="supprimer(doc.id)" class="btn btn-outline-danger">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-2 mt-3">
            <button :disabled="!documents.prev_page_url" @click="chargerDocuments(documents.prev_page_url)" class="btn btn-outline-secondary">Précédent</button>
            <button :disabled="!documents.next_page_url" @click="chargerDocuments(documents.next_page_url)" class="btn btn-outline-secondary">Suivant</button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import { ref } from 'vue';
import NotificationCenter from '../NotificationCenter.vue';

export default {
    components: { NotificationCenter },
    data() {
        return {
            documents: { data: [] },
            types: [],
            modeles: [],
            form: {
                id: null,
                nom: '',
                type: '',
                modele_document_id: null,
            },
            filtres: {
                type: '',
                est_public: '',
            },
            notifications: ref([]),
        };
    },
    mounted() {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`;
        this.chargerDocuments();
        this.chargerTypes();
        this.chargerModeles();
    },
    methods: {
        addNotification(title, text, type = 'success') {
            this.notifications.push({ title, text, type });
            setTimeout(() => { this.notifications.shift() }, 5000);
        },
        chargerDocuments(url = 'documents') {
            axios.get(url, { params: this.filtres }).then(res => {
                this.documents = res.data.data;
            });
        },
        chargerTypes() {
            axios.get('documents/types').then(res => {
                this.types = res.data.data;
            });
        },
        chargerModeles() {
            axios.get('admin/modeles').then(res => {
                this.modeles = res.data.data;
            });
        },
        soumettreDocument() {
            const method = this.form.id ? 'put' : 'post';
            const url = this.form.id ? `documents/${this.form.id}` : 'documents';
            axios[method](url, this.form).then(res => {
                this.addNotification('Succès', res.data.message, 'success');
                this.resetForm();
                this.chargerDocuments();
            }).catch(err => {
                const errors = err.response?.data?.errors || {};
                this.addNotification('Erreur', JSON.stringify(errors), 'error');
            });
        },
        editDocument(doc) {
            this.form = { ...doc };
        },
        resetForm() {
            this.form = { id: null, nom: '', type: '', modele_document_id: null };
        },
        supprimer(id) {
            if (!confirm('Voulez-vous vraiment supprimer ce document ?')) return;
            axios.delete(`documents/${id}`).then(res => {
                this.addNotification('Succès', res.data.message, 'success');
                this.chargerDocuments();
            });
        },
        telecharger(id) {
            window.open(`/api/documents/${id}/telecharger`, '_blank');
        },
        archiver(doc) {
            const action = doc.est_public ? 'archiver' : 'desarchiver';
            axios.post(`documents/${doc.id}/${action}`).then(res => {
                this.addNotification('Succès', res.data.message, 'success');
                this.chargerDocuments();
            });
        },
        dupliquer(doc) {
            axios.post(`documents/${doc.id}/dupliquer`, { nom: doc.nom + '_copie' }).then(res => {
                this.addNotification('Succès', res.data.message, 'success');
                this.chargerDocuments();
            });
        },
        formatDate(date) {
            return new Date(date).toLocaleString();
        }
    }
};
</script>

<style scoped>
table th, table td {
    text-align: center;
}
</style>
