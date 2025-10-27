<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Mettre à jour les Emplois du Temps</h1>
    <div class="card p-3">
      <p class="text-muted">Importer un fichier CSV (ou XLSX si activé plus tard) pour publier les nouveaux emplois du temps.</p>
      <input ref="file" type="file" accept=".csv,.txt" class="form-control mb-2"/>
      <button class="btn btn-primary" @click="importer">Importer</button>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
export default {
  name: 'AdminEmplois',
  methods: {
    async importer() {
      const f = this.$refs.file?.files?.[0]
      if (!f) return alert('Choisissez un fichier CSV')
      const fd = new FormData()
      fd.append('fichier', f)
      try {
        await axios.post('/api/admin/imports/emploi-temps', fd, { headers: { 'Content-Type': 'multipart/form-data' }})
        this.$toast?.success?.('Import effectué')
      } catch (e) {
        console.error(e)
        alert("Import échoué")
      }
    }
  }
}
</script>
