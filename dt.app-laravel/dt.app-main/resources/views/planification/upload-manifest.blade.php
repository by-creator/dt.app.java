@auth
{{-- ════════════════════════════════════════════════════════════
     VUE AUTHENTIFIÉE : Formulaire (gauche) + Liste (droite)
═════════════════════════════════════════════════════════════ --}}
<x-layouts::app :title="__('Upload Manifest')">

<style>
    /* ── Variables ── */
    :root {
        --um-border: #e2e8f0; --um-surface: #ffffff; --um-bg: #f8fafc;
        --um-text: #0f172a; --um-muted: #64748b;
        --um-indigo: #4B49AC; --um-indigo-h: #3b3a8e;
        --um-green: #1D6F42; --um-blue: #0F4C81;
        --um-success-bg:#f0fdf4; --um-success-text:#166534; --um-success-border:#bbf7d0;
        --um-error-bg:#fef2f2; --um-error-text:#991b1b; --um-error-border:#fecaca;
    }

    /* ── Layout 2 colonnes ── */
    .um-grid { display:grid; grid-template-columns:400px 1fr; gap:1.5rem; padding:1.5rem; align-items:start; min-height:100%; }
    @media(max-width:900px){ .um-grid { grid-template-columns:1fr; } }

    /* ── Carte commune ── */
    .um-card { background:var(--um-surface); border:1px solid var(--um-border); border-radius:16px; overflow:hidden; }
    .um-card-head { padding:1rem 1.25rem; border-bottom:1px solid var(--um-border); background:var(--um-bg); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .um-card-head h2 { margin:0; font-size:.95rem; font-weight:700; color:var(--um-text); display:flex; align-items:center; gap:.5rem; }
    .um-card-body { padding:1.25rem; }

    /* ── Formulaire ── */
    .um-muted { color:var(--um-muted); font-size:.8rem; margin-bottom:1rem; }
    .um-label { font-size:.8rem; font-weight:600; color:var(--um-text); display:block; margin-bottom:.4rem; }
    .um-file { width:100%; border:1px solid #cbd5e1; background:#f8fafc; color:var(--um-text); border-radius:8px; padding:10px 12px; font-size:.8rem; box-sizing:border-box; }
    .um-file:focus { border-color:var(--um-indigo); outline:none; box-shadow:0 0 0 3px rgba(75,73,172,.15); }
    .um-btn-submit { width:100%; margin-top:1rem; border:none; border-radius:8px; padding:10px; font-size:.85rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem; background:var(--um-indigo); color:#fff; transition:background .2s; }
    .um-btn-submit:hover { background:var(--um-indigo-h); }
    .um-status-ok { background:var(--um-success-bg); color:var(--um-success-text); border:1px solid var(--um-success-border); border-radius:8px; padding:.75rem 1rem; font-size:.8rem; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }
    .um-status-err { background:var(--um-error-bg); color:var(--um-error-text); border:1px solid var(--um-error-border); border-radius:8px; padding:.75rem 1rem; font-size:.8rem; margin-bottom:1rem; }

    /* ── Barre de recherche ── */
    .um-search-row { display:flex; gap:.5rem; }
    .um-search-input { flex:1; border:1px solid var(--um-border); background:var(--um-bg); border-radius:8px; padding:.5rem .85rem; font-size:.82rem; color:var(--um-text); }
    .um-search-input:focus { border-color:var(--um-indigo); outline:none; box-shadow:0 0 0 3px rgba(75,73,172,.12); }
    .um-search-btn { border:none; background:var(--um-indigo); color:#fff; border-radius:8px; padding:.5rem 1rem; font-size:.8rem; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:.4rem; }
    .um-search-btn:hover { background:var(--um-indigo-h); }
    .um-clear-btn { border:1px solid var(--um-border); background:var(--um-surface); color:var(--um-muted); border-radius:8px; padding:.5rem .75rem; font-size:.8rem; cursor:pointer; text-decoration:none; display:flex; align-items:center; }
    .um-clear-btn:hover { border-color:var(--um-indigo); color:var(--um-indigo); }

    /* ── Tableau ── */
    .um-table-wrap { overflow-x:auto; margin-top:1rem; }
    .um-table { width:100%; border-collapse:collapse; font-size:.8rem; }
    .um-table thead th { background:#1F3864; color:#fff; font-weight:700; padding:.6rem .75rem; text-align:left; white-space:nowrap; }
    .um-table thead th:first-child { border-radius:8px 0 0 0; }
    .um-table thead th:last-child { border-radius:0 8px 0 0; text-align:center; }
    .um-table tbody tr:nth-child(even) { background:#EEF2F7; }
    .um-table tbody tr:hover { background:rgba(75,73,172,.06); }
    .um-table tbody td { padding:.55rem .75rem; border-bottom:1px solid var(--um-border); color:var(--um-text); white-space:nowrap; }
    .um-table tbody td:last-child { text-align:center; }

    /* ── Badges & Boutons action ── */
    .um-call-badge { display:inline-flex; align-items:center; gap:.35rem; background:#eef2ff; color:var(--um-indigo); border:1px solid #c7d2fe; border-radius:20px; padding:3px 10px; font-size:.75rem; font-weight:700; }
    .um-btn-sm { display:inline-flex; align-items:center; gap:.3rem; padding:.35rem .75rem; border-radius:6px; border:none; font-size:.75rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .15s; }
    .um-btn-preview { background:#6366f1; color:#fff; }
    .um-btn-preview:hover { background:#4f46e5; color:#fff; }
    .um-btn-xlsx { background:var(--um-green); color:#fff; }
    .um-btn-xlsx:hover { background:#155533; color:#fff; }
    .um-btn-iftmin { background:var(--um-blue); color:#fff; }
    .um-btn-iftmin:hover { background:#0a3560; color:#fff; }
    .um-btn-txt { background:#64748b; color:#fff; }
    .um-btn-txt:hover { background:#475569; color:#fff; }

    /* ── Pagination ── */
    .um-pagination { margin-top:1rem; display:flex; justify-content:center; }
    .um-pagination .pagination { display:flex; gap:.25rem; list-style:none; margin:0; padding:0; flex-wrap:wrap; }
    .um-pagination .page-item .page-link { padding:.4rem .75rem; border:1px solid var(--um-border); border-radius:6px; font-size:.78rem; color:var(--um-text); text-decoration:none; display:block; background:var(--um-surface); }
    .um-pagination .page-item.active .page-link { background:var(--um-indigo); color:#fff; border-color:var(--um-indigo); }
    .um-pagination .page-item.disabled .page-link { opacity:.45; pointer-events:none; }
    .um-pagination .page-item .page-link:hover:not(.active):not(.disabled) { background:var(--um-bg); border-color:var(--um-indigo); color:var(--um-indigo); }

    /* ── Empty state ── */
    .um-empty { text-align:center; padding:2.5rem 1rem; color:var(--um-muted); font-size:.85rem; }
    .um-empty svg { opacity:.3; margin-bottom:.75rem; }
</style>

<div class="um-grid">

    {{-- ── COLONNE GAUCHE : Formulaire ── --}}
    <div class="um-card">
        <div class="um-card-head">
            <h2>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import du manifeste
            </h2>
        </div>
        <div class="um-card-body">
            <p class="um-muted">Chargez un fichier <strong>.txt</strong>. Le système génèrera automatiquement les fichiers <strong>XLSX</strong> et <strong>IFTMIN</strong>.</p>

            @if(session('success'))
                <div class="um-status-ok">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="um-status-err">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('planification.upload-manifest.store') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="um-label" for="manifest-file">Fichier manifeste (.txt)</label>
                    <input id="manifest-file" type="file" name="manifest" class="um-file" accept=".txt,text/plain" required>
                </div>
                <button type="submit" class="um-btn-submit">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Importer et générer les fichiers
                </button>
            </form>
        </div>
    </div>

    {{-- ── COLONNE DROITE : Liste paginée ── --}}
    <div class="um-card">
        <div class="um-card-head">
            <h2>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="3"/></svg>
                Historique des codifications
            </h2>
            <span style="font-size:.75rem;color:var(--um-muted);">{{ $codifications->total() }} enregistrement(s)</span>
        </div>
        <div class="um-card-body">

            {{-- Barre de recherche --}}
            <form method="GET" action="{{ route('planification.upload-manifest') }}" class="um-search-row">
                <input type="text" name="search" class="um-search-input"
                       placeholder="Rechercher par call number…"
                       value="{{ request('search') }}">
                <button type="submit" class="um-search-btn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Rechercher
                </button>
                @if(request('search'))
                    <a href="{{ route('planification.upload-manifest') }}" class="um-clear-btn" title="Effacer">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                @endif
            </form>

            {{-- Tableau --}}
            <div class="um-table-wrap">
                @if($codifications->count() > 0)
                    <table class="um-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Call Number</th>
                                <th>Fichier manifest</th>
                                <th>Importé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($codifications as $cod)
                                <tr>
                                    <td style="color:var(--um-muted);">{{ $cod->id }}</td>
                                    <td><span class="um-call-badge">{{ $cod->call_number ?: '—' }}</span></td>
                                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;" title="{{ basename($cod->manifest) }}">
                                        {{ basename($cod->manifest) }}
                                    </td>
                                    <td style="color:var(--um-muted);">{{ $cod->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div style="display:flex;gap:.35rem;justify-content:center;flex-wrap:wrap;">
                                            <a href="{{ route('planification.codification.preview', $cod) }}" class="um-btn-sm um-btn-preview" title="Prévisualiser">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                Prévisualiser
                                            </a>
                                            <a href="{{ route('planification.codification.download.xls', $cod) }}" class="um-btn-sm um-btn-xlsx" title="Télécharger XLS">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                XLS
                                            </a>
                                            <a href="{{ route('planification.codification.download.iftmin', $cod) }}" class="um-btn-sm um-btn-iftmin" title="Télécharger IFTMIN">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                                IFTMIN
                                            </a>
                                            <a href="{{ route('planification.codification.download.manifest', $cod) }}" class="um-btn-sm um-btn-txt" title="Télécharger TXT">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                TXT
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Pagination --}}
                    @if($codifications->hasPages())
                        <div class="um-pagination">
                            {{ $codifications->links() }}
                        </div>
                    @endif

                @else
                    <div class="um-empty">
                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="display:block;margin:0 auto"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="3"/></svg>
                        @if(request('search'))
                            Aucun résultat pour « {{ request('search') }} »
                        @else
                            Aucune codification enregistrée pour le moment.
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>

</div>

</x-layouts::app>

@else
{{-- ════════════════════════════════════════════════════════════
     VUE PUBLIQUE : Formulaire seul (sans boutons de téléchargement)
═════════════════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Manifest - Dakar Terminal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --dt-border:#e2e8f0; --dt-page-text:#0f172a; --dt-muted:#64748b;
            --dt-input-border:#cbd5e1; --dt-input-bg:#f8fafc;
            --dt-success-bg:#f0fdf4; --dt-success-text:#166534; --dt-success-border:#bbf7d0;
            --dt-danger-bg:#fef2f2; --dt-danger-text:#991b1b; --dt-danger-border:#fecaca;
            --dt-indigo:#4B49AC;
        }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:radial-gradient(circle at top left,rgba(121,120,233,.12),transparent 28%),linear-gradient(180deg,#f7f8ff 0%,#eef2ff 100%); color:var(--dt-page-text); font-family:'Instrument Sans',sans-serif; }
        .shell { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 20px; }
        .card { width:100%; max-width:520px; background:#fff; border-radius:20px; box-shadow:0 24px 56px -32px rgba(15,23,42,.22),0 0 0 1px rgba(148,163,184,.18); padding:36px; }
        .logo { display:flex; justify-content:center; margin-bottom:28px; }
        .logo img { height:68px; width:auto; object-fit:contain; }
        .title { text-align:center; margin-bottom:24px; }
        .title h1 { margin:0; font-weight:700; font-size:1.2rem; display:flex; justify-content:center; align-items:center; gap:10px; color:var(--dt-page-text); }
        .muted { color:var(--dt-muted); font-size:.82rem; text-align:center; margin-bottom:1.25rem; }
        .status-ok { background:var(--dt-success-bg); color:var(--dt-success-text); border:1px solid var(--dt-success-border); border-radius:8px; padding:.75rem 1rem; font-size:.82rem; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem; }
        .status-err { background:var(--dt-danger-bg); color:var(--dt-danger-text); border:1px solid var(--dt-danger-border); border-radius:8px; padding:.75rem 1rem; font-size:.82rem; margin-bottom:1rem; }
        .label { font-size:.8rem; font-weight:600; display:block; margin-bottom:.5rem; }
        .file-input { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); border-radius:8px; padding:11px 12px; font-size:.82rem; }
        .file-input:focus { border-color:var(--dt-indigo); outline:none; box-shadow:0 0 0 3px rgba(75,73,172,.15); }
        .btn-submit { width:100%; margin-top:1.25rem; border:none; border-radius:8px; padding:11px; font-size:.875rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:.5rem; background:var(--dt-indigo); color:#fff; }
        .btn-submit:hover { background:#3b3a8e; }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="logo">
                <img src="{{ asset('img/image.png') }}" alt="Dakar Terminal">
            </div>
            <div class="title">
                <h1><i class="fas fa-file-upload" style="color:var(--dt-indigo)"></i> Upload Manifest</h1>
            </div>
            <p class="muted">Chargez un fichier manifeste au format <strong>.txt</strong>.</p>

            @if(session('success'))
                <div class="status-ok">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="status-err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('planification.upload-manifest.store') }}" enctype="multipart/form-data">
                @csrf
                <label class="label" for="manifest-file">Fichier manifeste (.txt)</label>
                <input id="manifest-file" type="file" name="manifest" class="file-input" accept=".txt,text/plain" required>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-upload"></i> Importer et générer les fichiers
                </button>
            </form>
        </div>
    </div>
</body>
</html>
@endauth
