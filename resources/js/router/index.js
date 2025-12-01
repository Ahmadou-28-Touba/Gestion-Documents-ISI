import { createRouter, createWebHashHistory } from 'vue-router';
import Login from '../components/Login.vue';
import Dashboard from '../components/Dashboard.vue';
import EtudiantAbsences from '../components/etudiant/Absences.vue';
import EtudiantDocuments from '../components/etudiant/Documents.vue';
import DemanderDocument from '../components/etudiant/DemanderDocument.vue';
import EnseignantValidation from '../components/enseignant/ValidationAbsences.vue';
// Admin routes disabled for refactor
// import AdminDocuments from '../components/admin/AdminDocuments.vue';
// import AdminUtilisateurs from '../components/admin/AdminUtilisateurs.vue';
import AdminClasses from '../components/admin/AdminClasses.vue';
// import AdminArchives from '../components/admin/AdminArchives.vue';
import DirecteurStatistiques from '../components/directeur/Statistiques.vue';
import DirecteurRapports from '../components/directeur/Rapports.vue';
import Profil from '../components/Profil.vue';
// Nouveaux composants CRUD
import DocumentList from '../components/DocumentList.vue';
import AbsenceList from '../components/AbsenceList.vue';
// import ModeleDocumentList from '../components/ModeleDocumentList.vue';
// Composants administrateur
import AdminDashboard from '../components/admin/AdminDashboard.vue';
import AdminModelesTypes from '../components/admin/AdminModelesTypes.vue';
import AdminGenererPublier from '../components/admin/AdminGenererPublier.vue';
import AdminEmplois from '../components/admin/AdminEmplois.vue';
import AdminArchivage from '../components/admin/AdminArchivage.vue';
import AdminUtilisateurs from '../components/admin/AdminUtilisateurs.vue';
// Composants par rôle
import EtudiantDashboard from '../components/etudiant/EtudiantDashboard.vue';
import EnseignantDashboard from '../components/enseignant/EnseignantDashboard.vue';
import GestionNotesEnseignant from '../components/enseignant/GestionNotesEnseignant.vue';
import DirecteurDashboard from '../components/directeur/DirecteurDashboard.vue';
import EmploiDuTempsEnseignant from '../components/enseignant/EmploiDuTemps.vue';

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/absences',
    name: 'EtudiantAbsences',
    component: EtudiantAbsences,
    meta: { requiresAuth: true, role: 'etudiant' }
  },
  {
    path: '/etudiant/documents',
    name: 'EtudiantDocuments',
    component: EtudiantDocuments,
    meta: { requiresAuth: true, role: 'etudiant' }
  },
  {
    path: '/etudiant/demander-document',
    name: 'DemanderDocument',
    component: DemanderDocument,
    meta: { requiresAuth: true, role: 'etudiant' }
  },
  {
    path: '/absences-validation',
    name: 'EnseignantValidation',
    component: EnseignantValidation,
    meta: { requiresAuth: true, role: 'enseignant' }
  },
  {
    path: '/enseignant-emploi-du-temps',
    name: 'EmploiDuTempsEnseignant',
    component: EmploiDuTempsEnseignant,
    meta: { requiresAuth: true, role: 'enseignant' }
  },
  // Admin routes removed for refactor
  {
    path: '/statistiques',
    name: 'DirecteurStatistiques',
    component: DirecteurStatistiques,
    meta: { requiresAuth: true, role: 'directeur' }
  },
  {
    path: '/rapports',
    name: 'DirecteurRapports',
    component: DirecteurRapports,
    meta: { requiresAuth: true, role: 'directeur' }
  },
  {
    path: '/profil',
    name: 'Profil',
    component: Profil,
    meta: { requiresAuth: true }
  },
  // Routes CRUD
  {
    path: '/documents-list',
    name: 'DocumentList',
    component: DocumentList,
    meta: { requiresAuth: true }
  },
  {
    path: '/absences-list',
    name: 'AbsenceList',
    component: AbsenceList,
    meta: { requiresAuth: true }
  },
  // Admin
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  {
    path: '/admin/generer-publier',
    name: 'AdminGenererPublier',
    component: AdminGenererPublier,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  {
    path: '/admin/emplois',
    name: 'AdminEmplois',
    component: AdminEmplois,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  {
    path: '/admin/archivage',
    name: 'AdminArchivage',
    component: AdminArchivage,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  {
    path: '/admin/modeles',
    name: 'AdminModelesTypes',
    component: AdminModelesTypes,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  {
    path: '/admin/utilisateurs',
    name: 'AdminUtilisateurs',
    component: AdminUtilisateurs,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  {
    path: '/admin/classes',
    name: 'AdminClasses',
    component: AdminClasses,
    meta: { requiresAuth: true, role: 'administrateur' }
  },
  // Routes administrateur
  // Admin dashboard and generation routes removed for refactor
  // Routes par rôle
  {
    path: '/etudiant-dashboard',
    name: 'EtudiantDashboard',
    component: EtudiantDashboard,
    meta: { requiresAuth: true, role: 'etudiant' }
  },
  {
    path: '/enseignant-dashboard',
    name: 'EnseignantDashboard',
    component: EnseignantDashboard,
    meta: { requiresAuth: true, role: 'enseignant' }
  },
  {
    path: '/enseignant/notes',
    name: 'GestionNotesEnseignant',
    component: GestionNotesEnseignant,
    meta: { requiresAuth: true, role: 'enseignant' }
  },
  {
    path: '/directeur-dashboard',
    name: 'DirecteurDashboard',
    component: DirecteurDashboard,
    meta: { requiresAuth: true, role: 'directeur' }
  }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes
});

// Garde de navigation pour l'authentification
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token');
  const user = JSON.parse(localStorage.getItem('user') || '{}');

  // Si la route nécessite une authentification
  if (to.meta.requiresAuth) {
    if (!token) {
      next('/login');
      return;
    }

    // Vérifier le rôle si spécifié
    if (to.meta.role && user.role !== to.meta.role) {
      next('/dashboard');
      return;
    }
  }

  // Si la route est pour les invités (login) et l'utilisateur est connecté
  if (to.meta.requiresGuest && token) {
    next('/dashboard');
    return;
  }

  next();
});

export default router;



