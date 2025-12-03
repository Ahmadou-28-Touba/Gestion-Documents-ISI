<template>
    <div id="app">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary" v-if="isAuthenticated">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span class="d-inline-block text-truncate align-middle" style="max-width: 180px" title="Gestion Documents ISI">Gestion Documents ISI</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <router-link to="/dashboard" class="nav-link">
                                <i class="fas fa-tachometer-alt me-1"></i>
                                Tableau de bord
                            </router-link>
                        </li>

                        <!-- Menu selon le rôle -->
                        <template v-if="isStudent">
                            <li class="nav-item">
                                <router-link to="/etudiant-dashboard" class="nav-link">Dashboard</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/absences" class="nav-link">Absences (déclarer/suivre)</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/documents" class="nav-link">Documents personnels</router-link>
                            </li>
                        </template>

                        <template v-if="isTeacher">
                            <li class="nav-item">
                                <router-link to="/enseignant-dashboard" class="nav-link">Dashboard</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/enseignant-emploi-du-temps" class="nav-link">Emploi du temps</router-link>
                            </li>
                        </template>

                        <template v-if="isAdmin">
                            <li class="nav-item">
                                <router-link to="/admin/dashboard" class="nav-link">Dashboard</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/admin/generer-publier" class="nav-link" title="Gestion Administrative">
                                    <span class="d-none d-md-inline text-truncate" style="max-width: 160px; display:inline-block;">Gestion Administrative</span>
                                    <span class="d-inline d-md-none">Admin</span>
                                </router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/admin/modeles" class="nav-link">Modèles</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/admin/archivage" class="nav-link">Archives</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/admin/utilisateurs" class="nav-link">Utilisateurs</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/admin/classes" class="nav-link" title="Gestion des classes">
                                    <span class="d-none d-md-inline text-truncate" style="max-width: 160px; display:inline-block;">Gestion des classes</span>
                                    <span class="d-inline d-md-none">Classes</span>
                                </router-link>
                            </li>
                        </template>

                        <template v-if="isDirector">
                            <li class="nav-item">
                                <router-link to="/directeur-dashboard" class="nav-link">Dashboard</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/statistiques" class="nav-link">Rapports / Statistiques</router-link>
                            </li>
                            <li class="nav-item">
                                <router-link to="/rapports" class="nav-link">Rapports</router-link>
                            </li>
                        </template>
                    </ul>

                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                {{ userName }}
                                <span class="badge bg-light text-dark ms-1">{{ userRole }}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><router-link to="/profil" class="dropdown-item">Mon profil</router-link></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" @click="logout" class="dropdown-item">Déconnexion</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="container-fluid py-4">
            <router-view />
        </main>

        <!-- Notifications -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
            <div v-for="notification in notifications" :key="notification.id"
                 class="toast show" role="alert">
                <div class="toast-header" :class="notification.type === 'error' ? 'bg-danger text-white' : 'bg-success text-white'">
                    <strong class="me-auto">{{ notification.title }}</strong>
                    <button type="button" class="btn-close" @click="removeNotification(notification.id)"></button>
                </div>
                <div class="toast-body">
                    {{ notification.text }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

export default {
    name: 'App',
    setup() {
        const store = useStore();
        const router = useRouter();

        const isAuthenticated = computed(() => store.getters.isAuthenticated);
        const user = computed(() => store.state.user);
        const userRole = computed(() => store.getters.userRole);
        const userName = computed(() => store.getters.userName);

        const isAdmin = computed(() => store.getters.isAdmin);
        const isStudent = computed(() => store.getters.isStudent);
        const isTeacher = computed(() => store.getters.isTeacher);
        const isDirector = computed(() => store.getters.isDirector);

        const notifications = computed(() => store.state.notifications);

        const logout = async () => {
            await store.dispatch('logout');
            router.push('/login');
        };

        const removeNotification = (id) => {
            store.dispatch('removeNotification', id);
        };

        // Initialiser l'authentification au montage
        onMounted(() => {
            store.dispatch('initAuth');
        });

        return {
            isAuthenticated,
            user,
            userRole,
            userName,
            isAdmin,
            isStudent,
            isTeacher,
            isDirector,
            notifications,
            logout,
            removeNotification
        };
    }
};
</script>

<style>
#app {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.navbar-brand {
    font-weight: bold;
}

.router-link-active {
    font-weight: bold;
}

.toast {
    margin-bottom: 10px;
}
</style>
