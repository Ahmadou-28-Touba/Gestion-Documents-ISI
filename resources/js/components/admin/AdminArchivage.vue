<template>
  <div class="container py-4">
    <h1 class="h4 mb-3">Archiver les Documents</h1>
    <div class="card p-3">
      <label class="form-label">IDs de documents à archiver (séparés par des virgules)</label>
      <input v-model="idsTexte" class="form-control" placeholder="ex: 10,11,15"/>
      <div class="mt-2">
        <button class="btn btn-warning" @click="archiver">Archiver</button>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios'
export default {
  name: 'AdminArchivage',
  data(){return{idsTexte:''}},
  methods: {
    async archiver(){
      const ids = this.idsTexte.split(',').map(s=>parseInt(s.trim(),10)).filter(Boolean)
      if(ids.length===0) return alert('Fournissez des IDs')
      try {
        for(const id of ids){ await axios.post(`/api/documents/${id}/archiver`) }
        this.$toast?.success?.('Archivage terminé')
      } catch(e){ console.error(e); alert('Archivage échoué') }
    }
  }
}
</script>
