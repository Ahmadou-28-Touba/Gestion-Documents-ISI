<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0">Génération de documents (Batch)</h1>
      <div class="btn-group">
        <button class="btn btn-outline-secondary" @click="presetBulletin">Prérégler: Bulletin</button>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form @submit.prevent="submitBatch">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Modèle (obligatoire)</label>
              <select v-model="form.modele_id" class="form-select" @change="onModeleChange" required>
                <option value="" disabled>Choisir un modèle actif...</option>
                <option v-for="m in modelesActifs" :key="m.id" :value="m.id">
                  {{ m.nom }} ({{ m.type_document }})
                </option>
              </select>
              <div class="form-text" v-if="form.type">Type détecté: {{ form.type }}</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Cible</label>
              <select v-model="form.cible" class="form-select" required>
                <option value="promotion">Promotion</option>
                <option value="groupe">Filière/Groupe</option>
                <option value="liste">Liste (IDs)</option>
              </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="publication" v-model="form.publication">
                <label class="form-check-label" for="publication">Publier</label>
              </div>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-3" v-if="form.cible==='promotion' || form.cible==='groupe'">
              <label class="form-label">Promotion (année)</label>
              <input type="number" class="form-control" v-model="form.promotion" placeholder="ex: 2025">
            </div>
            <div class="col-md-4" v-if="form.cible==='groupe'">
              <label class="form-label">Filière</label>
              <input type="text" class="form-control" v-model="form.filiere" placeholder="ex: informatique">
            </div>
            <div class="col-md-6" v-if="form.cible==='liste'">
              <label class="form-label">IDs Étudiants (séparés par des virgules)</label>
              <input type="text" class="form-control" v-model="listeIds" placeholder="ex: 12,45,78">
              <div class="form-text">Utilise les IDs d'étudiants. Optionnellement, tu peux fournir des IDs d'utilisateurs dans le JSON avancé.</div>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-12">
              <label class="form-label">Données communes (JSON optionnel)</label>
              <textarea class="form-control" rows="3" v-model="donneesCommunesRaw" placeholder='{"annee_univ":"2024-2025"}'></textarea>
            </div>
          </div>

          <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" type="submit" :disabled="submitting">
              <span v-if="!submitting">Générer</span>
              <span v-else>
                <span class="spinner-border spinner-border-sm me-1"></span> Traitement...
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="resultats" class="card shadow-sm">
      <div class="card-header bg-light">Résultats</div>
      <div class="card-body">
        <p class="mb-1">Total: <strong>{{ resultats.total }}</strong></p>
        <p class="mb-1 text-success">Succès: <strong>{{ resultats.success }}</strong></p>
        <p class="mb-3 text-danger">Erreurs: <strong>{{ resultats.errors }}</strong></p>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Étudiant ID</th>
                <th>Statut</th>
                <th>Détails</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in resultats.details" :key="i">
                <td>{{ d.etudiant_id }}</td>
                <td>
                  <span :class="d.status==='error' ? 'badge bg-danger' : 'badge bg-success'">
                    {{ d.status }}
                  </span>
                </td>
                <td>{{ d.message || d.document_id || '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="alert alert-info mt-3" v-if="form.publication">
          Les documents publiés sont accessibles aux étudiants et des notifications ont été envoyées.
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminGeneration',
  data() {
    return {
      modelesActifs: [],
      form: {
        modele_id: '',
        type: '',
        cible: 'promotion',
        promotion: '',
        filiere: '',
        publication: true
      },
      listeIds: '',
      donneesCommunesRaw: '',
      submitting: false,
      resultats: null
    }
  },
  methods: {
    async loadModeles() {
      try {
        const res = await axios.get('admin/modeles')
        this.modelesActifs = (res.data?.data || []).filter(m => m.est_actif)
      } catch (e) {
        console.error(e)
      }
    },
    onModeleChange() {
      const m = this.modelesActifs.find(x => x.id === this.form.modele_id)
      this.form.type = m ? m.type_document : ''
    },
    presetBulletin() {
      const m = this.modelesActifs.find(x => x.type_document === 'bulletin')
      if (m) {
        this.form.modele_id = m.id
        this.form.type = m.type_document
      }
      this.form.cible = 'promotion'
      this.form.publication = true
    },
    parseDonnees() {
      if (!this.donneesCommunesRaw) return {}
      try { return JSON.parse(this.donneesCommunesRaw) } catch (_) { return {} }
    },
    buildPayload() {
      const payload = {
        type: this.form.type,
        cible: this.form.cible,
        promotion: this.form.promotion || null,
        filiere: this.form.filiere || null,
        donnees_communes: this.parseDonnees(),
        publication: !!this.form.publication
      }
      if (this.form.cible === 'liste') {
        const ids = (this.listeIds || '')
          .split(',')
          .map(s => parseInt(s.trim(), 10))
          .filter(n => !isNaN(n))
        if (ids.length) payload.etudiant_ids = ids
      }
      return payload
    },
    async submitBatch() {
      if (!this.form.modele_id || !this.form.type) {
        alert('Veuillez choisir un modèle actif. Le type sera déduit du modèle.')
        return
      }
      this.submitting = true
      this.resultats = null
      try {
        const res = await axios.post('admin/documents/generer-batch', this.buildPayload())
        this.resultats = res.data?.data || null
        if (!this.resultats) alert('Aucun résultat retourné')
      } catch (e) {
        console.error(e)
        const msg = e.response?.data?.message || 'Erreur lors de la génération'
        alert(msg)
      } finally {
        this.submitting = false
      }
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.loadModeles()
  }
}
</script>
