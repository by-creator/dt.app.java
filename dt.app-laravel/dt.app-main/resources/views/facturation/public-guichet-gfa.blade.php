<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guichet GFA - {{ config('app.name', 'Laravel') }}</title>
    @include('partials.app-icons')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --primary:#4B49AC; --primary-dark:#3e3d99; --bg:#f4f6fb; --text:#191c24; --border:#dee2e6; --shadow:0 2px 12px rgba(0,0,0,.06); }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; font-family:'Instrument Sans',sans-serif; background:radial-gradient(circle at top left, rgba(75,73,172,0.10), transparent 28%), radial-gradient(circle at top right, rgba(40,167,69,0.08), transparent 22%), var(--bg); color:var(--text); }
        main { width:min(1180px, calc(100vw - 32px)); margin:0 auto; padding:28px 0 40px; }
        .page-header { margin-bottom:20px; }
        .page-header h1 { display:flex; align-items:center; gap:10px; margin:0; font-size:clamp(1.8rem,2vw,2.35rem); font-weight:800; }
        .guichet-header-card { background:#fff; border-radius:12px; box-shadow:var(--shadow); padding:28px 24px 22px; text-align:center; margin-bottom:20px; }
        .guichet-selects { display:flex; justify-content:center; margin-bottom:16px; }
        .guichet-selects select { border:1px solid #d0d0e8; border-radius:8px; padding:9px 16px; font-size:14px; color:#343a40; background:#fff; min-width:220px; }
        .client-en-cours { font-size:26px; font-weight:800; margin-bottom:6px; transition:color .3s; }
        .client-en-cours.active { color:var(--primary); }
        .client-attente-msg { font-size:13px; color:#9e9e9e; }
        .service-badge { display:inline-block; background:#f0f0fb; color:var(--primary); border-radius:6px; padding:3px 12px; font-size:12px; font-weight:700; letter-spacing:1px; margin-bottom:10px; }
        .guichet-alert { display:none; background:#fff3cd; border:1px solid #ffc107; border-radius:8px; padding:10px 18px; font-size:13px; color:#856404; text-align:center; margin-bottom:14px; }
        .guichet-alert.show { display:block; }
        .action-buttons { display:flex; flex-wrap:wrap; justify-content:center; gap:12px; margin-bottom:14px; }
        .btn-action { display:flex; align-items:center; gap:8px; padding:10px 24px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; color:#fff; transition:opacity .2s, transform .1s, box-shadow .2s; }
        .btn-action:disabled { opacity:.35; cursor:not-allowed; transform:none !important; box-shadow:none !important; }
        .btn-action:not(:disabled) { box-shadow:0 4px 14px rgba(0,0,0,.22); }
        .btn-action:not(:disabled):hover { opacity:.88; transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.28); }
        .btn-suivant { background:#4340c4; } .btn-rappel { background:#3d4a56; } .btn-termine { background:#1a9e3f; }
        .btn-incomplet { background:#d48f00; color:#fff !important; } .btn-absent { background:#c82333; }
        .guichet-bottom { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .queue-card, .guide-card { background:#fff; border-radius:12px; box-shadow:var(--shadow); padding:24px; }
        .queue-title { font-size:14px; font-weight:700; margin-bottom:14px; }
        .guichet-tabs { display:flex; border-bottom:1px solid var(--border); margin-bottom:18px; }
        .tab-item { padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; border:none; border-bottom:2px solid transparent; color:#6c757d; background:none; }
        .tab-item.active { color:var(--primary); border-bottom-color:var(--primary); }
        .tab-pane { display:none; } .tab-pane.active { display:block; }
        .tab-empty-msg, .ticket-info { font-size:13px; color:#555; margin-bottom:12px; }
        .btn-ticket { background:var(--primary); color:#fff; border:none; border-radius:6px; padding:9px 20px; font-size:13px; font-weight:700; cursor:pointer; }
        .queue-list { list-style:none; padding:0; margin:0; }
        .queue-item { display:flex; align-items:center; gap:10px; padding:7px 0; border-bottom:1px solid #f2f2f7; font-size:13px; }
        .queue-item:last-child { border-bottom:none; }
        .q-num { font-weight:700; color:var(--primary); min-width:70px; }
        .q-time { color:#aaa; font-size:11px; margin-left:auto; }
        .guide-title { font-size:14px; font-weight:700; margin-bottom:14px; }
        .guide-card ul { list-style:none; padding:0; margin:0; }
        .guide-card li { font-size:13px; color:#555; padding:5px 0; display:flex; gap:6px; }
        .guide-card li::before { content:'•'; color:var(--primary); font-weight:700; }
        .ws-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:4px; background:#dc3545; transition:background .3s; }
        .ws-dot.connected { background:#28a745; }
        .ws-status { font-size:11px; color:#aaa; text-align:center; margin-top:8px; }
        @media (max-width:768px) {
            main { width:min(100vw - 24px, 1180px); padding-top:20px; }
            .guichet-bottom { grid-template-columns:1fr; }
            .btn-action { justify-content:center; width:100%; }
            .queue-item { align-items:flex-start; flex-wrap:wrap; }
            .q-time { margin-left:0; width:100%; padding-left:80px; }
        }
    </style>
</head>
<body>
<main>
    <div class="page-header">
        <h1><i class="fas fa-desktop" style="color:#4B49AC"></i>Guichet</h1>
    </div>
    <div class="guichet-header-card">
        <div class="guichet-selects">
            <select id="sel-guichet" onchange="onGuichetChange()">
                <option value="">-- Selectionner un guichet --</option>
            </select>
        </div>
        <div id="service-badge-display" class="service-badge" style="display:none"></div>
        <div class="client-en-cours" id="client-en-cours">Client en cours : &mdash;</div>
        <div class="client-attente-msg" id="attente-msg">Aucun client en attente</div>
        <div class="ws-status"><span class="ws-dot" id="ws-dot"></span><span id="ws-label">Non connecte</span></div>
    </div>
    <div id="guichet-alert" class="guichet-alert"><i class="fas fa-exclamation-triangle"></i> <span id="alert-msg">Veuillez selectionner un guichet avant d'utiliser les boutons.</span></div>
    <div class="action-buttons">
        <button id="btn-suivant" class="btn-action btn-suivant" onclick="actionSuivant()" disabled><i class="fas fa-bell"></i> Suivant</button>
        <button id="btn-rappel" class="btn-action btn-rappel" onclick="actionRappel()" disabled><i class="fas fa-redo"></i> Rappel</button>
        <button id="btn-termine" class="btn-action btn-termine" onclick="actionTermine()" disabled><i class="fas fa-check-square"></i> Termine</button>
        <button id="btn-incomplet" class="btn-action btn-incomplet" onclick="actionIncomplet()" disabled><i class="fas fa-exclamation"></i> Incomplet</button>
        <button id="btn-absent" class="btn-action btn-absent" onclick="actionAbsent()" disabled><i class="fas fa-times-circle"></i> Absent</button>
    </div>
    <div class="guichet-bottom">
        <div class="queue-card">
            <div class="queue-title" id="queue-title">CLIENT(S) EN ATTENTE : 0</div>
            <div class="guichet-tabs">
                <button class="tab-item active" onclick="switchTab(event, 'tab-client')">Client</button>
                <button class="tab-item" onclick="switchTab(event, 'tab-personnel')">Personnel</button>
                <button class="tab-item" onclick="switchTab(event, 'tab-rapports')">Rapports</button>
            </div>
            <div id="tab-client" class="tab-pane active">
                <ul class="queue-list" id="queue-list"><li><p class="tab-empty-msg">Aucun client en attente</p></li></ul>
                <p class="ticket-info">Pour les clients qui ne peuvent pas scanner :</p>
                <button class="btn-ticket" id="btn-ticket" onclick="openTicketModal()">TICKET</button>
            </div>
            <div id="tab-personnel" class="tab-pane"><p class="tab-empty-msg">Aucun personnel en attente.</p></div>
            <div id="tab-rapports" class="tab-pane"><p class="tab-empty-msg">Aucun rapport disponible.</p></div>
        </div>
        <div class="guide-card">
            <div class="guide-title">Guide d'utilisation</div>
            <ul>
                <li><span><strong>Suivant</strong> : appeler le prochain client</span></li>
                <li><span><strong>Rappel</strong> : rappeler le client en cours</span></li>
                <li><span><strong>Incomplet</strong> : dossier incomplet</span></li>
                <li><span><strong>Termine</strong> : traitement termine</span></li>
                <li><span><strong>Absent</strong> : client absent</span></li>
            </ul>
        </div>
    </div>
</main>
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
    const realtimeConfig = { key: @js(config('services.pusher.key')), cluster: @js(config('services.pusher.cluster')) };
    let guichetId = null;
    let serviceId = null;
    let currentTicket = null;
    let pusher = null;
    let queueChannel = null;
    let currentChannel = null;
    let alertTimer = null;
    const csrfHeaders = (extra = {}) => ({ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, ...extra });

    async function init() {
        const response = await fetch('/gfa/api/guichets');
        if (!response.ok) return;
        const guichets = await response.json();
        const select = document.getElementById('sel-guichet');
        guichets.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.numero;
            select.appendChild(option);
        });
    }

    function switchTab(event, tabId) {
        document.querySelectorAll('.tab-item').forEach((tab) => tab.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach((pane) => pane.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    async function onGuichetChange() {
        guichetId = document.getElementById('sel-guichet').value || null;
        unsubscribeRealtimeChannels();
        serviceId = null;
        currentTicket = null;
        updateCurrentDisplay(null);
        updateQueueDisplay([]);
        setButtons(false);
        if (!guichetId) {
            document.getElementById('service-badge-display').style.display = 'none';
            return;
        }
        const infoResponse = await fetch('/gfa/api/guichet/' + guichetId + '/info');
        if (!infoResponse.ok) return;
        const info = await infoResponse.json();
        serviceId = info.serviceId;
        const badge = document.getElementById('service-badge-display');
        if (info.serviceNom) {
            badge.textContent = info.serviceNom;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
        const currentResponse = await fetch('/gfa/api/guichet/' + guichetId + '/current');
        if (currentResponse.status === 200) {
            currentTicket = await currentResponse.json();
            updateCurrentDisplay(currentTicket);
        }
        if (serviceId) {
            const waitingResponse = await fetch('/gfa/api/guichet/' + guichetId + '/waiting');
            if (waitingResponse.ok) updateQueueDisplay(await waitingResponse.json());
        }
        setButtons(true);
        subscribeRealtimeChannels();
    }

    function connectRealtime() {
        if (!realtimeConfig.key || !realtimeConfig.cluster || typeof Pusher === 'undefined') {
            document.getElementById('ws-label').textContent = 'Temps reel indisponible';
            return;
        }
        pusher = new Pusher(realtimeConfig.key, { cluster: realtimeConfig.cluster, forceTLS: true });
        pusher.connection.bind('connected', () => {
            document.getElementById('ws-dot').classList.add('connected');
            document.getElementById('ws-label').textContent = 'Connecte';
            subscribeRealtimeChannels();
        });
        pusher.connection.bind('disconnected', () => {
            document.getElementById('ws-dot').classList.remove('connected');
            document.getElementById('ws-label').textContent = 'Deconnecte';
        });
        pusher.connection.bind('error', () => {
            document.getElementById('ws-dot').classList.remove('connected');
            document.getElementById('ws-label').textContent = 'Erreur reseau';
        });
    }

    function unsubscribeRealtimeChannels() {
        if (queueChannel) {
            queueChannel.unbind_all();
            pusher?.unsubscribe(queueChannel.name);
            queueChannel = null;
        }
        if (currentChannel) {
            currentChannel.unbind_all();
            pusher?.unsubscribe(currentChannel.name);
            currentChannel = null;
        }
    }

    function subscribeRealtimeChannels() {
        if (!pusher || !serviceId || !guichetId) return;
        unsubscribeRealtimeChannels();
        queueChannel = pusher.subscribe('gfa-service.' + serviceId);
        queueChannel.bind('queue.updated', (payload) => updateQueueDisplay(payload.queue || []));
        currentChannel = pusher.subscribe('gfa-guichet.' + guichetId);
        currentChannel.bind('ticket.current', (payload) => {
            currentTicket = payload.ticket || null;
            updateCurrentDisplay(currentTicket);
        });
    }

    function updateCurrentDisplay(ticket) {
        const title = document.getElementById('client-en-cours');
        const message = document.getElementById('attente-msg');
        if (ticket) {
            title.textContent = 'Client en cours : ' + ticket.numero;
            title.classList.add('active');
            message.textContent = ticket.nomClient || 'Dossier en cours de traitement';
        } else {
            title.innerHTML = 'Client en cours : &mdash;';
            title.classList.remove('active');
            message.textContent = 'Aucun client en attente';
        }
        const hasTicket = !!ticket;
        document.getElementById('btn-rappel').disabled = !hasTicket || !guichetId;
        document.getElementById('btn-termine').disabled = !hasTicket || !guichetId;
        document.getElementById('btn-incomplet').disabled = !hasTicket || !guichetId;
        document.getElementById('btn-absent').disabled = !hasTicket || !guichetId;
    }

    function updateQueueDisplay(queue) {
        const count = Array.isArray(queue) ? queue.length : 0;
        document.getElementById('queue-title').textContent = 'CLIENT(S) EN ATTENTE : ' + count;
        if (!currentTicket) {
            document.getElementById('attente-msg').textContent = count > 0 ? count + ' client(s) en attente' : 'Aucun client en attente';
        }
        const list = document.getElementById('queue-list');
        if (count === 0) {
            list.innerHTML = '<li><p class="tab-empty-msg">Aucun client en attente</p></li>';
            return;
        }
        list.innerHTML = queue.map((ticket) => `<li class="queue-item"><span class="q-num">${ticket.numero}</span><span>${ticket.nomClient || '-'}</span><span class="q-time">${ticket.createdAt ? new Date(ticket.createdAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : ''}</span></li>`).join('');
    }

    function setButtons(enabled) {
        document.getElementById('btn-suivant').disabled = !enabled;
        const hasTicket = enabled && !!currentTicket;
        document.getElementById('btn-rappel').disabled = !hasTicket;
        document.getElementById('btn-termine').disabled = !hasTicket;
        document.getElementById('btn-incomplet').disabled = !hasTicket;
        document.getElementById('btn-absent').disabled = !hasTicket;
    }

    function showAlert(message) {
        const alert = document.getElementById('guichet-alert');
        document.getElementById('alert-msg').textContent = message;
        alert.classList.add('show');
        clearTimeout(alertTimer);
        alertTimer = setTimeout(() => alert.classList.remove('show'), 3500);
    }

    async function actionSuivant() {
        if (!guichetId) return showAlert('Veuillez selectionner un guichet.');
        const response = await fetch('/gfa/api/guichet/call-next', {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ guichetId }),
        });
        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            return showAlert(payload.error || 'Aucun ticket en attente.');
        }
        currentTicket = await response.json();
        updateCurrentDisplay(currentTicket);
    }

    async function actionRappel() {
        if (!currentTicket) return showAlert('Aucun client en cours.');
        await fetch('/gfa/api/guichet/recall', {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ ticketId: currentTicket.id }),
        });
    }

    async function actionTicketStatus(endpoint) {
        if (!currentTicket) return showAlert('Aucun client en cours.');
        const response = await fetch('/gfa/api/guichet/ticket/' + currentTicket.id + '/' + endpoint, {
            method: 'PATCH',
            headers: csrfHeaders(),
        });
        if (!response.ok) return showAlert('Erreur lors de la mise a jour.');
        currentTicket = null;
        updateCurrentDisplay(null);
    }

    function actionTermine() { actionTicketStatus('termine'); }
    function actionIncomplet() { actionTicketStatus('incomplet'); }
    function actionAbsent() { actionTicketStatus('absent'); }

    async function openTicketModal() {
        const response = await fetch('/gfa/api/scan-token');
        if (!response.ok) return showAlert('Impossible de generer le lien ticket.');
        const data = await response.json();
        window.open('/gfa/ticket?token=' + encodeURIComponent(data.token), '_blank');
    }

    init().catch(() => {});
    connectRealtime();
</script>
</body>
</html>
