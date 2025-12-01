<template>
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="mb-0">
          <i class="fas fa-pen me-2"></i>
          Gestion des notes
        </h2>
        <router-link to="/enseignant-dashboard" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-arrow-left me-1"></i>
          Retour au tableau de bord
        </router-link>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-7">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fas fa-clipboard-list me-2"></i>
              Notes récentes
            </h5>
            <button class="btn btn-sm btn-outline-secondary" @click="loadNotes">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
          <div class="card-body">
            <div v-if="notes && notes.length">
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Étudiant</th>
                      <th>Classe</th>
                      <th>Matière</th>
                      <th>Type</th>
                      <th>Note</th>
                      <th>Date</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="n in notes" :key="n.id">
                      <td>
                        <strong>{{ n.etudiant?.utilisateur?.nom }} {{ n.etudiant?.utilisateur?.prenom }}</strong>
                        <br>
                        <small class="text-muted">{{ n.etudiant?.numero_etudiant }}</small>
                      </td>
                      <td>
                        <small class="text-muted">
                          {{ (n.classe || n.etudiant?.classe || n.etudiant)?.filiere }}
                          {{ (n.classe || n.etudiant?.classe || n.etudiant)?.annee }}
                          <span v-if="(n.classe || n.etudiant?.classe || n.etudiant)?.groupe">
                            ({{ (n.classe || n.etudiant?.classe || n.etudiant).groupe }})
                          </span>
                        </small>
                      </td>
                      <td>{{ n.matiere || '-' }}</td>
                      <td>{{ n.type_controle }}</td>
                      <td>
                        <span class="badge bg-primary">{{ Number(n.valeur).toFixed(2) }}/20</span>
                      </td>
                      <td>{{ formatDate(n.date) }}</td>
                      <td class="text-end">
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-primary me-1"
                          @click="startEdit(n)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger"
                          @click="deleteNote(n)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="mb-2" v-if="selectedEtudiant">
                <small class="text-muted">
                  Classe :
                  {{ selectedEtudiant.filiere }}
                  {{ selectedEtudiant.annee }}
                  <span v-if="selectedEtudiant.groupe">
                    ({{ selectedEtudiant.groupe }})
                  </span>
                </small>
              </div>
            </div>
            <div v-else class="text-muted">Aucune note enregistrée</div>
          </div>
        </div>
      </div>

      <div class="col-md-5">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-pen me-2"></i>
              Saisir une note
            </h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitNote">
              <div class="mb-3">
                <label class="form-label">Étudiant *</label>
                <select
                  class="form-control"
                  v-model="noteForm.etudiant_id"
                  required
                >
                  <option disabled value="">Sélectionnez un étudiant</option>
                  <option
                    v-for="e in etudiants"
                    :key="e.id"
                    :value="e.id"
                  >
                    {{ e.utilisateur?.nom }} {{ e.utilisateur?.prenom }}
                    ({{ e.numero_etudiant || 'N° non défini' }})
                  </option>
                </select>
                <small class="form-text text-muted">
                  Choisissez l'étudiant par son nom et numéro.
                </small>
              </div>

              <div class="mb-2" v-if="selectedEtudiant">
                <small class="text-muted">
                  Classe :
                  {{ selectedEtudiant.filiere }}
                  {{ selectedEtudiant.annee }}
                  <span v-if="selectedEtudiant.groupe">
                    ({{ selectedEtudiant.groupe }})
                  </span>
                </small>
              </div>

              <div class="mb-3">
                <label class="form-label">Matière</label>
                <input
                  type="text"
                  class="form-control"
                  v-model="noteForm.matiere"
                  placeholder="Ex : Analyse, Programmation..."
                >
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Type de contrôle *</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="noteForm.type_controle"
                    required
                    placeholder="DS, Examen..."
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Note (/20) *</label>
                  <input
                    type="number"
                    step="0.25"
                    min="0"
                    max="20"
                    class="form-control"
                    v-model.number="noteForm.valeur"
                    required
                  >
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Date *</label>
                  <input
                    type="date"
                    class="form-control"
                    v-model="noteForm.date"
                    required
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Période</label>
                  <input
                    type="text"
                    class="form-control"
                    v-model="noteForm.periode"
                    placeholder="Ex : Semestre 1, S1..."
                  >
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Commentaire</label>
                <textarea
                  class="form-control"
                  rows="2"
                  v-model="noteForm.commentaire"
                  placeholder="Appréciation, remarques..."
                ></textarea>
              </div>

              <div class="text-end">
                <button
                  v-if="editingNoteId"
                  type="button"
                  class="btn btn-outline-secondary me-2"
                  @click="cancelEdit"
                >
                  Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i>
                  {{ editingNoteId ? 'Mettre à jour la note' : 'Enregistrer la note' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'GestionNotesEnseignant',
  data() {
    return {
      notes: [],
      etudiants: [],
      editingNoteId: null,
      noteForm: {
        etudiant_id: '',
        matiere: '',
        type_controle: '',
        valeur: null,
        date: new Date().toISOString().split('T')[0],
        periode: '',
        commentaire: ''
      }
    }
  },
  computed: {
    selectedEtudiant() {
      if (!this.noteForm.etudiant_id) return null
      return this.etudiants.find(e => e.id === this.noteForm.etudiant_id) || null
    }
  },
  mounted() {
    this.loadNotes()
    this.fetchEtudiants()
  },
  methods: {
    async loadNotes() {
      try {
        const res = await axios.get('enseignant/notes')
        this.notes = res.data?.data || []
      } catch (e) {
        console.error('Erreur chargement notes:', e)
        this.$toast?.error?.('Erreur lors du chargement des notes')
      }
    },
    startEdit(note) {
      this.editingNoteId = note.id
      this.noteForm = {
        etudiant_id: note.etudiant_id,
        matiere: note.matiere || '',
        type_controle: note.type_controle || '',
        valeur: note.valeur,
        date: note.date ? String(note.date).slice(0, 10) : new Date().toISOString().split('T')[0],
        periode: note.periode || '',
        commentaire: note.commentaire || ''
      }
    },
    cancelEdit() {
      this.editingNoteId = null
      this.noteForm = {
        etudiant_id: '',
        matiere: '',
        type_controle: '',
        valeur: null,
        date: new Date().toISOString().split('T')[0],
        periode: '',
        commentaire: ''
      }
    },
    async deleteNote(note) {
      if (!confirm('Supprimer cette note ?')) return

      try {
        await axios.delete(`enseignant/notes/${note.id}`)
        this.$toast?.success?.('Note supprimée avec succès')

        if (this.editingNoteId === note.id) {
          this.cancelEdit()
        }

        this.loadNotes()
      } catch (error) {
        console.error('Erreur lors de la suppression de la note:', error)
        const r = error.response
        const msg = r?.data?.message || error.message || 'Erreur lors de la suppression de la note'
        this.$toast?.error?.(msg)
      }
    },
    async fetchEtudiants() {
      try {
        const res = await axios.get('enseignant/etudiants')
        this.etudiants = res.data?.data || res.data || []
      } catch (e) {
        console.error('Erreur chargement étudiants:', e)
      }
    },
    async submitNote() {
      try {
        const payload = { ...this.noteForm }
        if (!payload.matiere) delete payload.matiere
        if (!payload.periode) delete payload.periode
        if (!payload.commentaire) delete payload.commentaire

        if (this.editingNoteId) {
          await axios.put(`enseignant/notes/${this.editingNoteId}`, payload)
          this.$toast?.success?.('Note mise à jour avec succès')
        } else {
          await axios.post('enseignant/notes', payload)
          this.$toast?.success?.('Note enregistrée avec succès')
        }
        this.loadNotes()

        this.noteForm = {
          etudiant_id: '',
          matiere: this.noteForm.matiere,
          type_controle: this.noteForm.type_controle,
          valeur: null,
          date: new Date().toISOString().split('T')[0],
          periode: this.noteForm.periode,
          commentaire: ''
        }
        this.editingNoteId = null
      } catch (error) {
        console.error('Erreur lors de la saisie de la note:', error)
        const r = error.response
        if (r?.status === 422 && r?.data?.errors) {
          const msgs = Object.values(r.data.errors).flat().join('\n')
          this.$toast?.error?.(`Validation échouée:\n${msgs}`)
        } else {
          const msg = r?.data?.message || error.message || 'Erreur lors de l\'enregistrement de la note'
          this.$toast?.error?.(msg)
        }
      }
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.card {
  margin-bottom: 1rem;
}
</style>
