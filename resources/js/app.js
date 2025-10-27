import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import axios from 'axios';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
// Import des nouveaux plugins et utilitaires
import ToastPlugin from './plugins/toast.js';
import errorHandler from './utils/errorHandler.js';

// Configuration d'axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

// Intercepteur pour ajouter le token d'authentification
axios.interceptors.request.use(
    config => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Intercepteur pour gérer les erreurs de réponse
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

const app = createApp(App);

// Utiliser les plugins
app.use(router);
app.use(store);
app.use(ToastPlugin);

// Initialiser l'authentification
store.dispatch('initAuth');

// Rendre les utilitaires disponibles globalement
app.config.globalProperties.$errorHandler = errorHandler;

// Exposer les utilitaires globalement pour les composants
window.$toast = app.config.globalProperties.$toast;
window.$errorHandler = errorHandler;

app.mount('#app');
