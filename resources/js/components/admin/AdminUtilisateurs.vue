<template>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Gestion des Utilisateurs</h1>
            <button @click="showModal = true" class="btn btn-success">
                <i class="fas fa-user-plus me-1"></i> Ajouter un Utilisateur
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="user in utilisateurs" :key="user.id">
                        <td>{{ user.id }}</td>
                        <td>{{ user.nom }}</td>
                        <td>{{ user.email }}</td>
                        <td>
                            <span class="badge bg-primary" v-if="user.role==='administrateur'">Administrateur</span>
                            <span class="badge bg-secondary" v-else>{{ user.role }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button @click="editUtilisateur(user)" class="btn btn-outline-warning">Modifier</button>
                                <button @click="deleteUtilisateur(user.id)" class="btn btn-outline-danger">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Ajouter / Modifier -->
        <div v-if="showModal" class="modal fade show" style="display:block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editing ? 'Modifier Utilisateur' : 'Ajouter Utilisateur' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveUtilisateur">
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input v-model="form.nom" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prénom</label>
                                <input v-model="form.prenom" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input v-model="form.email" type="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rôle</label>
                                <select v-model="form.role" class="form-select" required>
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="administrateur">Administrateur</option>
                                    <option value="enseignant">Enseignant</option>
                                    <option value="etudiant">Étudiant</option>
                                    <option value="directeur">Directeur</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mot de passe</label>
                                <input v-model="form.password" type="password" class="form-control" :required="!editing">
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" @click="closeModal" class="btn btn-outline-secondary">Annuler</button>
                                <button type="submit" class="btn btn-success">{{ editing ? 'Mettre à jour' : 'Ajouter' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            utilisateurs: [],
            showModal: false,
            editing: false,
            form: {
                id: null,
                nom: '',
                prenom: '',
                email: '',
                role: '',
                password: ''
            }
        };
    },
    methods: {
        fetchUtilisateurs(page = 1) {
            axios.get('admin/utilisateurs', { params: { page } })
                .then(res => {
                    // L'API renvoie un objet de pagination: { data: { data: [...], current_page, ... } }
                    const payload = res.data?.data;
                    this.utilisateurs = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                })
                .catch(err => console.error(err));
        },
        saveUtilisateur() {
            if (this.editing) {
                const updateData = { ...this.form };
                if (!updateData.password) delete updateData.password; // ne pas mettre à jour le mot de passe si vide
                axios.put(`admin/utilisateurs/${this.form.id}`, updateData)
                    .then(() => {
                        this.fetchUtilisateurs();
                        this.closeModal();
                    })
                    .catch(err => {
                        console.error(err);
                        const res = err.response;
                        if (res?.status === 422) {
                            const messages = Object.values(res.data?.errors || {}).flat().join('\n');
                            alert(`Validation échouée:\n${messages}`);
                        } else {
                            alert(res?.data?.message || 'Erreur lors de la mise à jour');
                        }
                    });
            } else {
                axios.post('admin/utilisateurs', this.form)
                    .then(() => {
                        this.fetchUtilisateurs();
                        this.closeModal();
                    })
                    .catch(err => {
                        console.error(err);
                        const res = err.response;
                        if (res?.status === 422) {
                            const messages = Object.values(res.data?.errors || {}).flat().join('\n');
                            alert(`Validation échouée:\n${messages}`);
                        } else {
                            alert(res?.data?.message || 'Erreur lors de la création (vérifiez les champs requis)');
                        }
                    });
            }
        },
        editUtilisateur(user) {
            this.editing = true;
            this.form = { ...user, password: '' };
            this.showModal = true;
        },
        deleteUtilisateur(id) {
            if (confirm("Voulez-vous vraiment supprimer cet utilisateur ?")) {
                axios.delete(`admin/utilisateurs/${id}`)
                    .then(() => this.fetchUtilisateurs())
                    .catch(err => console.error(err));
            }
        },
        closeModal() {
            this.showModal = false;
            this.editing = false;
            this.form = { id: null, nom: '', prenom: '', email: '', role: '', password: '' };
        }
    },
    mounted() {
        axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`;
        this.fetchUtilisateurs();
    }
};
</script>
