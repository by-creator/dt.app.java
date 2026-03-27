<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    .plani-manifest { overflow: visible; min-height: max-content; }
    .plani-manifest .pm-page-title { text-align:center; margin-bottom:24px; }
    .plani-manifest .pm-page-title h1 { margin:0; font-weight:700; display:flex; justify-content:center; gap:10px; color:var(--dt-page-text,#1e293b); }
    .plani-manifest .pm-tabs { display:flex; justify-content:center; gap:6px; flex-wrap:wrap; border-bottom:2px solid var(--dt-border,#e2e8f0); margin-bottom:22px; background:var(--dt-panel-bg,#fff); padding:0 8px 6px; overflow-x:auto; overflow-y:hidden; box-shadow:var(--dt-shadow,0 1px 4px rgba(0,0,0,.06)); }
    .plani-manifest .pm-tab { border:none; background:transparent; color:var(--dt-muted-text,#64748b); font-size:14px; font-weight:600; padding:12px 16px; border-bottom:3px solid transparent; margin-bottom:-2px; display:inline-flex; align-items:center; gap:8px; cursor:default; }
    .plani-manifest .pm-tab.active { color:#4B49AC; border-bottom-color:#4B49AC; }
    .plani-manifest .pm-card { background:var(--dt-panel-bg,#fff); color:var(--dt-page-text,#1e293b); border-radius:12px; box-shadow:var(--dt-shadow,0 1px 4px rgba(0,0,0,.08)); padding:24px; max-width:980px; margin:0 auto 20px; border:1px solid var(--dt-border,#e2e8f0); }
    .plani-manifest .pm-card h3 { font-size:20px; font-weight:700; color:var(--dt-page-text,#1e293b); display:flex; gap:10px; justify-content:center; margin:0 0 18px; }
    .plani-manifest .pm-muted { color:var(--dt-muted-text,#64748b); font-size:13px; text-align:center; margin-bottom:18px; }
    .plani-manifest .pm-form { max-width:500px; margin:20px auto 0; display:grid; gap:16px; }
    .plani-manifest .pm-label { font-size:13px; font-weight:600; color:var(--dt-page-text,#1e293b); display:block; margin-bottom:8px; }
    .plani-manifest .pm-file { width:100%; border:1px solid var(--dt-input-border,#cbd5e1); background:var(--dt-input-bg,#f8fafc); color:var(--dt-page-text,#1e293b); border-radius:8px; padding:12px; font-size:13px; min-height:42px; box-sizing:border-box; }
    .plani-manifest .pm-file:focus, .plani-manifest .pm-file:hover { border-color:#4B49AC; outline:none; box-shadow:0 0 0 4px var(--dt-ring, rgba(75,73,172,.16)); }
    .plani-manifest .pm-btn { border:none; border-radius:7px; padding:10px 16px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:42px; background:#4B49AC; color:#fff; }
    .plani-manifest .pm-btn:hover { background:#3b3a8e; }
    .plani-manifest .pm-btn-dl { border:none; border-radius:7px; padding:10px 20px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; min-height:42px; text-decoration:none; }
    .plani-manifest .pm-btn-xlsx { background:#1D6F42; color:#fff; }
    .plani-manifest .pm-btn-xlsx:hover { background:#155533; color:#fff; }
    .plani-manifest .pm-btn-edi { background:#0F4C81; color:#fff; }
    .plani-manifest .pm-btn-edi:hover { background:#0a3560; color:#fff; }
    .plani-manifest .pm-status-ok { background:var(--dt-success-bg,#f0fdf4); color:var(--dt-success-text,#166534); border:1px solid var(--dt-success-border,#bbf7d0); border-radius:8px; padding:10px; font-size:13px; margin-bottom:12px; }
    .plani-manifest .pm-status-err { background:var(--dt-danger-bg,#fef2f2); color:var(--dt-danger-text,#991b1b); border:1px solid var(--dt-danger-border,#fecaca); border-radius:8px; padding:10px; font-size:13px; margin-bottom:12px; }
    .plani-manifest .pm-dl-row { display:flex; gap:12px; flex-wrap:wrap; justify-content:center; margin-top:16px; }
    .plani-manifest .pm-call-badge { display:inline-flex; align-items:center; gap:6px; background:#eef2ff; color:#4B49AC; border:1px solid #c7d2fe; border-radius:20px; padding:4px 14px; font-size:13px; font-weight:700; margin-bottom:10px; }
</style>

<div class="pm-page-title">
    <h1><i class="fas fa-file-upload" style="color:#4B49AC"></i>Upload Manifest</h1>
</div>

<div class="pm-tabs">
    <button type="button" class="pm-tab active"><i class="fas fa-file"></i> Fichier</button>
</div>

<div class="pm-card">
    <h3><i class="fas fa-file-arrow-up"></i> Import du manifeste (.txt)</h3>
    <p class="pm-muted">Chargez un fichier manifeste au format <strong>.txt</strong>. Le système génèrera automatiquement les fichiers <strong>XLS</strong> et <strong>IFTMIN</strong>.</p>

    @if(session('success'))
        <div class="pm-status-ok">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @if(session('codification_id'))
            <div style="text-align:center;">
                <div class="pm-call-badge">
                    <i class="fas fa-tag"></i>
                    Call Number enregistré
                </div>
                <div class="pm-dl-row">
                    <a href="{{ route('planification.codification.download.xls', session('codification_id')) }}"
                       class="pm-btn-dl pm-btn-xlsx">
                        <i class="fas fa-file-excel"></i> Télécharger XLS
                    </a>
                    <a href="{{ route('planification.codification.download.iftmin', session('codification_id')) }}"
                       class="pm-btn-dl pm-btn-edi">
                        <i class="fas fa-file-code"></i> Télécharger IFTMIN
                    </a>
                    @auth
                    <a href="{{ route('planification.codification.preview', session('codification_id')) }}"
                       class="pm-btn-dl" style="background:#6366f1;color:#fff;">
                        <i class="fas fa-eye"></i> Prévisualiser
                    </a>
                    @endauth
                </div>
            </div>
        @endif
    @endif

    @if($errors->any())
        <div class="pm-status-err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <form class="pm-form" method="POST" action="{{ route('planification.upload-manifest.store') }}" enctype="multipart/form-data">
        @csrf
        <div>
            <label class="pm-label" for="manifest-file">Fichier manifeste (.txt)</label>
            <input id="manifest-file" type="file" name="manifest" class="pm-file" accept=".txt,text/plain" required>
        </div>
        <button type="submit" class="pm-btn">
            <i class="fas fa-upload"></i> Importer et générer les fichiers
        </button>
    </form>
</div>
