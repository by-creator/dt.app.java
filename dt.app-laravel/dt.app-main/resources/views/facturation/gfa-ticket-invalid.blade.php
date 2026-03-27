<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR invalide - Dakar Terminal</title>
    @include('partials.app-icons')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4B49AC;
            --secondary: #7978E9;
            --danger: #dc3545;
            --text-primary: #1f2a44;
            --text-secondary: #5b657a;
            --surface: #ffffff;
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
            justify-content: center;
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

        .invalid-card {
            width: 100%;
            max-width: 420px;
            background: var(--surface);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(31, 46, 94, .12);
            border: 1px solid var(--border-soft);
        }

        .invalid-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            padding: 28px 24px 24px;
            text-align: center;
            color: #fff;
        }

        .invalid-header .icon-wrap {
            width: 72px;
            height: 72px;
            margin: 0 auto 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .14);
            font-size: 30px;
        }

        .invalid-header .label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            color: rgba(255,255,255,.75);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .invalid-header h1 {
            font-size: 30px;
            font-weight: 900;
            line-height: 1.1;
            margin: 0;
        }

        .invalid-body {
            padding: 28px 28px 30px;
            text-align: center;
        }

        .invalid-body p {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .invalid-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .invalid-actions {
            display: flex;
            justify-content: center;
        }

        .btn-return {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 22px;
            border: none;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: .03em;
            text-decoration: none;
            box-shadow: 0 14px 30px rgba(75, 73, 172, .22);
            transition: transform .2s ease, opacity .2s ease;
        }

        .btn-return:hover {
            color: #fff;
            opacity: .95;
            transform: translateY(-1px);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="page-logo">
        <img src="{{ asset('img/image.png') }}" alt="Dakar Terminal">
        <p>Gestion de la file d'attente</p>
    </div>

    <div class="invalid-card">
        <div class="invalid-header">
            <div class="icon-wrap">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="label">Acces indisponible</div>
            <h1>QR invalide ou expire</h1>
        </div>

        <div class="invalid-body">
            <p>Le lien de prise de ticket n'est plus valide. Veuillez rescanner le QR code depuis l'ecran d'affichage pour generer un nouvel acces.</p>

            <div class="invalid-note">
                <i class="fas fa-sync-alt"></i>
                <span>Un nouveau scan est necessaire avant de continuer.</span>
            </div>

            
        </div>
    </div>
</body>
</html>
