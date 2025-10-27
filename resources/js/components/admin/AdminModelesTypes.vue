<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0">Gérer les Modèles (par type)</h1>
    </div>

    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Type</th>
              <th>Fichier actif</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in types" :key="t.type">
              <td>
                <div class="fw-semibold">{{ t.label }}</div>
                <div class="text-muted small">{{ t.type }}</div>
              </td>
              <td>
                <span v-if="t.modele">{{ t.modele.nom }} ({{ t.modele.extension }})</span>
                <span v-else class="text-muted">Aucun modèle actif</span>
              </td>
              <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                  <button class="btn btn-primary" @click="nouveauModele(t)">Nouveau modèle (Word)</button>
                  <button class="btn btn-outline-secondary" @click="telecharger(t)" :disabled="!t.modele">Télécharger</button>
                  <button class="btn btn-outline-primary" @click="ouvrirWord(t)" :disabled="!t.modele">Ouvrir dans Word</button>
                  <button class="btn btn-success" @click="declencherUpload(t)">Remplacer (Upload)</button>
                </div>
                <input type="file"
                       class="d-none"
                       :id="'upload-'+t.type"
                       accept=".docx,.odt,.html,.htm,.txt"
                       @change="(e)=>handleUploadChange(e, t)"/>
                <div class="form-text mt-1">
                  1) Téléchargez le modèle • 2) Modifiez-le dans Word/LibreOffice • 3) Upload pour remplacer
                </div>
              </td>
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
  name: 'AdminModelesTypes',
  data() {
    return {
      types: [],
      downloading: {},
    }
  },
  methods: {
    async chargerTypes() {
      try {
        const res = await axios.get('admin/modeles/types')
        this.types = res.data?.data || []
      } catch (e) {
        console.error(e)
        const res = e.response
        const msg = res?.data?.message || e.message || 'Erreur lors du chargement des types de modèles'
        alert(`Erreur lors du chargement des types de modèles (HTTP ${res?.status || 'NA'})\n${msg}`)
      }
    },
    async nouveauModele(t) {
      try {
        const res = await axios.get(`admin/modeles/type/${encodeURIComponent(t.type)}/new-docx`, { responseType: 'blob' })
        const disposition = res.headers['content-disposition'] || ''
        let filename = (disposition.split('filename=')[1] || `${t.type}_modele_vierge.docx`).replace(/"/g,'')
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        console.error(e)
        alert('Impossible de générer le modèle Word vierge')
      }
    },
    async telecharger(t) {
      if (!t.modele) return
      try {
        const res = await axios.get(`admin/modeles/type/${encodeURIComponent(t.type)}/download`, { responseType: 'blob' })
        const disposition = res.headers['content-disposition'] || ''
        let filename = (disposition.split('filename=')[1] || `${t.type}_modele.docx`).replace(/"/g,'')
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        console.error(e)
        alert('Téléchargement impossible pour ce type')
      }
    },
    ouvrirWord(t) {
      // Même action que télécharger: laisse l’utilisateur ouvrir avec Word/LibreOffice
      this.telecharger(t)
    },
    declencherUpload(t) {
      const input = document.getElementById('upload-'+t.type)
      if (input) input.click()
    },
    async handleUploadChange(e, t) {
      const file = e.target.files && e.target.files[0]
      if (!file) return
      try {
        const fd = new FormData()
        fd.append('fichier_modele', file)
        await axios.post(`admin/modeles/type/${encodeURIComponent(t.type)}/upload`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        this.$toast?.success?.('Modèle remplacé')
        await this.chargerTypes()
      } catch (e2) {
        console.error(e2)
        alert('Erreur lors du remplacement du modèle')
      } finally {
        e.target.value = ''
      }
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.chargerTypes()
  }
}
</script>
