<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Générer & Publier des documents</h1>
    <div class="card p-3 mb-3">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Type de document</label>
          <select v-model="type" class="form-select">
            <option disabled value="">-- Choisir --</option>
            <option v-for="t in types" :key="t.type" :value="t.type">{{ t.label }}</option>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">IDs étudiants (séparés par des virgules)</label>
          <input v-model="idsTexte" class="form-control" placeholder="ex: 1,2,5,9" />
        </div>
      </div>
      <div class="mt-3 d-flex gap-2 flex-wrap">
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" @click="aperçu" :disabled="!type">
            <i class="fas fa-eye me-1"></i> Aperçu (PDF)
          </button>
          <button class="btn btn-primary" @click="publier" :disabled="!type || !idsTexte">
            <i class="fas fa-paper-plane me-1"></i> Publier
          </button>
          <button class="btn btn-success" @click="publierPourTous" :disabled="!type || isPublishingAll">
            <i class="fas fa-users me-1"></i>
            <span v-if="!isPublishingAll">Publier pour tous</span>
            <span v-else>
              <i class="fas fa-spinner fa-spin me-1"></i> Publication en cours...
            </span>
          </button>
        </div>
        <button class="btn btn-light ms-auto" @click="chargerRecents">
          <i class="fas fa-sync-alt me-1"></i> Rafraîchir récents
        </button>
      </div>
    </div>

    <div v-if="pdfUrl" class="card p-3 mb-3">
      <h6>Prévisualisation</h6>
      <iframe :src="pdfUrl" style="width:100%;height:70vh;border:1px solid #ddd;"></iframe>
    </div>

    <!-- Résultat de la publication normale -->
    <div v-if="publishResult" class="card p-3 mb-3">
      <h6 class="mb-2">Résultat de la publication</h6>
      <div>OK: {{ publishResult.ok || 0 }} | Erreurs: {{ publishResult.errors || 0 }}</div>
      <div v-if="(publishResult.details || []).length" class="mt-2">
        <ul class="mb-0">
          <li v-for="(d,i) in publishResult.details" :key="i">{{ d.error || d.warning || 'OK' }} <span v-if="d.etudiant_id">(étudiant {{ d.etudiant_id }})</span></li>
        </ul>
      </div>
    </div>

    <!-- Résultat de la publication groupée -->
    <div v-if="publishAllResult && (publishAllResult.success.length > 0 || publishAllResult.errors.length > 0)" class="card p-3 mb-3">
      <h6 class="mb-3">Résultat de la publication groupée</h6>
      <div class="row">
        <div class="col-md-6">
          <div class="alert alert-success">
            <h6><i class="fas fa-check-circle me-2"></i>Réussites ({{ publishAllResult.success.length }})</h6>
            <ul class="mb-0 mt-2" v-if="publishAllResult.success.length > 0">
              <li v-for="(item, i) in publishAllResult.success.slice(0, 5)" :key="'success-'+i" class="text-truncate">
                {{ item.etudiant_nom }} (ID: {{ item.etudiant_id }})
              </li>
              <li v-if="publishAllResult.success.length > 5" class="text-muted">
                + {{ publishAllResult.success.length - 5 }} autres...
              </li>
            </ul>
            <p v-else class="mb-0">Aucun document publié avec succès.</p>
          </div>
        </div>
        <div class="col-md-6" v-if="publishAllResult.errors.length > 0">
          <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs ({{ publishAllResult.errors.length }})</h6>
            <ul class="mb-0 mt-2">
              <li v-for="(error, i) in publishAllResult.errors.slice(0, 5)" :key="'error-'+i" class="text-truncate">
                {{ error.etudiant_nom || `Étudiant ${error.etudiant_id}` }}: {{ error.message || 'Erreur inconnue' }}
              </li>
              <li v-if="publishAllResult.errors.length > 5" class="text-muted">
                + {{ publishAllResult.errors.length - 5 }} autres erreurs...
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="card p-3">
      <div class="d-flex align-items-center mb-2">
        <h6 class="mb-0">Documents récents <span v-if="type">— {{ type }}</span></h6>
        <button class="btn btn-sm btn-outline-secondary ms-auto" @click="chargerRecents">Rafraîchir</button>
      </div>
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Type</th>
              <th>Étudiant</th>
              <th>Date</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in recents" :key="d.id">
              <td>{{ d.id }}</td>
              <td>{{ d.nom }}</td>
              <td>{{ d.type }}</td>
              <td>{{ d.etudiant_id }}</td>
              <td>{{ d.date_generation }}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" @click="telechargerDoc(d)">Télécharger</button>
              </td>
            </tr>
            <tr v-if="recents.length === 0">
              <td colspan="6" class="text-center text-muted">Aucun document récent</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminGenererPublier',
  data() {
    return {
      type: '',
      types: [],
      idsTexte: '',
      pdfUrl: null,
      documentsRecents: [],
      publishResult: null,
      isPublishingAll: false,
      publishAllResult: {
        success: [],
        errors: []
      }
    }
  },
  methods: {
    async chargerTypes() {
      try {
        const response = await axios.get('modele-documents/types')
        this.types = response.data.data
      } catch (error) {
        console.error('Erreur lors du chargement des types de documents:', error)
        this.$toast.error('Erreur lors du chargement des types de documents')
      }
    },

    async publierPourTous() {
      if (!confirm('Êtes-vous sûr de vouloir publier ce document pour TOUS les étudiants ? Cette opération peut prendre du temps.')) {
        return
      }

      this.isPublishingAll = true
      this.publishAllResult = { success: [], errors: [] }
      
      try {
        const response = await axios.post(`documents/publier-pour-tous`, {
          type: this.type,
          annee_scolaire: this.getAnneeScolaire()
        })

        this.publishAllResult = response.data.resultats || { success: [], errors: [] }
        
        if (response.data.success) {
          this.$toast.success(`Publication terminée : ${this.publishAllResult.success.length} réussites, ${this.publishAllResult.errors.length} échecs`)
          this.chargerRecents()
        } else {
          this.$toast.error('Erreur lors de la publication groupée')
        }
      } catch (error) {
        console.error('Erreur lors de la publication groupée:', error)
        this.$toast.error(error.response?.data?.message || 'Erreur lors de la publication groupée')
      } finally {
        this.isPublishingAll = false
      }
    },

    getAnneeScolaire() {
      const date = new Date()
      const annee = date.getFullYear()
      const mois = date.getMonth() + 1
      return mois >= 9 ? `${annee}-${annee + 1}` : `${annee - 1}-${annee}`
    },

    async aperçu() {
      if (!this.type) return alert('Choisissez un type')
      try {
        // on prend le premier id si fourni
        const ids = this.idsTexte.split(',').map(s=>parseInt(s.trim(),10)).filter(Boolean)
        const etu = ids[0]
        const res = await axios.post(`admin/documents/generer-preview`, { type: this.type, etudiant_id: etu }, { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
        this.pdfUrl = url
      } catch (e) {
        console.error(e)
        const r = e.response
        alert(`Impossible de générer l'aperçu (HTTP ${r?.status || 'NA'})\n${r?.data?.message || e.message}`)
      }
    },
    async publier() {
      if (!this.type) return alert('Choisissez un type')
      const ids = this.idsTexte.split(',').map(s=>parseInt(s.trim(),10)).filter(Boolean)
      if (ids.length === 0) return alert('Fournissez au moins un ID étudiant')
      try {
        const res = await axios.post(`admin/documents/publier`, { type: this.type, etudiant_ids: ids })
        this.publishResult = res.data?.data || null
        this.$toast?.success?.(`Publication OK: ${this.publishResult?.ok || 0}`)
        await this.chargerRecents()
      } catch (e) {
        console.error(e)
        const r = e.response
        alert(`Publication échouée (HTTP ${r?.status || 'NA'})\n${r?.data?.message || e.message}`)
      }
    },
    async chargerRecents() {
      try {
        const params = {}
        if (this.type) params.type = this.type
        const res = await axios.get('admin/documents/recent', { params })
        this.recents = res.data?.data || []
      } catch (e) {
        const r = e.response
        alert(`Erreur chargement récents (HTTP ${r?.status || 'NA'})\n${r?.data?.message || e.message}`)
      }
    },
    async telechargerDoc(d) {
      try {
        const res = await axios.get(`admin/documents/${d.id}/download`, { responseType: 'blob' })
        const disposition = res.headers['content-disposition'] || ''
        let filename = (disposition.split('filename=')[1] || `${d.nom || 'document'}_${d.id}.pdf`).replace(/"/g,'')
        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        const r = e.response
        alert(`Téléchargement impossible (HTTP ${r?.status || 'NA'})\n${r?.data?.message || e.message}`)
      }
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.chargerTypes()
    this.chargerRecents()
  },
  watch: {
    type() {
      this.chargerRecents()
    }
  }
}
</script>
