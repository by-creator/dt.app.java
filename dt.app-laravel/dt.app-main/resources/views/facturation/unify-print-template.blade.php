<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $type }}</title>
    @include('partials.app-icons')
    <style>
        :root {
            --dt-ink: #162033;
            --dt-muted: #667085;
            --dt-line: #d9dfec;
            --dt-soft: #f5f7ff;
            --dt-accent: #4b49ac;
            --dt-accent-soft: #eef1ff;
            --dt-paper: #ffffff;
            --dt-page: #eef2ff;
            --dt-shadow: 0 20px 55px rgba(15, 23, 42, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            color: var(--dt-ink);
            background:
                radial-gradient(circle at top left, rgba(75, 73, 172, 0.09), transparent 28%),
                linear-gradient(180deg, var(--dt-page) 0%, #f8fafc 100%);
            padding: 24px 0 40px;
        }

        .page-shell {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: var(--dt-paper);
            box-shadow: var(--dt-shadow);
        }

        .page-inner {
            padding: 16mm 15mm 14mm;
        }

        .header-table,
        .info-table,
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            margin-bottom: 14px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 38%;
        }

        .logo {
            width: 220px;
            max-width: 100%;
            height: auto;
            display: block;
        }

        .title-cell {
            text-align: right;
        }

        .doc-badge {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(75, 73, 172, 0.16);
            background: var(--dt-accent-soft);
            color: var(--dt-accent);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            border-radius: 999px;
        }

        .doc-title {
            margin: 0 0 6px;
            font-size: 25px;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .doc-subtitle {
            margin: 0;
            color: var(--dt-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .meta-table {
            margin: 14px 0 18px;
            border: 1px solid var(--dt-line);
        }

        .meta-table td {
            padding: 10px 12px;
            border: 1px solid var(--dt-line);
            font-size: 12px;
        }

        .meta-label {
            width: 24%;
            background: var(--dt-soft);
            color: var(--dt-muted);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .section-title {
            margin: 0 0 10px;
            padding: 10px 12px;
            background: linear-gradient(135deg, var(--dt-accent-soft), #f8faff);
            border: 1px solid var(--dt-line);
            color: var(--dt-accent);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .info-table {
            table-layout: fixed;
            border: 1px solid var(--dt-line);
        }

        .info-table th,
        .info-table td {
            border: 1px solid var(--dt-line);
            padding: 12px 14px;
            text-align: left;
            vertical-align: top;
        }

        .info-table th {
            width: 34%;
            background: var(--dt-soft);
            color: #344054;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .info-table td {
            font-size: 14px;
            line-height: 1.6;
            font-weight: 600;
            word-break: break-word;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .footer-table td {
            padding-top: 12px;
            border-top: 1px solid var(--dt-line);
            color: var(--dt-muted);
            font-size: 11px;
        }

        .footer-right {
            text-align: right;
        }

        @media screen and (max-width: 900px) {
            body {
                padding: 12px;
                background: #f8fafc;
            }

            .page-shell {
                width: 100%;
                min-height: auto;
            }

            .page-inner {
                padding: 20px 16px;
            }

            .header-table,
            .header-table tbody,
            .header-table tr,
            .header-table td {
                display: block;
                width: 100%;
            }

            .title-cell {
                text-align: left;
                padding-top: 14px;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .page-shell {
                width: 210mm;
                min-height: 297mm;
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="page-shell">
        <div class="page-inner">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ asset('img/image.png') }}" alt="Dakar Terminal" class="logo">
                    </td>
                    <td class="title-cell">
                        <span class="doc-badge">{{ $isAttestation ? 'Attestation' : 'Fiche officielle' }}</span>
                        <h1 class="doc-title">{{ $type }}</h1>
                        <p class="doc-subtitle">
                            {{ $isAttestation ? 'Document synthese genere pour confirmer les informations principales d\'ouverture Unify.' : 'Document de synthese regroupant les informations declarees pour l\'ouverture du compte Unify.' }}
                        </p>
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">Type de document</td>
                    <td>{{ $type }}</td>
                    @if ($dateActiviteFormatted && ! $isAttestation)
                        <td class="meta-label">Date d'activite</td>
                        <td>{{ $dateActiviteFormatted }}</td>
                    @else
                        <td class="meta-label">Emetteur</td>
                        <td>Dakar Terminal</td>
                    @endif
                </tr>
            </table>

            <div class="section-title">{{ $isAttestation ? 'Informations retenues' : 'Informations declarees' }}</div>

            <table class="info-table">
                <tbody>
                    @foreach ($data as $label => $value)
                        <tr>
                            <th>{{ $label }}</th>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="footer-table">
                <tr>
                    <td>Dakar Terminal</td>
                    <td class="footer-right">Generation automatique du document Unify</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
