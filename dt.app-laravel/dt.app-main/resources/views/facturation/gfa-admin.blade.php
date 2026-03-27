<x-layouts::app :title="__('Gfa Admin')">
    <div class="gfa-admin-page flex h-full w-full flex-1 flex-col gap-6 pb-8">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .gfa-admin-page { overflow: visible; min-height: max-content; }
            .gfa-admin-page .tabs{display:flex;justify-content:center;gap:6px;flex-wrap:wrap;border-bottom:2px solid var(--dt-border);margin-bottom:22px;background:var(--dt-panel-bg);padding:0 8px 6px;overflow-x:auto;overflow-y:hidden;box-shadow:var(--dt-shadow)}
            .gfa-admin-page .tab{border:none;background:transparent;color:var(--dt-muted-text);font-size:14px;font-weight:600;padding:12px 16px;border-bottom:3px solid transparent;margin-bottom:-2px;display:inline-flex;align-items:center;gap:8px;cursor:pointer}
            .gfa-admin-page .tab.active{color:#4B49AC;border-bottom-color:#4B49AC}
            .gfa-admin-page .pane{display:none}.gfa-admin-page .pane.active{display:block}
            .gfa-admin-page .gfa-card{background:var(--dt-panel-bg);color:var(--dt-page-text);border-radius:12px;box-shadow:var(--dt-shadow);padding:24px;max-width:1100px;margin:0 auto 20px;border:1px solid var(--dt-border)}
            .gfa-admin-page .gfa-card h3{font-size:20px;font-weight:700;color:var(--dt-page-text);display:flex;gap:10px;justify-content:center;margin:0 0 18px}
            .gfa-admin-page .controls{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:16px;background:var(--dt-panel-alt-bg);padding:15px;border-radius:8px;border:1px solid var(--dt-border)}
            .gfa-admin-page .input,.gfa-admin-page .controls select,.gfa-admin-page .controls input[type="date"]{width:100%;border:1px solid var(--dt-input-border);background:var(--dt-input-bg);color:var(--dt-page-text);border-radius:8px;padding:8px 12px;font-size:13px;min-height:38px;box-sizing:border-box}
            .gfa-admin-page .btn{border:none;border-radius:7px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px}
            .gfa-admin-page .btn-primary{background:#4B49AC;color:#fff}.gfa-admin-page .btn-add{background:#4B49AC;color:#fff}.gfa-admin-page .btn-danger{background:#dc3545;color:#fff}
            .gfa-admin-page .btn-icon{padding:6px 10px;min-width:34px}
            .gfa-admin-page .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 16px}.gfa-admin-page .form-actions{margin-top:12px;display:flex;justify-content:flex-end}
            .gfa-admin-page .toolbar-row{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin-bottom:16px}
            .gfa-admin-page .toolbar-row .controls{flex:1;min-width:260px;margin-bottom:0}
            .gfa-admin-page .toolbar-row .toolbar-action{flex:0 0 auto}
            .gfa-admin-page .split-grid{display:grid;grid-template-columns:minmax(320px,.9fr) minmax(0,1.35fr);gap:16px;align-items:start}
            .gfa-admin-page .sub-card{border:1px solid var(--dt-border);border-radius:10px;padding:16px;background:var(--dt-panel-alt-bg)}
            .gfa-admin-page .stack{display:grid;gap:10px}
            .gfa-admin-page .actions{display:flex;flex-wrap:nowrap;gap:8px}
            .gfa-admin-page .table-meta{display:flex;justify-content:space-between;align-items:center;margin-top:12px;flex-wrap:wrap;gap:8px}
            .gfa-admin-page .password-toggle-group{position:relative}
            .gfa-admin-page .password-toggle-group .input{padding-right:5.6rem}
            .gfa-admin-page .password-toggle{position:absolute;top:50%;right:12px;transform:translateY(-50%);border:none;background:transparent;color:#818cf8;font-size:12px;font-weight:700;cursor:pointer;padding:4px}
            .gfa-admin-page .password-toggle:hover{opacity:.85}
            .gfa-admin-page .table-wrap{overflow:auto;border:1px solid var(--dt-border);border-radius:12px}.gfa-admin-page .table{width:100%;border-collapse:collapse}.gfa-admin-page .table th,.gfa-admin-page .table td{padding:12px 14px;border-top:1px solid var(--dt-border);text-align:left;font-size:13px;color:var(--dt-page-text)}.gfa-admin-page .table th{background:var(--dt-table-head-bg);border-top:none;font-size:12px}
            .gfa-admin-page .stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}.gfa-admin-page .stat{background:var(--dt-panel-alt-bg);border:1px solid var(--dt-border);border-radius:10px;padding:15px 10px;text-align:center}.gfa-admin-page .muted{color:var(--dt-muted-text);font-size:13px}.gfa-admin-page .center{text-align:center}.gfa-admin-page .empty{padding:24px;color:var(--dt-soft-text);text-align:center}
            .gfa-admin-page .status{display:none;padding:10px;border-radius:8px;font-size:13px;margin-bottom:12px}.gfa-admin-page .ok{background:var(--dt-success-bg);color:var(--dt-success-text);border:1px solid var(--dt-success-border)}.gfa-admin-page .err{background:var(--dt-danger-bg);color:var(--dt-danger-text);border:1px solid var(--dt-danger-border)}
            @media (max-width:992px){.gfa-admin-page .grid,.gfa-admin-page .stats,.gfa-admin-page .split-grid{grid-template-columns:1fr}.gfa-admin-page .toolbar-row{flex-direction:column;align-items:stretch}.gfa-admin-page .toolbar-row .toolbar-action{width:100%}}
        </style>

        <div class="center" style="margin-bottom:25px;">
            <h1 style="margin:0;font-weight:700;display:flex;justify-content:center;gap:10px;"><i class="fas fa-layer-group" style="color:#4B49AC"></i>Gfa Admin</h1>
        </div>

        <div class="tabs">
            <button class="tab active" data-pane="global"><i class="fas fa-chart-bar"></i> Vue globale</button>
            <button class="tab" data-pane="services"><i class="fas fa-briefcase"></i> Services</button>
            <button class="tab" data-pane="guichets"><i class="fas fa-desktop"></i> Guichets</button>
            <button class="tab" data-pane="agents"><i class="fas fa-user-tie"></i> Agents</button>
            <button class="tab" data-pane="tickets"><i class="fas fa-ticket-alt"></i> Tickets</button>
            <button class="tab" data-pane="parametres"><i class="fas fa-cog"></i> Parametres</button>
            <button class="tab" data-pane="ecran"><i class="fas fa-tv"></i> Ecran</button>
        </div>

        <div id="global" class="pane active"><div class="gfa-card"><h3><i class="fas fa-chart-line"></i> Statistiques Temps Reel</h3><div id="stats-box"></div></div></div>

        <div id="services" class="pane"><div class="split-grid"><div class="gfa-card"><h3><i class="fas fa-plus-circle"></i> Enregistrement service</h3><div id="service-status" class="status"></div><div class="sub-card"><div class="grid"><div><label>Nom du service *</label><input id="service-name" class="input"></div><div><label>Prefixe</label><input id="service-prefix" class="input"></div></div><div class="form-actions"><button class="btn btn-add" type="button" onclick="createService()"><i class="fas fa-plus"></i> Enregistrer</button></div></div></div><div class="gfa-card"><h3><i class="fas fa-briefcase"></i> Liste des services</h3><div id="service-list-status" class="status"></div><div class="toolbar-row"><div class="controls"><input id="service-search" class="input" placeholder="Rechercher un service..." style="max-width:300px"></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Nom</th><th>Prefixe</th><th>Actions</th></tr></thead><tbody id="services-body"></tbody></table></div></div></div></div>

        <div id="guichets" class="pane"><div class="split-grid"><div class="gfa-card"><h3><i class="fas fa-plus-circle"></i> Enregistrement guichet</h3><div id="guichet-status" class="status"></div><div class="sub-card"><div class="grid"><div><label>Numero *</label><input id="guichet-numero" class="input"></div><div><label>Informations</label><input id="guichet-infos" class="input"></div><div style="grid-column:1/-1"><label>Service rattache</label><select id="guichet-service" class="input"><option value="">Aucun</option></select></div></div><div class="form-actions"><button class="btn btn-add" type="button" onclick="createGuichet()"><i class="fas fa-plus"></i> Enregistrer</button></div></div></div><div class="gfa-card"><h3><i class="fas fa-desktop"></i> Liste des guichets</h3><div id="guichet-list-status" class="status"></div><div class="toolbar-row"><div class="controls"><input id="guichet-search" class="input" placeholder="Rechercher un guichet..." style="max-width:300px"></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Numero</th><th>Informations</th><th>Service</th><th>Actions</th></tr></thead><tbody id="guichets-body"></tbody></table></div><div class="table-meta"><span id="guichets-count" class="muted"></span><div style="display:flex;gap:8px;align-items:center"><button type="button" class="btn btn-primary" id="guichets-prev">Precedent</button><span id="guichets-page" class="muted"></span><button type="button" class="btn btn-primary" id="guichets-next">Suivant</button></div></div></div></div></div>

        <div id="agents" class="pane"><div class="split-grid"><div class="gfa-card"><h3><i class="fas fa-plus-circle"></i> Enregistrement agent</h3><div id="agent-status" class="status"></div><div class="sub-card"><div class="grid"><div><label>Nom *</label><input id="agent-nom" class="input"></div><div><label>Prenom</label><input id="agent-prenom" class="input"></div><div><label>Service *</label><select id="agent-service" class="input"></select></div><div><label>Guichet *</label><select id="agent-guichet" class="input"></select></div></div><div class="form-actions"><button class="btn btn-add" type="button" onclick="createAgent()"><i class="fas fa-plus"></i> Enregistrer</button></div></div></div><div class="gfa-card"><h3><i class="fas fa-user-tie"></i> Liste des agents</h3><div id="agent-list-status" class="status"></div><div class="toolbar-row"><div class="controls"><input id="agent-search" class="input" placeholder="Rechercher un agent..." style="max-width:300px"></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Nom</th><th>Prenom</th><th>Service</th><th>Guichet</th><th>Actions</th></tr></thead><tbody id="agents-body"></tbody></table></div><div class="table-meta"><span id="agents-count" class="muted"></span><div style="display:flex;gap:8px;align-items:center"><button type="button" class="btn btn-primary" id="agents-prev">Precedent</button><span id="agents-page" class="muted"></span><button type="button" class="btn btn-primary" id="agents-next">Suivant</button></div></div></div></div></div>

        <div id="tickets" class="pane"><div class="gfa-card"><h3><i class="fas fa-ticket-alt"></i> Suivi des tickets</h3><div class="controls"><input id="ticket-search" class="input" placeholder="Rechercher..." style="flex:1;min-width:200px"><select id="filter-guichet" style="width:auto"></select><select id="filter-statut" style="width:auto"><option value="">Tous statuts</option><option value="EN_ATTENTE">En attente</option><option value="EN_COURS">En cours</option><option value="TERMINE">Termine</option><option value="INCOMPLET">Incomplet</option><option value="ABSENT">Absent</option></select><input type="date" id="filter-date" style="width:auto"><button type="button" class="btn btn-add" onclick="exportTickets()"><i class="fas fa-file-excel"></i> Export Excel</button><button type="button" class="btn btn-danger" onclick="truncateTickets()"><i class="fas fa-trash"></i> Vider tickets</button></div><div class="table-wrap"><table class="table"><thead><tr><th>Numero</th><th>Service</th><th>Agent</th><th>Creation</th><th>Appel</th><th>Cloture</th><th>Temps attente</th><th>Temps traitement</th><th>Statut</th></tr></thead><tbody id="tickets-body"></tbody></table></div><div class="table-meta"><span id="tickets-count" class="muted"></span><div style="display:flex;gap:8px;align-items:center"><button type="button" class="btn btn-primary" id="tickets-prev">Precedent</button><span id="tickets-page" class="muted"></span><button type="button" class="btn btn-primary" id="tickets-next">Suivant</button></div></div></div></div>

        <div id="parametres" class="pane"><div class="gfa-card"><h3><i class="fas fa-wifi"></i> Parametres Wi-Fi</h3><p class="muted center">Configuration du point d'acces pour le QR Code de l'ecran public.</p><form action="/facturation/gfa-admin/wifi-settings" method="post" style="max-width:500px;margin:20px auto;display:grid;gap:16px">@csrf<div><label>Nom du reseau (SSID)</label><input name="ssid" class="input" required placeholder="Ex: DakarTerminal_Guest" value="{{ $wifiSettings->ssid ?? '' }}"></div><div><label>Mot de passe</label><div class="password-toggle-group"><input id="wifi-password" type="password" name="password" class="input" placeholder="Laisser vide si ouvert" value="{{ $wifiSettings->password ?? '' }}"><button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="wifi-password" aria-label="Afficher le mot de passe">Voir</button></div></div><button type="submit" class="btn btn-primary" style="justify-content:center"><i class="fas fa-save"></i> Enregistrer les parametres</button></form></div></div>

        <div id="ecran" class="pane"><div class="gfa-card center"><h3><i class="fas fa-tv"></i> Affichage Public</h3><p class="muted" style="margin-bottom:25px;">Lancer l'interface de file d'attente pour les ecrans du hall.</p><a class="btn btn-primary" href="{{ route('facturation.gfa-admin.public') }}" target="_blank">Ouvrir l'ecran d'appel</a></div></div>

        <script>
            const store = { services: [], guichets: [], agents: [], tickets: [], stats: [] };
            const $ = id => document.getElementById(id);
            const lower = v => (v || '').toString().toLowerCase();
            const fmt = v => v || '-';
            const formatDuration = value => {
                if (value === null || value === undefined || value === '') return '-';
                const totalSeconds = Number(value);
                if (Number.isNaN(totalSeconds)) return '-';
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                if (hours > 0) return `${hours}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
                if (minutes > 0) return `${minutes}m ${String(seconds).padStart(2, '0')}s`;
                return `${seconds}s`;
            };
            const showStatus = (id, msg, ok) => { const el = $(id); el.textContent = msg; el.className = `status ${ok ? 'ok' : 'err'}`; el.style.display = 'block'; };
            const csrfHeaders = (extra = {}) => ({ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, ...extra });
            const json = async url => { const r = await fetch(url); if (!r.ok) throw new Error('api'); return await r.json(); };
            const tabs = document.querySelectorAll('.gfa-admin-page .tab');
            tabs.forEach(t => t.addEventListener('click', () => { tabs.forEach(x => x.classList.remove('active')); document.querySelectorAll('.gfa-admin-page .pane').forEach(p => p.classList.remove('active')); t.classList.add('active'); $(t.dataset.pane).classList.add('active'); }));
            document.querySelectorAll('[data-password-toggle]').forEach(button => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.getAttribute('aria-controls'));
                    if (!input) return;
                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    button.textContent = shouldShow ? button.dataset.hideLabel : button.dataset.showLabel;
                    button.setAttribute('aria-label', shouldShow ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                });
            });
            function syncOptions() { $('guichet-service').innerHTML = '<option value="">Aucun</option>' + store.services.map(s => `<option value="${s.id}">${s.nom}</option>`).join(''); $('agent-service').innerHTML = store.services.map(s => `<option value="${s.id}">${s.nom}</option>`).join(''); $('agent-guichet').innerHTML = store.guichets.map(g => `<option value="${g.id}">${g.numero}</option>`).join(''); $('filter-guichet').innerHTML = '<option value="">Tous les guichets</option>' + store.guichets.map(g => `<option value="${g.id}">${g.numero}</option>`).join(''); }
            function renderStats() { $('stats-box').innerHTML = store.stats.length ? store.stats.map(s => `<div style="margin-bottom:25px"><div style="font-size:12px;font-weight:800;letter-spacing:1px;color:#4B49AC;margin-bottom:12px;text-transform:uppercase;text-align:left;padding-left:5px;border-left:4px solid #4B49AC;">${s.serviceNom}</div><div class="stats"><div class="stat"><div class="muted">Attente</div><div style="font-size:24px;font-weight:800">${s.enAttente}</div></div><div class="stat"><div class="muted">En cours</div><div style="font-size:24px;font-weight:800">${s.enCours}</div></div><div class="stat"><div class="muted">Termines</div><div style="font-size:24px;font-weight:800">${s.termine}</div></div><div class="stat"><div class="muted">Incomplets</div><div style="font-size:24px;font-weight:800">${s.incomplet}</div></div><div class="stat"><div class="muted">Absents</div><div style="font-size:24px;font-weight:800">${s.absent}</div></div></div></div>`).join('') : '<p class="empty">Aucune donnee disponible.</p>'; }
            function renderServices() {
                const q = lower($('service-search').value);
                const rows = store.services.filter(s => lower(s.nom).includes(q) || lower(s.prefixe).includes(q));
                $('services-body').innerHTML = rows.length ? rows.map(s => `<tr><td><input id="service-name-${s.id}" class="input" value="${s.nom ?? ''}"></td><td><input id="service-prefix-${s.id}" class="input" value="${s.prefixe ?? ''}"></td><td><div class="actions"><button type="button" class="btn btn-primary btn-icon" onclick="updateService(${s.id})" title="Modifier"><i class="fas fa-pen"></i></button><button type="button" class="btn btn-danger btn-icon" onclick="deleteService(${s.id})" title="Supprimer"><i class="fas fa-trash"></i></button></div></td></tr>`).join('') : '<tr><td colspan="3" class="empty">Aucun service</td></tr>';
            }
            function renderGuichets() {
                const q = lower($('guichet-search').value);
                const filtered = store.guichets.filter(g => [g.numero, g.infos, g.serviceNom].some(v => lower(v).includes(q)));
                const totalPages = Math.max(1, Math.ceil(filtered.length / guichetPageSize));
                if (guichetPage >= totalPages) guichetPage = totalPages - 1;
                const rows = filtered.slice(guichetPage * guichetPageSize, (guichetPage + 1) * guichetPageSize);
                const serviceOptions = '<option value="">Aucun</option>' + store.services.map(s => `<option value="${s.id}">${s.nom}</option>`).join('');
                $('guichets-body').innerHTML = rows.length ? rows.map(g => `<tr><td><input id="guichet-numero-${g.id}" class="input" value="${g.numero ?? ''}"></td><td><input id="guichet-infos-${g.id}" class="input" value="${g.infos ?? ''}"></td><td><select id="guichet-service-${g.id}" class="input">${serviceOptions}</select></td><td><div class="actions"><button type="button" class="btn btn-primary btn-icon" onclick="updateGuichet(${g.id})" title="Modifier"><i class="fas fa-pen"></i></button><button type="button" class="btn btn-danger btn-icon" onclick="deleteGuichet(${g.id})" title="Supprimer"><i class="fas fa-trash"></i></button></div></td></tr>`).join('') : '<tr><td colspan="4" class="empty">Aucun guichet</td></tr>';
                rows.forEach(g => { const select = $(`guichet-service-${g.id}`); if (select) select.value = g.serviceId || ''; });
                $('guichets-count').textContent = `${filtered.length} guichet(s)`;
                $('guichets-page').textContent = `Page ${guichetPage + 1} / ${totalPages}`;
                $('guichets-prev').disabled = guichetPage === 0;
                $('guichets-next').disabled = guichetPage >= totalPages - 1;
            }
            let guichetPage = 0;
            const guichetPageSize = 5;
            let agentPage = 0;
            const agentPageSize = 5;
            let ticketPage = 0;
            const ticketPageSize = 5;
            function renderAgents() {
                const q = lower($('agent-search').value);
                const filtered = store.agents.filter(a => [a.nom, a.prenom, a.serviceNom, a.guichetNumero].some(v => lower(v).includes(q)));
                const totalPages = Math.max(1, Math.ceil(filtered.length / agentPageSize));
                if (agentPage >= totalPages) agentPage = totalPages - 1;
                const rows = filtered.slice(agentPage * agentPageSize, (agentPage + 1) * agentPageSize);
                const serviceOptions = store.services.map(s => `<option value="${s.id}">${s.nom}</option>`).join('');
                const guichetOptions = store.guichets.map(g => `<option value="${g.id}">${g.numero}</option>`).join('');
                $('agents-body').innerHTML = rows.length ? rows.map(a => `<tr><td><input id="agent-nom-${a.id}" class="input" value="${a.nom ?? ''}"></td><td><input id="agent-prenom-${a.id}" class="input" value="${a.prenom ?? ''}"></td><td><select id="agent-service-${a.id}" class="input">${serviceOptions}</select></td><td><select id="agent-guichet-${a.id}" class="input">${guichetOptions}</select></td><td><div class="actions"><button type="button" class="btn btn-primary btn-icon" onclick="updateAgent(${a.id})" title="Modifier"><i class="fas fa-pen"></i></button><button type="button" class="btn btn-danger btn-icon" onclick="deleteAgent(${a.id})" title="Supprimer"><i class="fas fa-trash"></i></button></div></td></tr>`).join('') : '<tr><td colspan="5" class="empty">Aucun agent</td></tr>';
                rows.forEach(a => {
                    const serviceSelect = $(`agent-service-${a.id}`);
                    const guichetSelect = $(`agent-guichet-${a.id}`);
                    if (serviceSelect) serviceSelect.value = a.serviceId || '';
                    if (guichetSelect) guichetSelect.value = a.guichetId || '';
                });
                $('agents-count').textContent = `${filtered.length} agent(s)`;
                $('agents-page').textContent = `Page ${agentPage + 1} / ${totalPages}`;
                $('agents-prev').disabled = agentPage === 0;
                $('agents-next').disabled = agentPage >= totalPages - 1;
            }
            function renderTickets() {
                const q = lower($('ticket-search').value);
                const filtered = store.tickets.filter(t => [t.id, t.numero, t.serviceNom, t.guichetNumero, t.agentNom, t.statut, t.waitingTime, t.processingTime, t.calledAt].some(v => lower(v).includes(q)));
                const totalPages = Math.max(1, Math.ceil(filtered.length / ticketPageSize));
                if (ticketPage >= totalPages) ticketPage = totalPages - 1;
                const rows = filtered.slice(ticketPage * ticketPageSize, (ticketPage + 1) * ticketPageSize);
                $('tickets-body').innerHTML = rows.length ? rows.map(t => `<tr><td>${fmt(t.numero)}</td><td>${fmt(t.serviceNom)}</td><td>${fmt(t.agentNom)}</td><td>${t.createdAt ? new Date(t.createdAt).toLocaleString('fr-FR') : '-'}</td><td>${t.calledAt ? new Date(t.calledAt).toLocaleString('fr-FR') : '-'}</td><td>${t.closedAt ? new Date(t.closedAt).toLocaleString('fr-FR') : '-'}</td><td>${formatDuration(t.waitingTime)}</td><td>${formatDuration(t.processingTime)}</td><td>${fmt(t.statut)}</td></tr>`).join('') : '<tr><td colspan="9" class="empty">Aucun ticket</td></tr>';
                $('tickets-count').textContent = `${filtered.length} ticket(s)`;
                $('tickets-page').textContent = `Page ${ticketPage + 1} / ${totalPages}`;
                $('tickets-prev').disabled = ticketPage === 0;
                $('tickets-next').disabled = ticketPage >= totalPages - 1;
            }
            async function createService() { try { await fetch('/gfa/api/services', { method:'POST', headers:csrfHeaders({ 'Content-Type':'application/json' }), body:JSON.stringify({ nom:$('service-name').value.trim(), prefixe:$('service-prefix').value.trim() }) }); showStatus('service-status', 'Service ajoute avec succes.', true); await loadServices(); } catch (e) { showStatus('service-status', 'Erreur lors de la creation.', false); } }
            async function createGuichet() { try { await fetch('/gfa/api/guichets', { method:'POST', headers:csrfHeaders({ 'Content-Type':'application/json' }), body:JSON.stringify({ numero:$('guichet-numero').value.trim(), infos:$('guichet-infos').value.trim(), serviceId:$('guichet-service').value || null }) }); showStatus('guichet-status', 'Guichet ajoute avec succes.', true); await loadGuichets(); await loadAgents(); } catch (e) { showStatus('guichet-status', 'Erreur lors de la creation.', false); } }
            async function createAgent() { try { await fetch('/gfa/api/agents', { method:'POST', headers:csrfHeaders({ 'Content-Type':'application/json' }), body:JSON.stringify({ nom:$('agent-nom').value.trim(), prenom:$('agent-prenom').value.trim(), serviceId:$('agent-service').value || null, guichetId:$('agent-guichet').value || null }) }); showStatus('agent-status', 'Agent ajoute avec succes.', true); await loadAgents(); } catch (e) { showStatus('agent-status', 'Erreur lors de la creation.', false); } }
            const swalDark = { background:'#1a1e2e', color:'#e2e8f0', cancelButtonColor:'#374151' };
            const swalSave = { ...swalDark, icon:'question', showCancelButton:true, confirmButtonText:'Enregistrer', cancelButtonText:'Annuler', confirmButtonColor:'#4B49AC' };
            const swalDel  = { ...swalDark, icon:'warning', showCancelButton:true, confirmButtonText:'Supprimer', cancelButtonText:'Annuler', confirmButtonColor:'#dc3545' };
            async function updateService(id) {
                const r = await Swal.fire({ ...swalSave, title:'Modifier ce service ?' }); if (!r.isConfirmed) return;
                try { await fetch(`/gfa/api/services/${id}`, { method:'PUT', headers:csrfHeaders({ 'Content-Type':'application/json' }), body:JSON.stringify({ nom:$(`service-name-${id}`).value.trim(), prefixe:$(`service-prefix-${id}`).value.trim() }) }); showStatus('service-list-status', 'Service modifie avec succes.', true); await loadServices(); } catch (e) { showStatus('service-list-status', 'Erreur lors de la modification.', false); }
            }
            async function deleteService(id) {
                const r = await Swal.fire({ ...swalDel, title:'Supprimer ce service ?' }); if (!r.isConfirmed) return;
                try { await fetch(`/gfa/api/services/${id}`, { method:'DELETE', headers:csrfHeaders() }); showStatus('service-list-status', 'Service supprime avec succes.', true); await loadServices(); } catch (e) { showStatus('service-list-status', 'Erreur lors de la suppression.', false); }
            }
            async function updateGuichet(id) {
                const r = await Swal.fire({ ...swalSave, title:'Modifier ce guichet ?' }); if (!r.isConfirmed) return;
                try { await fetch(`/gfa/api/guichets/${id}`, { method:'PUT', headers:csrfHeaders({ 'Content-Type':'application/json' }), body:JSON.stringify({ numero:$(`guichet-numero-${id}`).value.trim(), infos:$(`guichet-infos-${id}`).value.trim(), serviceId:$(`guichet-service-${id}`).value || null }) }); showStatus('guichet-list-status', 'Guichet modifie avec succes.', true); await loadGuichets(); } catch (e) { showStatus('guichet-list-status', 'Erreur lors de la modification.', false); }
            }
            async function deleteGuichet(id) {
                const r = await Swal.fire({ ...swalDel, title:'Supprimer ce guichet ?' }); if (!r.isConfirmed) return;
                try { await fetch(`/gfa/api/guichets/${id}`, { method:'DELETE', headers:csrfHeaders() }); showStatus('guichet-list-status', 'Guichet supprime avec succes.', true); await loadGuichets(); await loadAgents(); } catch (e) { showStatus('guichet-list-status', 'Erreur lors de la suppression.', false); }
            }
            async function updateAgent(id) {
                const r = await Swal.fire({ ...swalSave, title:'Modifier cet agent ?' }); if (!r.isConfirmed) return;
                try { await fetch(`/gfa/api/agents/${id}`, { method:'PUT', headers:csrfHeaders({ 'Content-Type':'application/json' }), body:JSON.stringify({ nom:$(`agent-nom-${id}`).value.trim(), prenom:$(`agent-prenom-${id}`).value.trim(), serviceId:$(`agent-service-${id}`).value || null, guichetId:$(`agent-guichet-${id}`).value || null }) }); showStatus('agent-list-status', 'Agent modifie avec succes.', true); await loadAgents(); } catch (e) { showStatus('agent-list-status', 'Erreur lors de la modification.', false); }
            }
            async function deleteAgent(id) {
                const r = await Swal.fire({ ...swalDel, title:'Supprimer cet agent ?' }); if (!r.isConfirmed) return;
                try { await fetch(`/gfa/api/agents/${id}`, { method:'DELETE', headers:csrfHeaders() }); showStatus('agent-list-status', 'Agent supprime avec succes.', true); await loadAgents(); } catch (e) { showStatus('agent-list-status', 'Erreur lors de la suppression.', false); }
            }
            async function exportTickets() { const params = new URLSearchParams(); if ($('filter-guichet').value) params.set('guichetId', $('filter-guichet').value); if ($('filter-statut').value) params.set('statut', $('filter-statut').value); if ($('filter-date').value) params.set('date', $('filter-date').value); const res = await fetch('/gfa/api/tickets/export' + (params.toString() ? '?' + params.toString() : '')); if (!res.ok) { await Swal.fire({ icon:'error', title:'Erreur', text:'Impossible de telecharger le fichier Excel.' }); return; } const blob = await res.blob(); const url = window.URL.createObjectURL(blob); const a = document.createElement('a'); a.href = url; a.download = `tickets-gfa-${new Date().toISOString().slice(0,10)}.xlsx`; document.body.appendChild(a); a.click(); a.remove(); window.URL.revokeObjectURL(url); }
            async function truncateTickets() { const result = await Swal.fire({ ...swalDel, title:'Vider tous les tickets ?', text:'Cette action supprimera definitivement tous les tickets.', confirmButtonText:'Oui, vider' }); if (!result.isConfirmed) return; const response = await fetch('/gfa/api/tickets/truncate', { method:'DELETE', headers:csrfHeaders() }); if (!response.ok) { await Swal.fire({ ...swalDark, icon:'error', title:'Erreur', text:'La suppression des tickets a echoue.' }); return; } await Swal.fire({ ...swalDark, icon:'success', title:'Succes', text:'La table des tickets a ete videe.', confirmButtonColor:'#4B49AC' }); await loadTickets(); await loadStats(); }
            async function loadServices() { store.services = await json('/gfa/api/services'); syncOptions(); renderServices(); }
            async function loadGuichets() { store.guichets = await json('/gfa/api/guichets'); syncOptions(); renderGuichets(); }
            async function loadAgents() { store.agents = await json('/gfa/api/agents'); renderAgents(); }
            async function loadStats() { store.stats = await json('/gfa/api/stats'); renderStats(); }
            async function loadTickets() { const params = new URLSearchParams(); if ($('filter-guichet').value) params.set('guichetId', $('filter-guichet').value); if ($('filter-statut').value) params.set('statut', $('filter-statut').value); if ($('filter-date').value) params.set('date', $('filter-date').value); store.tickets = await json('/gfa/api/tickets' + (params.toString() ? '?' + params.toString() : '')); renderTickets(); }
            $('guichets-prev').addEventListener('click', () => { if (guichetPage > 0) { guichetPage--; renderGuichets(); } });
            $('guichets-next').addEventListener('click', () => { guichetPage++; renderGuichets(); });
            $('guichet-search').addEventListener('input', () => { guichetPage = 0; renderGuichets(); });
            $('agents-prev').addEventListener('click', () => { if (agentPage > 0) { agentPage--; renderAgents(); } });
            $('agents-next').addEventListener('click', () => { agentPage++; renderAgents(); });
            $('agent-search').addEventListener('input', () => { agentPage = 0; renderAgents(); });
            $('tickets-prev').addEventListener('click', () => { if (ticketPage > 0) { ticketPage--; renderTickets(); } });
            $('tickets-next').addEventListener('click', () => { ticketPage++; renderTickets(); });
            $('ticket-search').addEventListener('input', () => { ticketPage = 0; renderTickets(); });
            $('service-search').addEventListener('input', renderServices); $('filter-guichet').addEventListener('change', () => { ticketPage = 0; loadTickets(); }); $('filter-statut').addEventListener('change', () => { ticketPage = 0; loadTickets(); }); $('filter-date').addEventListener('change', () => { ticketPage = 0; loadTickets(); });
            Promise.resolve().then(async () => { await Promise.all([loadServices(), loadGuichets(), loadAgents(), loadStats(), loadTickets()]); });
        </script>
    </div>
</x-layouts::app>
