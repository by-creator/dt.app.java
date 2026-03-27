<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ecran d'affichage - Dakar Terminal</title>
    @include('partials.app-icons')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#4B49AC; --secondary:#7978E9; --info:#248AFD; --text-primary:#1f2a44; --text-secondary:#5b657a; --surface:#ffffff; --border-soft:rgba(31, 42, 68, .12); }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Overpass',sans-serif; background:linear-gradient(180deg,#ffffff 0%,#f4f7ff 100%); color:var(--text-primary); min-height:100vh; display:flex; flex-direction:column; }
        .display-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 32px; background:rgba(255,255,255,.96); border-bottom:1px solid var(--border-soft); box-shadow:0 4px 14px rgba(17,34,68,.06); }
        .display-topbar img { max-width:200px; max-height:36px; width:auto; height:auto; object-fit:contain; }
        .dt-clock { font-size:22px; font-weight:700; letter-spacing:2px; font-variant-numeric:tabular-nums; }
        .dt-date { font-size:13px; color:var(--text-secondary); text-align:right; margin-top:2px; }
        .ws-indicator { display:flex; align-items:center; gap:6px; font-size:11px; color:var(--text-secondary); }
        .ws-dot { width:8px; height:8px; border-radius:50%; background:#dc3545; transition:background .3s; }
        .ws-dot.connected { background:#28a745; }
        .display-area { flex:1; display:flex; align-items:center; justify-content:center; padding:24px 40px 10px; }
        .display-card { background:var(--surface); border:1px solid var(--border-soft); box-shadow:0 16px 42px rgba(17,34,68,.08); border-radius:24px; width:100%; display:flex; overflow:hidden; min-height:400px; transition:box-shadow .4s; }
        .display-card.flash { box-shadow:0 0 0 6px rgba(75,73,172,.25), 0 16px 42px rgba(17,34,68,.08); }
        .dc-left { flex:0 0 45%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:16px; background:linear-gradient(145deg,#f4f7ff 0%,#eef1ff 100%); border-right:2px solid var(--border-soft); padding:40px 36px; }
        .display-now-serving { font-size:12px; font-weight:700; letter-spacing:3px; text-transform:uppercase; color:var(--text-secondary); }
        .display-ticket-number { font-size:160px; font-weight:900; color:var(--primary); letter-spacing:-6px; line-height:1; text-shadow:0 6px 28px rgba(75,73,172,.22); transition:color .3s, transform .3s; }
        .display-ticket-number.highlight { color:var(--info); transform:scale(1.04); }
        .dc-right { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:24px; padding:40px; text-align:center; }
        .display-ticket-badge { background:linear-gradient(135deg,var(--secondary),var(--info)); border-radius:16px; padding:10px 40px; font-size:18px; font-weight:700; color:#fff; letter-spacing:2px; }
        .display-divider { width:60px; height:3px; background:linear-gradient(90deg,var(--secondary),var(--info)); border-radius:2px; }
        .display-guichet-block { background:var(--primary); border-radius:16px; padding:20px 50px; text-align:center; min-width:240px; }
        .gb-label { font-size:12px; font-weight:700; letter-spacing:3px; color:rgba(255,255,255,.75); text-transform:uppercase; margin-bottom:8px; }
        .gb-value { font-size:64px; font-weight:900; color:#fff; line-height:1; letter-spacing:-1px; transition:transform .3s; }
        .gb-value.highlight { transform:scale(1.06); }
        .display-agent-name { font-size:22px; font-weight:700; color:var(--text-secondary); }
        .qr-section { display:flex; gap:30px; padding:20px 40px 30px; }
        .qr-card { background:var(--surface); border:1px solid var(--border-soft); border-radius:20px; box-shadow:0 12px 32px rgba(17,34,68,.06); flex:1; display:flex; overflow:hidden; min-height:180px; }
        .qr-card-left { flex:0 0 35%; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:linear-gradient(145deg,#f4f7ff 0%,#eef1ff 100%); border-right:1px solid var(--border-soft); padding:20px 15px; text-align:center; }
        .qr-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; }
        .qr-icon-wifi { background:linear-gradient(135deg,rgba(36,138,253,.2),rgba(121,120,233,.2)); color:#248AFD; }
        .qr-icon-ticket { background:linear-gradient(135deg,rgba(121,120,233,.2),rgba(75,73,172,.2)); color:#7978E9; }
        .qr-step { font-size:9px; font-weight:700; letter-spacing:2px; color:var(--text-secondary); text-transform:uppercase; }
        .qr-title { font-size:13px; font-weight:700; }
        .qr-card-right { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:15px 20px; }
        .qr-code img { width:180px; height:180px; border-radius:10px; background:#fff; padding:5px; border:1px solid var(--border-soft); box-shadow:0 4px 10px rgba(17,34,68,.05); }
        .qr-label { font-size:11px; font-weight:600; text-align:center; }
        .qr-text { font-size:10px; color:#888; text-align:center; }
        .activate-overlay { position:fixed; inset:0; z-index:9999; background:rgba(75,73,172,.92); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:20px; color:#fff; text-align:center; }
        .activate-overlay h2 { font-size:28px; font-weight:800; }
        .activate-overlay p { font-size:15px; opacity:.8; }
        .btn-activate { background:#fff; color:var(--primary); border:none; border-radius:12px; padding:14px 36px; font-size:16px; font-weight:700; cursor:pointer; }
        @media (max-width:768px) {
            .display-card { flex-direction:column; min-height:unset; }
            .dc-left { border-right:none; border-bottom:2px solid var(--border-soft); padding:28px 20px; }
            .display-ticket-number { font-size:100px; }
            .qr-section { flex-direction:column; padding:20px; }
            .qr-card { flex-direction:column; }
            .qr-card-left { border-right:none; border-bottom:2px solid var(--border-soft); }
        }
    </style>
</head>
<body>
    <div class="activate-overlay" id="activate-overlay">
        <i class="fas fa-volume-up" style="font-size:48px;opacity:.8"></i>
        <h2>Ecran d'affichage GFA</h2>
        <p>Cliquez pour activer le son et les annonces vocales</p>
        <button class="btn-activate" onclick="activateAudio()"><i class="fas fa-play mr-2"></i> Demarrer l'affichage</button>
    </div>
    <div class="display-topbar">
        <img src="{{ asset('img/image.png') }}" alt="Dakar Terminal">
        <div class="ws-indicator"><span class="ws-dot" id="ws-dot"></span><span id="ws-label">Connexion...</span></div>
        <div style="text-align:right">
            <div class="dt-clock" id="clock">00:00:00</div>
            <div class="dt-date" id="date-display"></div>
        </div>
    </div>
    <div class="display-area">
        <div class="display-card" id="display-card">
            <div class="dc-left">
                <div class="display-now-serving">Appel en cours</div>
                <div class="display-ticket-number" id="ticket-number">-</div>
            </div>
            <div class="dc-right">
                <div class="display-ticket-badge" id="service-badge">- SERVICE -</div>
                <div class="display-divider"></div>
                <div class="display-guichet-block">
                    <div class="gb-label">Guichet</div>
                    <div class="gb-value" id="guichet-name">-</div>
                </div>
                <div class="display-agent-name" id="agent-name">En attente</div>
            </div>
        </div>
    </div>
    <div class="qr-section">
        <div class="qr-card">
            <div class="qr-card-left">
                <div class="qr-icon qr-icon-wifi"><i class="fas fa-wifi"></i></div>
                <div class="qr-step">Etape 1</div>
                <div class="qr-title">Connexion Wi-Fi</div>
            </div>
            <div class="qr-card-right">
                <div class="qr-code"><img src="https://api.qrserver.com/v1/create-qr-code/?size=320x320&data={{ urlencode($wifiQrData) }}" alt="QR Code WiFi"></div>
                <div class="qr-label"><strong>SSID :</strong> <span class="qr-text">{{ $wifiSsid }}</span></div>
            </div>
        </div>
        <a id="ticket-qr-card" class="qr-card" href="#" target="_blank" style="text-decoration:none;color:inherit;cursor:pointer">
            <div class="qr-card-left">
                <div class="qr-icon qr-icon-ticket"><i class="fas fa-ticket-alt"></i></div>
                <div class="qr-step">Etape 2</div>
                <div class="qr-title">Prendre un ticket</div>
            </div>
            <div class="qr-card-right">
                <div class="qr-code"><img id="ticket-qr-img" src="" alt="QR Ticket" style="opacity:.4"></div>
                <div class="qr-label">Scanner pour un ticket</div>
            </div>
        </a>
    </div>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        const realtimeConfig = { key: @js(config('services.pusher.key')), cluster: @js(config('services.pusher.cluster')) };
        const DAYS = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        const MONTHS = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        let audioContext = null;
        let qrRefreshTimer = null; // kept for compatibility, no longer used

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
            document.getElementById('date-display').textContent = DAYS[now.getDay()] + ' ' + now.getDate() + ' ' + MONTHS[now.getMonth()] + ' ' + now.getFullYear();
        }

        function activateAudio() {
            document.getElementById('activate-overlay').style.display = 'none';
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            window.speechSynthesis?.getVoices();
            connectRealtime();
        }

        function playChime() {
            if (!audioContext) return;
            [523.25, 659.25, 783.99].forEach((frequency, index) => {
                const osc = audioContext.createOscillator();
                const gain = audioContext.createGain();
                osc.connect(gain);
                gain.connect(audioContext.destination);
                osc.type = 'sine';
                const start = audioContext.currentTime + index * 0.25;
                osc.frequency.setValueAtTime(frequency, start);
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.35, start + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.6);
                osc.start(start);
                osc.stop(start + 0.65);
            });
        }

        function speak(text) {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'fr-FR';
            utterance.rate = 0.9;
            const voices = window.speechSynthesis.getVoices();
            const frenchVoice = voices.find((voice) => voice.lang === 'fr-FR') || voices.find((voice) => voice.lang.toLowerCase().startsWith('fr'));
            if (frenchVoice) utterance.voice = frenchVoice;
            window.speechSynthesis.speak(utterance);
        }

        function announce(numero, guichet, recalled = false) {
            const prefix = recalled ? 'Rappel du ticket' : 'Ticket';
            speak(`${prefix} ${numero}, vous êtes attendu au guichet ${guichet}.`);
        }

        function updateDisplay(ticket) {
            document.getElementById('service-badge').textContent = ticket.serviceNom || '- SERVICE -';
            document.getElementById('ticket-number').textContent = ticket.numero || '-';
            document.getElementById('guichet-name').textContent = ticket.guichetNumero || '-';
            document.getElementById('agent-name').textContent = ticket.agentNom || 'En cours de traitement';
            const card = document.getElementById('display-card');
            const number = document.getElementById('ticket-number');
            const guichet = document.getElementById('guichet-name');
            card.classList.add('flash');
            number.classList.add('highlight');
            guichet.classList.add('highlight');
            setTimeout(() => {
                card.classList.remove('flash');
                number.classList.remove('highlight');
                guichet.classList.remove('highlight');
            }, 2000);
        }

        function resetDisplay() {
            document.getElementById('service-badge').textContent = '- SERVICE -';
            document.getElementById('ticket-number').textContent = '-';
            document.getElementById('guichet-name').textContent = '-';
            document.getElementById('agent-name').textContent = 'En attente';
        }

        function refreshTicketQr() {
            const ticketUrl = window.location.origin + '/gfa/ticket/go';
            document.getElementById('ticket-qr-img').src = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' + encodeURIComponent(ticketUrl);
            document.getElementById('ticket-qr-img').style.opacity = '1';
            document.getElementById('ticket-qr-card').href = ticketUrl;
        }

        async function loadCurrentTicket() {
            try {
                const response = await fetch('/gfa/api/tickets?statut=EN_COURS');
                if (!response.ok) return;
                const tickets = await response.json();
                if (!Array.isArray(tickets) || tickets.length === 0) return;
                const current = tickets.slice().sort((a, b) => new Date(b.calledAt || 0) - new Date(a.calledAt || 0))[0];
                if (current) updateDisplay(current);
            } catch (e) {
            }
        }

        function connectRealtime() {
            if (!realtimeConfig.key || !realtimeConfig.cluster || typeof Pusher === 'undefined') {
                document.getElementById('ws-label').textContent = 'Temps reel indisponible';
                return;
            }
            const pusher = new Pusher(realtimeConfig.key, { cluster: realtimeConfig.cluster, forceTLS: true });
            const channel = pusher.subscribe('gfa-display');
            pusher.connection.bind('connected', () => {
                document.getElementById('ws-dot').classList.add('connected');
                document.getElementById('ws-label').textContent = 'En ligne';
            });
            pusher.connection.bind('disconnected', () => {
                document.getElementById('ws-dot').classList.remove('connected');
                document.getElementById('ws-label').textContent = 'Deconnecte';
            });
            pusher.connection.bind('error', () => {
                document.getElementById('ws-dot').classList.remove('connected');
                document.getElementById('ws-label').textContent = 'Erreur reseau';
            });
            channel.bind('ticket.called', (ticket) => {
                updateDisplay(ticket);
                playChime();
                setTimeout(() => announce(ticket.numero, ticket.guichetNumero || 'inconnu', !!ticket.recalled), 950);
            });
            channel.bind('ticket.closed', () => setTimeout(resetDisplay, 3000));
        }

        updateClock();
        setInterval(updateClock, 1000);
        document.addEventListener('DOMContentLoaded', () => {
            refreshTicketQr();
            loadCurrentTicket();
        });
    </script>
</body>
</html>
