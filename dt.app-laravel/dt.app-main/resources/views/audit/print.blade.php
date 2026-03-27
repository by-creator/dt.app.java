<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal d'audit — {{ now()->format('d/m/Y') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            padding: 20px;
        }

        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #4b49ac;
        }

        .print-header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #4b49ac;
        }

        .print-header .meta {
            font-size: 11px;
            color: #64748b;
            text-align: right;
            line-height: 1.6;
        }

        .filters-summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 11px;
            color: #475569;
        }

        .filters-summary strong { color: #1e293b; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead th {
            background: #4b49ac;
            color: #fff;
            padding: 7px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eef2ff; }

        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-get    { background: #d1fae5; color: #065f46; }
        .badge-post   { background: #dbeafe; color: #1e40af; }
        .badge-put,
        .badge-patch  { background: #fef3c7; color: #92400e; }
        .badge-delete { background: #fee2e2; color: #991b1b; }

        .badge-2xx { background: #d1fae5; color: #065f46; }
        .badge-3xx { background: #dbeafe; color: #1e40af; }
        .badge-4xx { background: #fef3c7; color: #92400e; }
        .badge-5xx { background: #fee2e2; color: #991b1b; }

        .url-cell { max-width: 200px; word-break: break-all; font-size: 10px; color: #475569; }
        .mono { font-family: 'Courier New', monospace; font-size: 10px; }

        .print-footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
        }

        .total-badge {
            display: inline-block;
            background: #eef2ff;
            color: #4b49ac;
            border-radius: 20px;
            padding: 2px 10px;
            font-weight: 700;
            font-size: 11px;
        }

        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div>
            <p style="font-size:11px;color:#64748b;font-weight:600;letter-spacing:.1em;text-transform:uppercase;margin-bottom:4px;">Dakar Terminal</p>
            <h1>Journal d'audit</h1>
            <p style="margin-top:6px;color:#64748b;font-size:11px;">
                <span class="total-badge">{{ $logs->count() }} entrée{{ $logs->count() > 1 ? 's' : '' }}</span>
            </p>
        </div>
        <div class="meta">
            <strong>Généré le</strong><br>
            {{ now()->locale('fr')->translatedFormat('l d F Y à H:i') }}<br><br>
            <strong>Par</strong><br>
            {{ auth()->user()->name }} ({{ auth()->user()->email }})
        </div>
    </div>

    @php
        $filters = array_filter([
            'Utilisateur' => request('user'),
            'Méthode' => request('method'),
            'Du' => request('date_from'),
            'Au' => request('date_to'),
            'Recherche' => request('search'),
        ]);
    @endphp

    @if (count($filters))
        <div class="filters-summary">
            <strong>Filtres appliqués :</strong>
            @foreach ($filters as $label => $value)
                <span style="margin-left:12px">{{ $label }} : <strong>{{ $value }}</strong></span>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:60px">#</th>
                <th style="width:110px">Date / Heure</th>
                <th style="width:130px">Utilisateur</th>
                <th style="width:60px">Rôle</th>
                <th style="width:100px">IP</th>
                <th style="width:55px">Méth.</th>
                <th>Route / URL</th>
                <th style="width:55px">Statut</th>
                <th style="width:50px">ms</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                @php
                    $statusClass = match(true) {
                        ($log->response_status ?? 0) >= 500 => 'badge-5xx',
                        ($log->response_status ?? 0) >= 400 => 'badge-4xx',
                        ($log->response_status ?? 0) >= 300 => 'badge-3xx',
                        default => 'badge-2xx',
                    };
                    $methodClass = 'badge-' . strtolower($log->method ?? '');
                @endphp
                <tr>
                    <td class="mono">{{ $log->id }}</td>
                    <td class="mono">
                        {{ $log->created_at?->format('d/m/Y') }}<br>
                        {{ $log->created_at?->format('H:i:s') }}
                    </td>
                    <td>
                        <strong>{{ $log->user_name ?? '—' }}</strong><br>
                        <span style="color:#64748b">{{ $log->user_email }}</span>
                    </td>
                    <td>{{ $log->user_role ?? '—' }}</td>
                    <td class="mono">{{ $log->ip_address ?? '—' }}</td>
                    <td><span class="badge {{ $methodClass }}">{{ $log->method }}</span></td>
                    <td class="url-cell">
                        @if ($log->route_name)
                            <strong>{{ $log->route_name }}</strong><br>
                        @endif
                        {{ $log->url }}
                    </td>
                    <td><span class="badge {{ $statusClass }}">{{ $log->response_status ?? '—' }}</span></td>
                    <td class="mono">{{ $log->duration_ms ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:20px;color:#94a3b8;">Aucune entrée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="print-footer">
        <span>Journal d'audit — Dakar Terminal</span>
        <span>{{ now()->format('d/m/Y H:i') }}</span>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
