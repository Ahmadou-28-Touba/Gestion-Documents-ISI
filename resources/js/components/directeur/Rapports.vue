<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h1 class="h4 mb-0"><i class="fas fa-chart-line me-2"></i> Rapports</h1>
      <div class="d-flex gap-2">
        <select class="form-select" style="width:auto" v-model.number="annee" @change="load()">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="btn btn-outline-secondary" @click="load()">
          Recharger
        </button>
        <button class="btn btn-primary" @click="exportPdf()">
          Exporter PDF
        </button>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Synthèse {{ annee }}</div>
          <div class="card-body">
            <ul class="mb-0">
              <li>Documents générés: <strong>{{ rapport.statistiques?.documents_generes || 0 }}</strong></li>
              <li>Absences déclarées: <strong>{{ rapport.statistiques?.absences_declarees || 0 }}</strong></li>
              <li>Utilisateurs inscrits: <strong>{{ rapport.statistiques?.utilisateurs_inscrits || 0 }}</strong></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Exports</div>
          <div class="card-body">
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary" @click="exportAbsences()">Exporter absences (JSON)</button>
              <button class="btn btn-outline-success" @click="exportDocuments()">Exporter documents (JSON)</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-header bg-light">Évolution mensuelle ({{ annee }})</div>
      <div class="card-body">
        <div class="row">
          <div class="col-12">
            <canvas id="evolutionChart" height="120"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Documents (année)</div>
          <div class="card-body">
            <div class="mb-3">
              <canvas id="documentsTypeChart" height="140"></canvas>
            </div>
            <div class="table-responsive" style="max-height: 320px; overflow:auto">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Étudiant</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in rapport.documents_generes || []" :key="d.id">
                    <td>{{ d.nom || 'Document' }}</td>
                    <td>{{ d.type }}</td>
                    <td>{{ d.etudiant?.utilisateur?.nom || '-' }}</td>
                    <td>{{ formatDate(d.date_generation) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-light">Absences (année)</div>
          <div class="card-body">
            <div class="table-responsive" style="max-height: 320px; overflow:auto">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>Étudiant</th>
                    <th>Enseignant</th>
                    <th>Déclaration</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="a in rapport.absences_traitees || []" :key="a.id">
                    <td>{{ a.etudiant?.utilisateur?.nom }}</td>
                    <td>{{ a.enseignant?.utilisateur?.nom }}</td>
                    <td>{{ formatDate(a.date_declaration) }}</td>
                    <td><span class="badge" :class="badgeFor(a.statut)">{{ a.statut }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'DirecteurRapports',
  data() {
    const currentYear = new Date().getFullYear()
    return {
      annee: currentYear,
      years: [currentYear, currentYear - 1, currentYear - 2, currentYear - 3],
      rapport: { statistiques: { evolution_mensuelle: {} }, documents_generes: [], absences_traitees: [] },
      loading: false,
      chartJsReady: false,
      charts: {
        evolution: null,
        docsType: null
      }
    }
  },
  methods: {
    async load() {
      this.loading = true
      try {
        const res = await axios.get(`directeur/rapport-annuel/${this.annee}`)
        this.rapport = res.data?.data || this.rapport
        await this.ensureCharts()
        this.renderCharts()
      } catch (e) {
        console.error(e)
      } finally {
        this.loading = false
      }
    },
    async exportPdf() {
      try {
        const res = await axios.get(`directeur/rapport-annuel/${this.annee}/pdf`, { responseType: 'blob' })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `rapport_annuel_${this.annee}.pdf`
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        console.error(e)
      }
    },
    async ensureCharts() {
      if (this.chartJsReady || window.Chart) { this.chartJsReady = true; return }
      await new Promise((resolve, reject) => {
        const s = document.createElement('script')
        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'
        s.onload = () => { this.chartJsReady = true; resolve() }
        s.onerror = reject
        document.head.appendChild(s)
      })
    },
    renderCharts() {
      if (!this.chartJsReady || !window.Chart) return
      // Destroy previous charts
      if (this.charts.evolution) { this.charts.evolution.destroy(); this.charts.evolution = null }
      if (this.charts.docsType) { this.charts.docsType.destroy(); this.charts.docsType = null }

      // Evolution mensuelle
      const evo = this.rapport?.statistiques?.evolution_mensuelle || {}
      const labels = Object.keys(evo)
      const docs = labels.map(m => evo[m]?.documents || 0)
      const abs = labels.map(m => evo[m]?.absences || 0)
      const users = labels.map(m => evo[m]?.utilisateurs || 0)
      const evoCtx = document.getElementById('evolutionChart')?.getContext('2d')
      if (evoCtx) {
        this.charts.evolution = new window.Chart(evoCtx, {
          type: 'line',
          data: {
            labels,
            datasets: [
              { label: 'Documents', data: docs, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.2)', tension: .2 },
              { label: 'Absences', data: abs, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.2)', tension: .2 },
              { label: 'Utilisateurs', data: users, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.2)', tension: .2 }
            ]
          },
          options: { responsive: true, maintainAspectRatio: false }
        })
      }

      // Documents par type (depuis la liste annuelle)
      const byType = {}
      ;(this.rapport?.documents_generes || []).forEach(d => {
        byType[d.type] = (byType[d.type] || 0) + 1
      })
      const types = Object.keys(byType)
      const counts = types.map(t => byType[t])
      const docsCtx = document.getElementById('documentsTypeChart')?.getContext('2d')
      if (docsCtx && types.length) {
        this.charts.docsType = new window.Chart(docsCtx, {
          type: 'pie',
          data: {
            labels: types,
            datasets: [{ data: counts, backgroundColor: ['#0d6efd','#20c997','#ffc107','#dc3545','#6f42c1','#198754'] }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        })
      }
    },
    async exportAbsences() {
      try {
        const res = await axios.get('directeur/export/absences', { params: { date_debut: `${this.annee}-01-01`, date_fin: `${this.annee}-12-31` } })
        this.downloadJson(res.data?.data || [], `absences_${this.annee}.json`)
      } catch (e) {
        console.error(e)
      }
    },
    async exportDocuments() {
      try {
        const res = await axios.get('directeur/export/documents', { params: { date_debut: `${this.annee}-01-01`, date_fin: `${this.annee}-12-31` } })
        this.downloadJson(res.data?.data || [], `documents_${this.annee}.json`)
      } catch (e) {
        console.error(e)
      }
    },
    downloadJson(obj, filename) {
      const blob = new Blob([JSON.stringify(obj, null, 2)], { type: 'application/json' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      a.remove()
      window.URL.revokeObjectURL(url)
    },
    formatDate(d) {
      return d ? new Date(d).toLocaleString('fr-FR') : '-'
    },
    badgeFor(statut) {
      const map = { en_attente: 'bg-warning', validee: 'bg-success', refusee: 'bg-danger' }
      return map[statut] || 'bg-secondary'
    }
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('auth_token')}`
    this.load()
  }
}
</script>













