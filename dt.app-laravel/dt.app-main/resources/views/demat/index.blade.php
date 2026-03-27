<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Dakar Terminal - Tout faire a distance</title>

    @include('partials.app-icons')


    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>


    <style>
        :root {
            color-scheme: light dark;
            --demat-page-bg: #f0f4f8;
            --demat-surface: rgba(255,255,255,.94);
            --demat-surface-soft: rgba(232,238,248,.9);
            --demat-border: rgba(148,163,184,.22);
            --demat-text: #191c24;
            --demat-muted: #64748b;
            --demat-card-text: #475569;
            --demat-shadow: 0 4px 18px rgba(0,0,0,.08);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --demat-page-bg: #020617;
                --demat-surface: rgba(15,23,42,.88);
                --demat-surface-soft: rgba(30,41,59,.84);
                --demat-border: rgba(148,163,184,.18);
                --demat-text: #e5eefb;
                --demat-muted: #94a3b8;
                --demat-card-text: #cbd5e1;
                --demat-shadow: 0 24px 56px rgba(2,6,23,.38);
            }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Overpass', sans-serif;
            background: var(--demat-page-bg);
            color: var(--demat-text);
            min-height: 100vh;
        }

        .demat-hero {
            background: var(--demat-surface);
            border-bottom: 1px solid var(--demat-border);
            padding: 32px 24px 36px;
            text-align: center;
            margin-bottom: 36px;
            box-shadow: var(--demat-shadow);
        }

        .demat-hero .brand-logo {
            max-width: 300px;
            max-height: 52px;
            width: auto;
            height: auto;
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .demat-hero h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--demat-text);
            margin-bottom: 6px;
        }

        .demat-hero p {
            font-size: 15px;
            color: var(--demat-muted);
        }

        .demat-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px 60px;
        }

        .demat-card {
            background: var(--demat-surface);
            border-radius: 14px;
            box-shadow: var(--demat-shadow);
            padding: 28px 24px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            text-decoration: none !important;
            color: inherit !important;
            transition: transform .18s, box-shadow .18s;
            border: 1px solid var(--demat-border);
        }

        .demat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 32px rgba(21,101,192,.15);
            border-color: #1976d2;
            color: inherit !important;
            text-decoration: none !important;
        }

        .demat-card:active { transform: translateY(-1px); }

        .demat-card .card-icon-wrap {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 40px;
        }

        .demat-card .card-title {
            font-size: 15px;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 10px;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .demat-card .card-desc {
            font-size: 12px;
            font-weight: 600;
            color: var(--demat-card-text);
            line-height: 1.5;
            text-transform: uppercase;
        }

        .demat-card .card-desc strong {
            color: var(--demat-text);
        }

        .icon-validation { background: color-mix(in srgb, #2e7d32 16%, transparent); }
        .icon-validation i { color: #2e7d32; }

        .icon-facturation { background: color-mix(in srgb, #e65100 16%, transparent); }
        .icon-facturation i { color: #e65100; }

        .icon-paiement { background: color-mix(in srgb, #1565c0 16%, transparent); }
        .icon-paiement i { color: #1565c0; }

        .icon-remise { background: color-mix(in srgb, #c62828 16%, transparent); }
        .icon-remise i { color: #c62828; }

        @media (max-width: 576px) {
            .demat-hero h1 { font-size: 24px; }
            .demat-grid { gap: 14px; padding: 0 12px 40px; }
            .demat-card { padding: 20px 14px 18px; }
            .demat-card .card-icon-wrap { width: 64px; height: 64px; font-size: 28px; }
            .demat-card .card-title { font-size: 13px; }
            .demat-card .card-desc { font-size: 11px; }
        }
    </style>
</head>
<body>

<div class="demat-hero">
    <img src="/img/image.png" alt="Dakar Terminal" class="brand-logo"/>
    <h1>Tout faire a distance</h1>
    <p>Bienvenu(e) sur notre plateforme</p>
</div>

<div class="demat-grid">
    <a href="/demat/validation" class="demat-card">
        <div class="card-icon-wrap icon-validation">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="card-title">Demande de validation</div>
        <div class="card-desc">
            <strong>VALIDATION</strong> : Effectuez Une Demande De Rattachement A Votre Maison De Transit
        </div>
    </a>

    <a href="https://ies.aglgroup.com/dkrp/Login" class="demat-card">
        <div class="card-icon-wrap icon-facturation">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="card-title">Facturation</div>
        <div class="card-desc">
            <strong>FACTURATION</strong> : Génerez Vos Factures Proforma / Factures Definitives / BAD
        </div>
    </a>

    <a href="{{ route('demat.paiement') }}" class="demat-card">
        <div class="card-icon-wrap icon-paiement">
            <i class="fas fa-credit-card"></i>
        </div>
        <div class="card-title">Paiement</div>
        <div class="card-desc">
            <strong>CAISSE</strong> : Payez Vos Factures Via Les Operateurs Wave / Yass / Orange Money
        </div>
    </a>

    <a href="{{ route('demat.remise') }}" class="demat-card">
        <div class="card-icon-wrap icon-remise">
            <i class="fas fa-tags"></i>
        </div>
        <div class="card-title">Demande de remise</div>
        <div class="card-desc">
            <strong>REMISE</strong> : Effectuez Une Demande De Remise
        </div>
    </a>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
