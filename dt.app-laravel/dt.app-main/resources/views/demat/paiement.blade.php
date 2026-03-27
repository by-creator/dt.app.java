<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Paiement - Dakar Terminal</title>
    @include('partials.app-icons')
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@300;400;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        :root {
            color-scheme: light dark;
            --demat-page-bg: #f0f4f8;
            --demat-surface: rgba(255,255,255,.95);
            --demat-surface-soft: rgba(232,238,248,.92);
            --demat-border: rgba(148,163,184,.22);
            --demat-text: #191c24;
            --demat-label: #475569;
            --demat-muted: #64748b;
            --demat-input-bg: rgba(255,255,255,.96);
            --demat-input-text: #1f2937;
            --demat-shadow: 0 4px 32px rgba(0,0,0,.09);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --demat-page-bg: #020617;
                --demat-surface: rgba(15,23,42,.9);
                --demat-surface-soft: rgba(30,41,59,.82);
                --demat-border: rgba(148,163,184,.18);
                --demat-text: #e5eefb;
                --demat-label: #cbd5e1;
                --demat-muted: #94a3b8;
                --demat-input-bg: rgba(15,23,42,.84);
                --demat-input-text: #e2e8f0;
                --demat-shadow: 0 24px 56px rgba(2,6,23,.42);
            }
        }

        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Overpass',sans-serif; background:var(--demat-page-bg); color:var(--demat-text); min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:40px 16px 60px; }
        .form-card { background:var(--demat-surface); border-radius:16px; box-shadow:var(--demat-shadow); border:1px solid var(--demat-border); padding:36px 40px; width:100%; max-width:620px; }
        @media (max-width:576px) { .form-card { padding:24px 18px; } }
        .btn-back { background:#4B49AC; color:#fff; border:none; border-radius:7px; padding:7px 16px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; margin-bottom:22px; text-decoration:none; }
        .btn-back:hover { background:#3e3d99; color:#fff; }
        .form-title { font-size:18px; font-weight:700; color:var(--demat-text); text-align:center; text-decoration:underline; text-underline-offset:4px; margin-bottom:24px; }
        .subtitle { text-align:center; font-size:14px; color:var(--demat-muted); margin-bottom:28px; }
        .pay-cards { display:flex; justify-content:center; gap:24px; flex-wrap:wrap; }
        .pay-card { flex:1; min-width:200px; max-width:240px; background:var(--demat-surface-soft); border:1px solid var(--demat-border); border-radius:16px; box-shadow:var(--demat-shadow); overflow:hidden; display:flex; align-items:center; justify-content:center; padding:32px 24px; text-decoration:none; transition:transform .25s ease, box-shadow .25s ease; }
        .pay-card:hover { transform:translateY(-5px); box-shadow:0 16px 40px rgba(0,0,0,.14); text-decoration:none; }
        .pay-card img { max-width:100%; max-height:100px; width:auto; height:auto; object-fit:contain; }
        @media (max-width:480px) { .pay-cards { flex-direction:column; align-items:center; } .pay-card { max-width:100%; width:100%; } }
        .footer-note { text-align:center; font-size:11px; color:var(--demat-muted); margin-top:28px; }
    </style>
</head>
<body>
<div class="form-card">
    <a href="{{ route('demat') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>

    <div class="form-title">Paiement</div>
    <div class="subtitle">Choisissez votre operateur de paiement</div>

    <div class="pay-cards">
        <a href="https://www.google.com/" class="pay-card">
            <img src="{{ asset('img/sycapay.png') }}" alt="Sycapay">
        </a>
        <a href="https://mytouchpoint.net/dakar_terminal" class="pay-card">
            <img src="{{ asset('img/intouch.png') }}" alt="Intouch">
        </a>
    </div>

    <div class="footer-note">&copy; 2026 DakarTerminal. Tous droits reserves.</div>
</div>
</body>
</html>
