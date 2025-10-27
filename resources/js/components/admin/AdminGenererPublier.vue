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
      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="aperçu">Aperçu (PDF)</button>
        <button class="btn btn-primary" @click="publier">Publier</button>
        <button class="btn btn-light ms-auto" @click="chargerRecents">Rafraîchir récents</button>
      </div>
    </div>

    <div v-if="pdfUrl" class="card p-3 mb-3">
      <h6>Prévisualisation</h6>
      <iframe :src="pdfUrl" style="width:100%;height:70vh;border:1px solid #ddd;"></iframe>
    </div>

    <div v-if="publishResult" class="card p-3 mb-3">
      <h6 class="mb-2">Résultat de la publication</h6>
      <div>OK: {{ publishResult.ok || 0 }} | Erreurs: {{ publishResult.errors || 0 }}</div>
      <div v-if="(publishResult.details || []).length" class="mt-2">
        <ul class="mb-0">
          <li v-for="(d,i) in publishResult.details" :key="i">{{ d.error || d.warning || 'OK' }} <span v-if="d.etudiant_id">(étudiant {{ d.etudiant_id }})</span></li>
        </ul>
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
    return { type: '', idsTexte: '', types: [], pdfUrl: '', publishResult: null, recents: [] }
  },
  methods: {
    async chargerTypes() {
      try {
        const res = await axios.get('admin/modeles/types')
        this.types = res.data?.data || []
      } catch (e) {
        const r = e.response
        alert(`Erreur chargement types (HTTP ${r?.status || 'NA'})\n${r?.data?.message || e.message}`)
      }
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
