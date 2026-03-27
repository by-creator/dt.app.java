<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Prendre un ticket - Dakar Terminal</title>
    @include('partials.app-icons')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4B49AC;
            --secondary: #7978E9;
            --info: #248AFD;
            --text-primary: #1f2a44;
            --text-secondary: #5b657a;
            --surface: #ffffff;
            --surface-alt: #f7f9ff;
            --border-soft: rgba(31, 42, 68, .12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Overpass', sans-serif;
            background: linear-gradient(180deg, #ffffff 0%, #f4f7ff 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 16px 60px;
        }

        .page-logo {
            margin-bottom: 32px;
            text-align: center;
        }

        .page-logo img {
            max-width: 300px;
            max-height: 52px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .page-logo p {
            font-size: 14px;
            color: #888;
            margin-top: 10px;
            letter-spacing: 1px;
        }

        #view-select {
            width: 100%;
            max-width: 520px;
        }

        .select-title {
            font-size: 18px;
            font-weight: 700;
            color: #191C24;
            text-align: center;
            margin-bottom: 24px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .service-btn {
            background: linear-gradient(160deg, #ffffff 0%, #f8f9ff 100%);
            border: 1px solid #e1e6f5;
            border-radius: 18px;
            padding: 28px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all .25s ease;
            color: var(--text-primary);
            box-shadow: 0 8px 22px rgba(31, 46, 94, .08);
            position: relative;
            overflow: hidden;
        }

        .service-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(121,120,233,.18), transparent 45%);
            pointer-events: none;
        }

        .service-btn:hover:not(:disabled) {
            border-color: rgba(75,73,172,.35);
            box-shadow: 0 14px 30px rgba(42, 56, 110, .16);
            transform: translateY(-5px);
        }

        .service-btn:disabled {
            opacity: .65;
            cursor: wait;
            transform: none;
        }

        .svc-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            box-shadow: 0 10px 18px rgba(36,138,253,.25);
            position: relative;
            z-index: 1;
        }

        .svc-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: .4px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .svc-prefix {
            font-size: 11px;
            color: var(--text-secondary);
            font-weight: 700;
            letter-spacing: 2px;
            position: relative;
            z-index: 1;
        }

        .service-validation .svc-icon { background: linear-gradient(135deg, #0ea5e9, #2563eb); }
        .service-facturation .svc-icon { background: linear-gradient(135deg, #7c3aed, #4f46e5); }
        .service-caisse .svc-icon { background: linear-gradient(135deg, #0f766e, #14b8a6); }
        .service-bad .svc-icon { background: linear-gradient(135deg, #f59e0b, #ea580c); }
        .service-default .svc-icon { background: linear-gradient(135deg, #4B49AC, #248AFD); }

        .no-services {
            grid-column: 1 / -1;
            text-align: center;
            color: #aaa;
            font-size: 14px;
            padding: 40px 0;
        }

        #view-ticket {
            display: none;
            width: 100%;
            max-width: 400px;
        }

        .ticket-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
        }

        .ticket-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 28px 24px 24px;
            text-align: center;
        }

        .th-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            color: rgba(255,255,255,.7);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .ticket-number-display {
            font-size: 88px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -3px;
            line-height: 1;
        }

        .ticket-body {
            padding: 28px 28px 20px;
        }

        .ticket-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f7;
        }

        .ticket-row:last-child { border-bottom: none; }

        .tr-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #aaa;
            text-transform: uppercase;
        }

        .tr-value {
            font-size: 14px;
            font-weight: 700;
            color: #191C24;
            text-align: right;
        }

        .ticket-footer {
            background: #f8f9ff;
            padding: 16px 28px;
            text-align: center;
            font-size: 11px;
            color: #aaa;
            border-top: 1px solid #f0f0f7;
        }

        .ticket-footer strong { color: var(--primary); }

        .error-box {
            display: none;
            width: 100%;
            max-width: 520px;
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-size: 14px;
            text-align: center;
        }

        @media (max-width: 480px) {
            .ticket-number-display { font-size: 72px; }
            .service-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <div class="page-logo">
        <img src="{{ asset('img/image.png') }}" alt="Dakar Terminal">
        <p>Gestion de la file d'attente</p>
    </div>

    <div id="error-box" class="error-box"></div>
    <div id="scan-token" data-token="{{ $token }}" style="display:none"></div>

    <div id="view-select">
        <div class="select-title">Choisissez votre service</div>
        <div class="service-grid">
            @php
                $serviceOrder = [
                    'VALIDATION' => 1,
                    'FACTURATION' => 2,
                    'CAISSE' => 3,
                    'BAD' => 4,
                ];

                $orderedServices = $services->sortBy(function ($service) use ($serviceOrder) {
                    $name = strtoupper($service->nom ?? '');

                    return $serviceOrder[$name] ?? 999;
                })->values();
            @endphp

            @forelse ($orderedServices as $service)
                @php
                    $name = strtoupper($service->nom ?? '');
                    $serviceClass = match ($name) {
                        'VALIDATION' => 'service-validation',
                        'FACTURATION' => 'service-facturation',
                        'CAISSE' => 'service-caisse',
                        'BAD' => 'service-bad',
                        default => 'service-default',
                    };

                    $iconClass = match ($name) {
                        'VALIDATION' => 'fas fa-clipboard-check',
                        'FACTURATION' => 'fas fa-file-invoice-dollar',
                        'CAISSE' => 'fas fa-wallet',
                        'BAD' => 'fas fa-id-card-alt',
                        default => 'fas fa-concierge-bell',
                    };
                @endphp

                <button
                    type="button"
                    class="service-btn {{ $serviceClass }}"
                    data-id="{{ $service->id }}"
                    data-nom="{{ $service->nom }}"
                    onclick="takeTicket(this)"
                >
                    <div class="svc-icon">
                        <i class="{{ $iconClass }}"></i>
                    </div>
                    <div class="svc-name">{{ $service->nom }}</div>
                    <div class="svc-prefix">{{ $service->code ?: '-' }}</div>
                </button>
            @empty
                <div class="no-services">
                    <i class="fas fa-exclamation-circle fa-2x mb-3 d-block"></i>
                    Aucun service disponible pour le moment.
                </div>
            @endforelse
        </div>
    </div>

    <div id="view-ticket">
        <div class="ticket-card">
            <div class="ticket-header">
                <div class="th-label">Votre numéro de ticket</div>
                <div class="ticket-number-display" id="receipt-number">---</div>
            </div>
            <div class="ticket-body">
                <div class="ticket-row">
                    <span class="tr-label">Service</span>
                    <span class="tr-value" id="receipt-service">-</span>
                </div>
                <div class="ticket-row">
                    <span class="tr-label">Date</span>
                    <span class="tr-value" id="receipt-date">-</span>
                </div>
                <div class="ticket-row">
                    <span class="tr-label">Heure</span>
                    <span class="tr-value" id="receipt-time">-</span>
                </div>
                <div class="ticket-row">
                    <span class="tr-label">Rang dans la file</span>
                    <span class="tr-value" id="receipt-rank">-</span>
                </div>
            </div>
            <div class="ticket-footer">
                Merci de patienter - <strong>Dakar Terminal</strong>
            </div>
        </div>
    </div>

    <script>
        const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        const months = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
        const scanTokenElement = document.getElementById('scan-token');
        const currentToken = scanTokenElement?.dataset.token || '';

        history.replaceState(null, '', location.href);
        window.addEventListener('popstate', () => history.go(1));

        function showError(message) {
            const box = document.getElementById('error-box');
            box.textContent = message;
            box.style.display = 'block';
        }

        function hideError() {
            document.getElementById('error-box').style.display = 'none';
        }

        function tokenStorageKey(token) {
            return 'gfa-ticket-used:' + token;
        }

        function markTokenAsUsed(token, ticket) {
            if (!token) return;
            sessionStorage.setItem(tokenStorageKey(token), JSON.stringify(ticket));
        }

        function getUsedTokenTicket(token) {
            if (!token) return null;
            const raw = sessionStorage.getItem(tokenStorageKey(token));
            if (!raw) return null;

            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        function renderTicketReceipt(ticket, serviceName = null) {
            const now = new Date();

            document.getElementById('receipt-number').textContent = ticket.numero || '???';
            document.getElementById('receipt-service').textContent = ticket.serviceNom || serviceName || '-';
            document.getElementById('receipt-date').textContent =
                days[now.getDay()] + ' ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
            document.getElementById('receipt-time').textContent =
                String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
            document.getElementById('receipt-rank').textContent = '#' + (ticket.id ?? '-');

            document.getElementById('view-select').style.display = 'none';
            document.getElementById('view-ticket').style.display = 'block';
            window.scrollTo(0, 0);
        }

        function lockTicketSelection() {
            document.querySelectorAll('.service-btn').forEach((item) => { item.disabled = true; });
        }

        function initializeUsedTokenState() {
            const usedTicket = getUsedTokenTicket(currentToken);
            if (!usedTicket) return;

            lockTicketSelection();
            renderTicketReceipt(usedTicket);
        }

        async function takeTicket(button) {
            const serviceId = Number(button.dataset.id);
            const serviceName = button.dataset.nom;
            const scanToken = currentToken;
            const buttons = document.querySelectorAll('.service-btn');

            hideError();
            buttons.forEach((item) => { item.disabled = true; });

            try {
                const response = await fetch('/gfa/api/tickets', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        serviceId,
                        scanToken,
                    }),
                });

                if (response.status === 403) {
                    window.location.replace('/gfa/ticket');
                    return;
                }

                if (!response.ok) {
                    throw new Error('Erreur serveur (' + response.status + ')');
                }

                const ticket = await response.json();
                markTokenAsUsed(scanToken, ticket);
                renderTicketReceipt(ticket, serviceName);
            } catch (error) {
                buttons.forEach((item) => { item.disabled = false; });
                showError('Impossible de creer le ticket. Veuillez reessayer.');
            }
        }

        initializeUsedTokenState();
    </script>
</body>
</html>
