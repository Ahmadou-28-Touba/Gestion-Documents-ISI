<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0">Mon emploi du temps</h1>
      <router-link to="/enseignant-dashboard" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>
        Retour au tableau de bord
      </router-link>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status"></div>
        </div>

        <div v-else>
          <div v-if="items.length === 0" class="text-center py-4 text-muted">
            Aucun emploi du temps publié.
          </div>

          <div v-else class="table-responsive">
            <table class="table table-striped align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nom</th>
                  <th>Date</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="doc in items" :key="doc.id">
                  <td>{{ doc.nom || 'Emploi du temps' }}</td>
                  <td>{{ formatDate(doc.date_generation) }}</td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" @click="download(doc.id)">
                      Télécharger PDF
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <nav v-if="pagination.last_page > 1" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button class="page-link" @click="changePage(pagination.current_page - 1)">Précédent</button>
              </li>
              <li class="page-item" v-for="p in pages" :key="p" :class="{ active: p === pagination.current_page }">
                <button class="page-link" @click="changePage(p)">{{ p }}</button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button class="page-link" @click="changePage(pagination.current_page + 1)">Suivant</button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EmploiDuTempsEnseignant',
  data() {
    return {
      loading: false,
      items: [],
      pagination: { current_page: 1, last_page: 1 },
    }
  },
  computed: {
    pages() {
      const p = []
      const start = Math.max(1, this.pagination.current_page - 2)
      const end = Math.min(this.pagination.last_page, start + 4)
      for (let i = start; i <= end; i++) p.push(i)
      return p
    }
  },
  methods: {
    async load(page = 1) {
      this.loading = true
      try {
        const res = await axios.get(`enseignant/emploi-du-temps?page=${page}`)
        const data = res.data?.data
        this.items = data?.data || []
        this.pagination = {
          current_page: data?.current_page || 1,
          last_page: data?.last_page || 1
        }
      } catch (e) {
        console.error(e)
      } finally {
        this.loading = false
      }
    },
    changePage(p) {
      if (p >= 1 && p <= this.pagination.last_page) this.load(p)
    },
    async download(id) {
      try {
        const response = await axios.get(`enseignant/emploi-du-temps/${id}/telecharger`, { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const a = document.createElement('a')
        a.href = url
        a.download = `emploi_du_temps_${id}.pdf`
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        alert('Téléchargement impossible')
        console.error(e)
      }
    },
    formatDate(d) {
      return new Date(d).toLocaleString()
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.load()
  }
}
</script>
