<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Demande de remise - Dakar Terminal</title>
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
        .email-banner { background:var(--demat-surface-soft); border:1px solid var(--demat-border); border-radius:10px; padding:18px 16px; display:flex; justify-content:center; gap:18px; margin-bottom:28px; flex-wrap:wrap; }
        .email-banner .mail-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:26px; }
        .mi-orange { background:#FF9800; color:#fff; }
        .mi-blue { background:#1565C0; color:#fff; }
        .mi-red { background:#E53935; color:#fff; }
        .mi-teal { background:#00897B; color:#fff; }
        .mi-purple { background:#8E24AA; color:#fff; }
        .field-group { margin-bottom:16px; }
        .field-group label { font-size:13px; font-weight:700; color:var(--demat-label); display:block; margin-bottom:6px; }
        .field-group input[type=text], .field-group input[type=email] { width:100%; border:1px solid var(--demat-border); background:var(--demat-input-bg); border-radius:8px; padding:10px 14px; font-size:14px; font-family:inherit; color:var(--demat-input-text); outline:none; transition:border-color .2s, box-shadow .2s; }
        .field-group input:focus { border-color:#4B49AC; }
        .field-group input:focus, .file-wrap:focus-within { box-shadow: 0 0 0 4px rgba(75,73,172,.15); }
        .file-wrap { display:flex; align-items:center; gap:10px; border:1px solid var(--demat-border); background:var(--demat-input-bg); border-radius:8px; padding:8px 12px; }
        .file-wrap input[type=file] { font-size:13px; color:var(--demat-label); flex:1; border:none; outline:none; }
        .file-label { font-size:12px; font-weight:700; color:var(--demat-muted); margin-bottom:6px; display:block; }
        .btn-submit { width:100%; background:#1565C0; color:#fff; border:none; border-radius:8px; padding:14px; font-size:14px; font-weight:700; letter-spacing:.5px; cursor:pointer; margin-top:24px; transition:background .2s; }
        .btn-submit:hover { background:#0d47a1; }
        .success-banner, .error-banner { border-radius:8px; padding:14px 18px; font-size:14px; text-align:center; margin-bottom:20px; display:none; }
        .success-banner { background:#d4edda; border:1px solid #28a745; color:#155724; }
        .error-banner { background:#f8d7da; border:1px solid #dc3545; color:#721c24; }
        .success-banner.show, .error-banner.show { display:block; }
        .footer-note { text-align:center; font-size:11px; color:var(--demat-muted); margin-top:28px; }
    </style>
</head>
<body>
<div class="form-card">
    <a href="/demat" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>

    <div id="success-msg" class="success-banner">
        <i class="fas fa-check-circle mr-2"></i> Votre demande de remise a bien ete envoyee !
    </div>

    <div id="error-msg" class="error-banner"></div>

    <div class="form-title">Formulaire de demande de remise</div>

    <div class="email-banner">
        <div class="mail-icon mi-orange"><i class="fas fa-envelope"></i></div>
        <div class="mail-icon mi-blue"><i class="fas fa-envelope-open-text"></i></div>
        <div class="mail-icon mi-red"><i class="fab fa-google"></i></div>
        <div class="mail-icon mi-teal"><i class="fas fa-paper-plane"></i></div>
        <div class="mail-icon mi-purple"><i class="fas fa-at"></i></div>
    </div>

    <form id="remise-form" onsubmit="submitForm(event)">
        <div class="field-group">
            <label>Nom</label>
            <input type="text" name="nom" placeholder="Votre nom" required/>
        </div>
        <div class="field-group">
            <label>Prenom</label>
            <input type="text" name="prenom" placeholder="Votre prenom" required/>
        </div>
        <div class="field-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Votre email" required/>
        </div>
        <div class="field-group">
            <label>Numero de BL</label>
            <input type="text" name="numeroBl" placeholder="Votre numero de BL" required/>
        </div>
        <div class="field-group">
            <label>Maison de transit</label>
            <input type="text" name="maisonTransit" placeholder="Votre maison de transit" required/>
        </div>
        <div class="field-group">
            <span class="file-label">DEMANDE MANUSCRITE</span>
            <div class="file-wrap"><input type="file" name="fileDemandeManuscrite" accept=".pdf,.jpg,.jpeg,.png" required/></div>
        </div>
        <div class="field-group">
            <span class="file-label">BAD SHIPPING</span>
            <div class="file-wrap"><input type="file" name="fileBadShipping" accept=".pdf,.jpg,.jpeg,.png" required/></div>
        </div>
        <div class="field-group">
            <span class="file-label">BL</span>
            <div class="file-wrap"><input type="file" name="fileBl" accept=".pdf,.jpg,.jpeg,.png" required/></div>
        </div>
        <div class="field-group">
            <span class="file-label">FACTURE</span>
            <div class="file-wrap"><input type="file" name="fileFacture" accept=".pdf,.jpg,.jpeg,.png" required/></div>
        </div>
        <div class="field-group">
            <span class="file-label">DECLARATION</span>
            <div class="file-wrap"><input type="file" name="fileDeclaration" accept=".pdf,.jpg,.jpeg,.png" required/></div>
        </div>
        <button type="submit" class="btn-submit">ENVOYER LA DEMANDE DE REMISE</button>
    </form>

    <div class="footer-note">&copy; 2026 DakarTerminal. Tous droits reserves.</div>
</div>
<script>
async function submitForm(e) {
    e.preventDefault();
    const form = document.getElementById('remise-form');
    const btn = form.querySelector('.btn-submit');
    const errorBox = document.getElementById('error-msg');
    errorBox.classList.remove('show');
    errorBox.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Envoi en cours...';

    try {
        const res = await fetch('/demat/remise', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });

        if (res.ok) {
            form.style.display = 'none';
            document.getElementById('success-msg').classList.add('show');
            window.scrollTo(0, 0);
            return;
        }

        const payload = await res.json().catch(() => null);
        errorBox.textContent = payload?.message ?? 'Erreur lors de l envoi. Veuillez reessayer.';
        errorBox.classList.add('show');
    } catch (err) {
        errorBox.textContent = 'Erreur de connexion. Veuillez reessayer.';
        errorBox.classList.add('show');
    }

    btn.disabled = false;
    btn.textContent = 'ENVOYER LA DEMANDE DE REMISE';
}

if (new URLSearchParams(location.search).get('success') === 'true') {
    document.getElementById('success-msg').classList.add('show');
}
</script>
</body>
</html>
