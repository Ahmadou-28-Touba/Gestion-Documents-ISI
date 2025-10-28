<template>
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-12">
        <h2>
          <i class="fas fa-users me-2"></i>
          Gestion des classes et matières
        </h2>
      </div>
    </div>

    <!-- Création d'une classe -->
    <div class="card mb-4">
      <div class="card-header">
        <strong>Créer une classe</strong>
      </div>
      <div class="card-body">
        <form @submit.prevent="createClasse" class="row g-3" aria-label="Créer une classe">
          <div class="col-md-3">
            <label class="form-label" for="classe-filiere">Filière</label>
            <input id="classe-filiere" name="filiere" v-model="createForm.filiere" type="text" class="form-control" required aria-required="true" aria-label="Filière de la classe">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="classe-annee">Année</label>
            <input id="classe-annee" name="annee" v-model="createForm.annee" type="text" class="form-control" placeholder="L1/L2..." required aria-required="true" aria-label="Année de la classe">
          </div>
          <div class="col-md-2">
            <label class="form-label" for="classe-groupe">Groupe</label>
            <input id="classe-groupe" name="groupe" v-model="createForm.groupe" type="text" class="form-control" placeholder="G1/G2" aria-label="Groupe de la classe">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="classe-label">Label</label>
            <input id="classe-label" name="label" v-model="createForm.label" type="text" class="form-control" placeholder="Optionnel" aria-label="Label optionnel de la classe">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit" :disabled="creating" title="Créer la classe">
              <span v-if="creating" class="spinner-border spinner-border-sm me-1"></span>
              Créer
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Liste des classes -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Classes</strong>
        <button class="btn btn-sm btn-outline-secondary" @click="loadClasses">
          <i class="fas fa-sync-alt me-1"></i> Actualiser
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Filière</th>
                <th>Année</th>
                <th>Groupe</th>
                <th>Label</th>
                <th>Enseignants</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in classes" :key="c.id">
                <td>{{ c.id }}</td>
                <td>
                  <input v-model="c._edit.filiere" :disabled="!c._editing" class="form-control form-control-sm" :aria-label="`Filière de la classe ${c.id}`">
                </td>
                <td>
                  <input v-model="c._edit.annee" :disabled="!c._editing" class="form-control form-control-sm" :aria-label="`Année de la classe ${c.id}`">
                </td>
                <td>
                  <input v-model="c._edit.groupe" :disabled="!c._editing" class="form-control form-control-sm" :aria-label="`Groupe de la classe ${c.id}`">
                </td>
                <td>
                  <input v-model="c._edit.label" :disabled="!c._editing" class="form-control form-control-sm" :aria-label="`Label de la classe ${c.id}`">
                </td>
                <td class="position-relative" style="min-width: 360px;">
                  <div>
                    <div v-for="e in (c.enseignants || [])" :key="e.id" class="badge bg-secondary me-2 mb-2 p-2 d-inline-flex align-items-center" style="white-space: normal;">
                      <span>
                        {{ e.utilisateur?.nom }} {{ e.utilisateur?.prenom }}
                        <small class="ms-1">(<em>{{ e.matricule }}</em>)</small>
                      </span>
                      <button class="btn btn-sm btn-link text-light ms-2 p-0" title="Retirer" @click="detachEnseignant(c.id, e.id)">
                        <i class="fas fa-times"></i>
                      </button>
                      <div class="input-group input-group-sm ms-2" style="min-width: 200px;">
                        <input :aria-label="`Matière pour ${e.utilisateur?.nom} ${e.utilisateur?.prenom}`" v-model="e._matiere" type="text" class="form-control" placeholder="Matière (ex: Math)">
                        <button class="btn btn-outline-light" title="Enregistrer la matière" @click="saveMatiere(c.id, e)"><i class="fas fa-save"></i></button>
                      </div>
                    </div>
                  </div>
                  <div class="mt-2">
                    <div class="input-group input-group-sm">
                      <input
                        v-model="c._search"
                        @input="onSearchInput(c)"
                        type="text"
                        name="enseignant-search"
                        class="form-control"
                        placeholder="Rechercher enseignant (nom, prénom, matricule)"
                        aria-label="Rechercher un enseignant par nom, prénom ou matricule"
                        role="combobox"
                        :aria-expanded="(c._results?.length || 0) > 0 ? 'true' : 'false'"
                        aria-autocomplete="list"
                        :aria-controls="`enseignants-list-${c.id}`"
                      >
                      <select class="form-select" :aria-label="`Sélectionner un enseignant pour la classe ${c.id}`" v-model="c._selectId">
                        <option :value="''">Tous les enseignants</option>
                        <option v-for="e in allEnseignants" :key="e.id" :value="e.id">
                          {{ e.utilisateur?.nom }} {{ e.utilisateur?.prenom }} ({{ e.matricule }})
                        </option>
                      </select>
                      <button class="btn btn-outline-primary" :disabled="!c._selected && !c._selectId" @click="attachSelected(c)">
                        Attacher
                      </button>
                    </div>
                    <div v-if="c._results?.length" :id="`enseignants-list-${c.id}`" class="list-group position-absolute shadow-sm" role="listbox" style="z-index: 1000; max-height: 240px; overflow:auto; min-width: 320px;">
                      <button
                        v-for="r in c._results"
                        :key="r.id"
                        type="button"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        role="option"
                        @click="selectResult(c, r)"
                      >
                        <span>
                          <strong>{{ r.utilisateur?.nom }} {{ r.utilisateur?.prenom }}</strong>
                          <small class="text-muted ms-2">{{ r.matricule }}</small>
                          <small class="text-muted ms-2">{{ r.utilisateur?.email }}</small>
                        </span>
                        <i v-if="c._selected?.id === r.id" class="fas fa-check text-success"></i>
                      </button>
                    </div>
                    <small v-if="c._selected" class="text-muted d-block mt-1" :aria-live="'polite'">Sélectionné: {{ c._selected.utilisateur?.nom }} {{ c._selected.utilisateur?.prenom }} ({{ c._selected.matricule }})</small>
                  </div>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button v-if="!c._editing" class="btn btn-outline-primary" @click="startEdit(c)" title="Éditer la classe">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button v-else class="btn btn-success" @click="saveEdit(c)" title="Enregistrer les modifications">
                      <i class="fas fa-check"></i>
                    </button>
                    <button v-if="c._editing" class="btn btn-secondary" @click="cancelEdit(c)" title="Annuler les modifications">
                      <i class="fas fa-undo"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click="removeClasse(c.id)" title="Supprimer la classe">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminClasses',
  data() {
    return {
      classes: [],
      createForm: { filiere: '', annee: '', groupe: '', label: '' },
      creating: false,
      allEnseignants: [],
    }
  },
  mounted() {
    try { axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}` } catch {}
    this.loadClasses()
    this.loadAllEnseignants()
  },
  methods: {
    async loadClasses() {
      try {
        const res = await axios.get('admin/classes', { timeout: 15000 })
        this.classes = (res.data?.data || []).map(c => ({
          ...c,
          enseignants: (c.enseignants || []).map(e => ({ ...e, _matiere: e.pivot?.matiere || '' })),
          _editing: false,
          _edit: { ...c },
          _attachId: '',
          _search: '',
          _results: [],
          _selected: null,
          _searchTimer: null,
        }))
      } catch (e) {
        console.error('Erreur chargement classes:', e)
        this.$toast?.error?.('Erreur chargement classes')
      }
    },
    async saveMatiere(classeId, enseignant)
    {
      try {
        await axios.put(`admin/classes/${classeId}/enseignants/${enseignant.id}/matiere`, { matiere: (enseignant._matiere || '').trim() }, { timeout: 15000 })
        this.$toast?.success?.('Matière enregistrée')
        // Recharger uniquement la ligne concernée
        const res = await axios.get('admin/classes', { timeout: 15000 })
        const updated = (res.data?.data || []).find(c => c.id === classeId)
        if (updated) {
          const idx = this.classes.findIndex(c => c.id === classeId)
          if (idx !== -1) {
            this.classes[idx] = {
              ...updated,
              enseignants: (updated.enseignants || []).map(e => ({ ...e, _matiere: e.pivot?.matiere || '' })),
              _editing: false,
              _edit: { ...updated },
              _attachId: '',
              _search: '',
              _results: [],
              _selected: null,
              _searchTimer: null,
            }
          }
        }
      } catch (e) {
        console.error('Erreur enregistrement matière:', e)
        const msg = e.response?.data?.message || 'Erreur enregistrement matière'
        this.$toast?.error?.(msg)
      }
    },
    async loadAllEnseignants() {
      try {
        const res = await axios.get('admin/enseignants', { timeout: 20000 })
        this.allEnseignants = res.data?.data || []
      } catch (e) {
        console.error('Erreur chargement enseignants:', e)
      }
    },
    onSearchInput(c) {
      if (c._searchTimer) {
        clearTimeout(c._searchTimer)
        c._searchTimer = null
      }
      const q = (c._search || '').trim()
      if (q.length < 2) {
        c._results = []
        c._selected = null
        return
      }
      c._searchTimer = setTimeout(async () => {
        try {
          const res = await axios.get('admin/enseignants/search', { params: { q }, timeout: 10000 })
          c._results = res.data?.data || []
        } catch (e) {
          console.error('Erreur recherche enseignants:', e)
        }
      }, 300)
    },
    selectResult(c, r) {
      c._selected = r
      c._results = []
    },
    async attachSelected(c) {
      const id = c._selected?.id || (c._selectId ? Number(c._selectId) : null)
      if (!id) return
      await this.attachEnseignant(c.id, id)
      c._selected = null
      c._search = ''
      c._results = []
      c._selectId = ''
    },
    async createClasse() {
      const payload = {
        filiere: (this.createForm.filiere || '').trim(),
        annee: (this.createForm.annee || '').trim(),
        groupe: (this.createForm.groupe || '').trim() || null,
        label: (this.createForm.label || '').trim() || null,
      }
      if (!payload.filiere || !payload.annee) {
        this.$toast?.error?.('Filière et Année sont obligatoires')
        return
      }
      this.creating = true
      try {
        console.log('POST admin/classes payload', payload)
        const res = await axios.post('admin/classes', payload, { timeout: 20000 })
        const created = res.data?.data
        if (created) {
          // Mise à jour optimiste
          this.classes = [{ ...created, _editing: false, _edit: { ...created }, _attachId: '' }, ...this.classes]
        }
        this.$toast?.success?.('Classe créée')
        this.createForm = { filiere: '', annee: '', groupe: '', label: '' }
        await this.loadClasses()
      } catch (e) {
        console.error('Erreur création classe:', e)
        const status = e.response?.status
        const msg = e.response?.data?.message || `Erreur création classe (HTTP ${status ?? 'NA'})`
        this.$toast?.error?.(msg)
      } finally {
        this.creating = false
      }
    },
    startEdit(c) {
      c._editing = true
      c._edit = { filiere: c.filiere, annee: c.annee, groupe: c.groupe, label: c.label }
    },
    cancelEdit(c) {
      c._editing = false
      c._edit = { filiere: c.filiere, annee: c.annee, groupe: c.groupe, label: c.label }
    },
    async saveEdit(c) {
      try {
        await axios.put(`admin/classes/${c.id}`, c._edit)
        this.$toast?.success?.('Classe mise à jour')
        c._editing = false
        this.loadClasses()
      } catch (e) {
        console.error('Erreur mise à jour classe:', e)
        const msg = e.response?.data?.message || 'Erreur mise à jour classe'
        this.$toast?.error?.(msg)
      }
    },
    async removeClasse(id) {
      if (!confirm('Supprimer cette classe ?')) return
      try {
        await axios.delete(`admin/classes/${id}`, { timeout: 15000 })
        this.$toast?.success?.('Classe supprimée')
        this.loadClasses()
      } catch (e) {
        console.error('Erreur suppression classe:', e)
        this.$toast?.error?.('Erreur suppression classe')
      }
    },
    async attachEnseignant(classeId, enseignantId) {
      if (!enseignantId) return
      try {
        await axios.post(`admin/classes/${classeId}/attach-enseignant`, { enseignant_id: Number(enseignantId) }, { timeout: 15000 })
        this.$toast?.success?.('Enseignant attaché')
        this.loadClasses()
      } catch (e) {
        console.error('Erreur attacher enseignant:', e)
        const msg = e.response?.data?.message || 'Erreur attacher enseignant'
        this.$toast?.error?.(msg)
      }
    },
    async detachEnseignant(classeId, enseignantId) {
      if (!confirm('Retirer cet enseignant de la classe ?')) return
      try {
        await axios.delete(`admin/classes/${classeId}/detach-enseignant/${enseignantId}`, { timeout: 15000 })
        this.$toast?.success?.('Enseignant retiré')
        this.loadClasses()
      } catch (e) {
        console.error('Erreur détacher enseignant:', e)
        this.$toast?.error?.('Erreur détacher enseignant')
      }
    },
  }
}
</script>

<style scoped>
.table td input.form-control-sm { min-width: 110px; }
</style>
