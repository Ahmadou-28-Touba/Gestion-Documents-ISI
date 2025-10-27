<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Génération de documents - Admin</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 24px; }
    .row { display: flex; gap: 12px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
    label { min-width: 180px; font-weight: 600; }
    input, select, textarea { padding: 8px; width: 100%; max-width: 520px; }
    button { padding: 10px 14px; cursor: pointer; }
    .pill { padding: 2px 8px; border-radius: 9999px; background: #eef2ff; color: #1e3a8a; margin-right: 6px; display: inline-block; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-top: 12px; }
    .muted { color: #6b7280; font-size: 12px; }
    .success { color: #065f46; }
    .error { color: #b91c1c; }
    pre { white-space: pre-wrap; background: #111827; color: #e5e7eb; padding: 12px; border-radius: 6px; }
  </style>
</head>
<body>
  <h2>Génération manuelle de documents</h2>
  <p class="muted">Choisissez un modèle, fournissez l'étudiant et les champs requis, puis générez le PDF.</p>

  <div class="card">
    <div class="row">
      <label>Jeton d'API (Bearer):</label>
      <input id="token" type="password" placeholder="Collez votre token Sanctum ici" />
      <button id="saveToken">Enregistrer</button>
    </div>
    <div class="row">
      <label>Modèle:</label>
      <select id="modeleSelect"></select>
      <button id="reloadModeles">Recharger</button>
    </div>
    <div class="row">
      <label>Étudiant ID:</label>
      <input id="etudiantId" type="number" placeholder="ex: 1" />
    </div>
    <div class="row">
      <label>Date du jour:</label>
      <input id="dateJour" type="date" />
    </div>

    <div id="schema" class="card" style="display:none;">
      <div class="row">
        <label>Champs requis:</label>
        <div id="requis"></div>
      </div>
      <div class="row">
        <label>Placeholders détectés:</label>
        <div id="placeholders"></div>
      </div>
    </div>

    <div id="dynamicForm" class="card">
      <h4>Données supplémentaires</h4>
      <div class="grid" id="extraFields"></div>
    </div>

    <div class="row">
      <button id="generate">Générer le document</button>
    </div>
    <div id="status" class="muted"></div>
    <pre id="output" style="display:none;"></pre>
  </div>

  <script>
    const apiBase = '/api';
    const tokenInput = document.getElementById('token');
    const saveTokenBtn = document.getElementById('saveToken');
    const modeleSelect = document.getElementById('modeleSelect');
    const reloadBtn = document.getElementById('reloadModeles');
    const etudiantIdInput = document.getElementById('etudiantId');
    const dateJourInput = document.getElementById('dateJour');
    const schemaDiv = document.getElementById('schema');
    const requisDiv = document.getElementById('requis');
    const placeholdersDiv = document.getElementById('placeholders');
    const extraFields = document.getElementById('extraFields');
    const statusDiv = document.getElementById('status');
    const outputPre = document.getElementById('output');

    function getHeaders() {
      const t = localStorage.getItem('token') || '';
      return { 'Authorization': 'Bearer ' + t, 'Content-Type': 'application/json' };
    }

    saveTokenBtn.onclick = () => {
      localStorage.setItem('token', tokenInput.value);
      statusDiv.textContent = 'Token enregistré.';
    };

    reloadBtn.onclick = loadModeles;
    modeleSelect.onchange = loadSchema;
    document.getElementById('generate').onclick = generateDoc;
    dateJourInput.valueAsDate = new Date();

    async function loadModeles() {
      statusDiv.textContent = 'Chargement des modèles...';
      const res = await fetch(apiBase + '/admin/modeles', { headers: getHeaders() });
      if (!res.ok) { statusDiv.textContent = 'Erreur chargement modèles'; return; }
      const data = await res.json();
      modeleSelect.innerHTML = '';
      (data.data || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.nom + ' (' + m.type_document + ')';
        opt.dataset.type = m.type_document;
        modeleSelect.appendChild(opt);
      });
      statusDiv.textContent = 'Modèles chargés';
      if (modeleSelect.options.length) loadSchema();
    }

    async function loadSchema() {
      const id = modeleSelect.value;
      if (!id) return;
      const res = await fetch(apiBase + '/admin/modeles/' + id + '/schema', { headers: getHeaders() });
      if (!res.ok) { statusDiv.textContent = 'Erreur chargement schéma'; return; }
      const json = await res.json();
      const s = json.data || {};
      schemaDiv.style.display = 'block';
      requisDiv.innerHTML = '';
      (s.champs_requis || []).forEach(c => {
        const span = document.createElement('span'); span.className='pill'; span.textContent=c; requisDiv.appendChild(span);
      });
      placeholdersDiv.innerHTML = '';
      (s.placeholders || []).forEach(c => {
        const span = document.createElement('span'); span.className='pill'; span.textContent=c; placeholdersDiv.appendChild(span);
      });

      // Simple génération de champs texte pour placeholders (hors etudiant.* et date_du_jour qui ont des inputs dédiés)
      extraFields.innerHTML = '';
      (s.placeholders || []).filter(p => !p.startsWith('etudiant.') && p !== 'date_du_jour').forEach(p => {
        const wrap = document.createElement('div');
        const label = document.createElement('label'); label.textContent = p; label.style.display='block';
        const input = document.createElement('input'); input.dataset.field=p;
        wrap.appendChild(label); wrap.appendChild(input); extraFields.appendChild(wrap);
      });
    }

    function buildDonnees() {
      const donnees = {};
      const date = dateJourInput.value; if (date) donnees['date_du_jour'] = date;
      const etu = {};
      // etudiant.* champs optionnels (auto-remplis côté serveur si omis)
      document.querySelectorAll('[data-field^="etudiant."]').forEach(inp => {
        const path = inp.dataset.field.split('.');
        if (!path[1]) return;
        etu[path[1]] = inp.value;
      });
      if (Object.keys(etu).length) donnees['etudiant'] = etu;
      // autres placeholders
      document.querySelectorAll('#extraFields [data-field]').forEach(inp => {
        setByPath(donnees, inp.dataset.field, inp.value);
      });
      return donnees;
    }

    function setByPath(obj, path, val) {
      const parts = path.split('.');
      let cur = obj; for (let i=0;i<parts.length-1;i++) { const k=parts[i]; cur[k]=cur[k]||{}; cur=cur[k]; }
      cur[parts[parts.length-1]] = val;
    }

    async function generateDoc() {
      const id = modeleSelect.value; if (!id) return;
      const selected = modeleSelect.options[modeleSelect.selectedIndex];
      const type = selected.dataset.type;
      const etuId = etudiantIdInput.value;
      if (!etuId) { statusDiv.textContent='Veuillez saisir Étudiant ID'; return; }
      statusDiv.textContent='Génération en cours...'; outputPre.style.display='none';
      const res = await fetch(apiBase + '/admin/documents/generer', {
        method: 'POST', headers: getHeaders(),
        body: JSON.stringify({ type: type, etudiant_id: parseInt(etuId,10), donnees: buildDonnees() })
      });
      const json = await res.json();
      if (!res.ok || !json.success) { statusDiv.textContent='Erreur de génération'; outputPre.textContent=JSON.stringify(json,null,2); outputPre.style.display='block'; return; }
      statusDiv.textContent='Document généré (ID: '+json.data.id+')';
      outputPre.textContent = JSON.stringify(json.data, null, 2); outputPre.style.display='block';
    }

    // Champs fixes pour etudiant.*
    (function renderEtudiantFields(){
      const fields = ['etudiant.nom','etudiant.prenom','etudiant.numero_etudiant','etudiant.filiere','etudiant.annee'];
      const container = document.getElementById('extraFields');
      fields.forEach(p => {
        const wrap = document.createElement('div');
        const label = document.createElement('label'); label.textContent = p; label.style.display='block';
        const input = document.createElement('input'); input.dataset.field=p; input.placeholder='(optionnel, auto-rempli)';
        wrap.appendChild(label); wrap.appendChild(input); container.appendChild(wrap);
      });
    })();

    // Initial load
    loadModeles();
  </script>
</body>
</html>
