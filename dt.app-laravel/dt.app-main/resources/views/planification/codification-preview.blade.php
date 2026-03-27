<x-layouts::app :title="'Prévisualisation — ' . $codification->call_number">

<style>
    :root {
        --cv-bg: #F0F4F8; --surface: #FFFFFF; --surface2: #E8EFF7;
        --border: rgba(30,80,160,.1); --border-h: rgba(0,160,145,.4);
        --cyan: #0097A7; --cyan-dim: rgba(0,151,167,.08);
        --indigo: #6366f1; --green: #1D6F42;
        --text: #0F172A; --text-dim: #475569;
        --mono: 'Space Mono', monospace; --sans: 'DM Sans', sans-serif;
    }
    .cv-wrap { background: var(--cv-bg); min-height: 100%; padding: 2rem 1.5rem 5rem; }
    .cv-inner { max-width: 1400px; margin: 0 auto; }

    .cv-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; }
    .cv-badge { display:inline-flex; align-items:center; gap:.5rem; font-size:.75rem; letter-spacing:.1em; background:var(--cyan-dim); color:var(--cyan); border:1px solid var(--border-h); padding:.35rem .9rem; border-radius:20px; text-transform:uppercase; }
    .cv-title { font-size:1.5rem; font-weight:700; color:var(--text); margin:.25rem 0 0; font-family:monospace; }
    .cv-meta { font-size:.85rem; color:var(--text-dim); margin-top:.2rem; }

    .cv-tabs { display:flex; gap:.5rem; margin-bottom:1.5rem; border-bottom:2px solid var(--border); padding-bottom:.75rem; }
    .cv-tab { font-size:.875rem; font-weight:600; padding:.55rem 1.4rem; border-radius:8px; border:1px solid var(--border); background:var(--surface); color:var(--text-dim); cursor:pointer; transition:all .2s; }
    .cv-tab.active { background:var(--indigo); color:#fff; border-color:var(--indigo); }
    .cv-tab:hover:not(.active) { border-color:var(--indigo); color:var(--indigo); }

    .cv-panel { display:none; }
    .cv-panel.active { display:block; }

    .cv-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
    .cv-card-head { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.5rem; border-bottom:1px solid var(--border); background:var(--surface2); flex-wrap:wrap; gap:.75rem; }
    .cv-card-head h3 { margin:0; font-size:1rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:.6rem; }
    .cv-count { font-size:.8rem; color:var(--text-dim); }

    /* ── Search bar ── */
    .cv-search-row { display:flex; align-items:center; gap:.5rem; }
    .cv-search-input { border:1px solid #cbd5e1; background:#f8fafc; border-radius:8px; padding:.4rem .8rem; font-size:.78rem; color:var(--text); width:220px; outline:none; transition:border-color .15s,box-shadow .15s; }
    .cv-search-input:focus { border-color:var(--indigo); box-shadow:0 0 0 3px rgba(99,102,241,.12); }
    .cv-search-clear { background:none; border:none; cursor:pointer; color:var(--text-dim); padding:.3rem; border-radius:6px; display:none; align-items:center; }
    .cv-search-clear:hover { color:var(--indigo); }
    .cv-match-count { font-size:.75rem; color:var(--text-dim); white-space:nowrap; }

    .cv-table-wrap { overflow-x:auto; max-height:65vh; overflow-y:auto; }
    .cv-table { width:100%; border-collapse:collapse; font-size:.78rem; min-width:900px; }
    .cv-table thead th { position:sticky; top:0; z-index:2; background:#1F3864; color:#fff; font-weight:700; padding:.6rem .75rem; text-align:left; white-space:nowrap; border-right:1px solid rgba(255,255,255,.1); }
    .cv-table tbody tr:nth-child(even) { background:#EEF2F7; }
    .cv-table tbody tr:hover { background:rgba(99,102,241,.06); }
    .cv-table tbody tr.cv-hidden { display:none; }
    .cv-table tbody td { padding:.5rem .75rem; border-bottom:1px solid #e2e8f0; border-right:1px solid #e2e8f0; white-space:nowrap; max-width:200px; overflow:hidden; text-overflow:ellipsis; color:var(--text); }
    .cv-table tbody td mark { background:#fef08a; color:inherit; border-radius:2px; padding:0 1px; }

    /* ── IFTMIN panel ── */
    .cv-iftmin-wrap { position:relative; }
    .cv-iftmin { background:#0F172A; color:#e2e8f0; border-radius:0 0 14px 14px; padding:1.5rem; font-family:monospace; font-size:.78rem; line-height:1.7; overflow-x:auto; white-space:pre; max-height:70vh; overflow-y:auto; }
    .cv-iftmin mark { background:#854d0e; color:#fef9c3; border-radius:2px; padding:0 1px; }
    .cv-iftmin-nav { display:flex; align-items:center; gap:.4rem; }
    .cv-iftmin-nav-btn { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); color:#e2e8f0; border-radius:6px; padding:.25rem .6rem; font-size:.75rem; cursor:pointer; }
    .cv-iftmin-nav-btn:hover { background:rgba(99,102,241,.3); border-color:var(--indigo); }
    .cv-iftmin-nav-btn:disabled { opacity:.35; cursor:default; }

    .cv-dl-row { display:flex; gap:.75rem; flex-wrap:wrap; }
    .cv-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1.25rem; border-radius:8px; border:none; font-size:.875rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .2s; }
    .cv-btn-xlsx { background:#1D6F42; color:#fff; }
    .cv-btn-xlsx:hover { background:#155533; color:#fff; }
    .cv-btn-iftmin { background:#0F4C81; color:#fff; }
    .cv-btn-iftmin:hover { background:#0a3560; color:#fff; }
    .cv-btn-txt { background:#64748b; color:#fff; }
    .cv-btn-txt:hover { background:#475569; color:#fff; }
    .cv-btn-back { background:var(--surface); color:var(--text-dim); border:1px solid var(--border); }
    .cv-btn-back:hover { border-color:var(--indigo); color:var(--indigo); }

    .cv-empty { padding:3rem; text-align:center; color:var(--text-dim); font-size:.9rem; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    .cv-inner > * { animation:fadeUp .45s ease both; }
</style>

<div class="cv-wrap">
<div class="cv-inner">

    {{-- HEADER --}}
    <div class="cv-header">
        <div>
            <div class="cv-badge">Codification #{{ $codification->id }}</div>
            <div class="cv-title">{{ $codification->call_number }}</div>
            <div class="cv-meta">Importé le {{ $codification->created_at->format('d/m/Y à H:i') }}</div>
        </div>
        <div class="cv-dl-row">
            <a href="{{ route('planification.codification.download.xls', $codification) }}" class="cv-btn cv-btn-xlsx">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Télécharger XLS
            </a>
            <a href="{{ route('planification.codification.download.iftmin', $codification) }}" class="cv-btn cv-btn-iftmin">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Télécharger IFTMIN
            </a>
            <a href="{{ route('planification.codification.download.manifest', $codification) }}" class="cv-btn cv-btn-txt">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Télécharger TXT
            </a>
            <a href="{{ route('planification.upload-manifest') }}" class="cv-btn cv-btn-back">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                Retour
            </a>
        </div>
    </div>

    {{-- TABS --}}
    <div class="cv-tabs">
        <button class="cv-tab active" onclick="switchTab('xlsx', this)">
            📊 Prévisualisation XLS
            <span id="xlsx-tab-count" style="background:rgba(99,102,241,.12);color:var(--indigo);border-radius:20px;padding:1px 8px;font-size:.72rem;margin-left:.4rem;font-weight:700;">
                {{ count($xlsxRows) }} lignes
            </span>
        </button>
        <button class="cv-tab" onclick="switchTab('iftmin', this)">
            📄 Prévisualisation IFTMIN
        </button>
    </div>

    {{-- PANEL XLSX --}}
    <div id="panel-xlsx" class="cv-panel active">
        <div class="cv-card">
            <div class="cv-card-head">
                <h3>
                    <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="3"/></svg>
                    Données XLS — {{ $codification->call_number }}
                </h3>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <span class="cv-count" id="xlsx-row-count">{{ count($xlsxRows) }} enregistrements · {{ count($xlsxHeaders) }} colonnes</span>
                    @if(count($xlsxRows) > 0)
                    <div class="cv-search-row">
                        <input type="text" id="xlsx-search" class="cv-search-input" placeholder="Rechercher dans le tableau…" oninput="filterXlsx(this.value)">
                        <button class="cv-search-clear" id="xlsx-clear" onclick="clearXlsxSearch()" title="Effacer">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                        <span class="cv-match-count" id="xlsx-match"></span>
                    </div>
                    @endif
                </div>
            </div>
            @if(count($xlsxRows) > 0)
                <div class="cv-table-wrap">
                    <table class="cv-table" id="xlsx-table">
                        <thead>
                            <tr>
                                @foreach($xlsxHeaders as $header)
                                    <th title="{{ $header }}">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="xlsx-tbody">
                            @foreach($xlsxRows as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td title="{{ $cell }}">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cv-empty">Aucune donnée à afficher dans le fichier XLS.</div>
            @endif
        </div>
    </div>

    {{-- PANEL IFTMIN --}}
    <div id="panel-iftmin" class="cv-panel">
        <div class="cv-card">
            <div class="cv-card-head">
                <h3>
                    <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Contenu IFTMIN brut
                </h3>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <span class="cv-count">Format UN/EDIFACT IFTMIN D04 96B</span>
                    @if($iftminContent)
                    <div class="cv-search-row">
                        <input type="text" id="iftmin-search" class="cv-search-input" placeholder="Rechercher dans IFTMIN…" style="background:#1e293b;border-color:#334155;color:#e2e8f0;" oninput="filterIftmin(this.value)">
                        <button class="cv-search-clear" id="iftmin-clear" onclick="clearIftminSearch()" title="Effacer" style="color:#94a3b8;">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                        <div class="cv-iftmin-nav" id="iftmin-nav" style="display:none;">
                            <button class="cv-iftmin-nav-btn" id="iftmin-prev" onclick="iftminNav(-1)" disabled>‹</button>
                            <span class="cv-match-count" id="iftmin-match" style="color:#94a3b8;"></span>
                            <button class="cv-iftmin-nav-btn" id="iftmin-next" onclick="iftminNav(1)" disabled>›</button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @if($iftminContent)
                <div class="cv-iftmin-wrap">
                    <div class="cv-iftmin" id="iftmin-content">{{ $iftminContent }}</div>
                </div>
            @else
                <div class="cv-empty">Fichier IFTMIN introuvable ou vide.</div>
            @endif
        </div>
    </div>

</div>
</div>

<script>
// ── Tab switching ────────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.cv-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.cv-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + name).classList.add('active');
    btn.classList.add('active');
}

// ── XLSX search ──────────────────────────────────────────────────────────────
function filterXlsx(q) {
    const tbody  = document.getElementById('xlsx-tbody');
    const rows   = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
    const clear  = document.getElementById('xlsx-clear');
    const match  = document.getElementById('xlsx-match');
    const total  = rows.length;

    q = q.trim().toLowerCase();

    if (!q) {
        rows.forEach(r => { r.classList.remove('cv-hidden'); r.querySelectorAll('td').forEach(td => { td.innerHTML = td.textContent; }); });
        if (clear) clear.style.display = 'none';
        if (match) match.textContent   = '';
        return;
    }

    if (clear) clear.style.display = 'flex';

    let count = 0;
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td'));
        const text  = cells.map(c => c.textContent).join(' ').toLowerCase();
        if (text.includes(q)) {
            row.classList.remove('cv-hidden');
            cells.forEach(td => {
                const raw = td.textContent;
                const idx = raw.toLowerCase().indexOf(q);
                if (idx !== -1) {
                    td.innerHTML = escHtml(raw.slice(0, idx))
                        + '<mark>' + escHtml(raw.slice(idx, idx + q.length)) + '</mark>'
                        + escHtml(raw.slice(idx + q.length));
                } else {
                    td.innerHTML = escHtml(raw);
                }
            });
            count++;
        } else {
            row.classList.add('cv-hidden');
            cells.forEach(td => { td.innerHTML = escHtml(td.textContent); });
        }
    });

    if (match) match.textContent = count + ' / ' + total + ' lignes';
}

function clearXlsxSearch() {
    const input = document.getElementById('xlsx-search');
    if (input) { input.value = ''; filterXlsx(''); input.focus(); }
}

// ── IFTMIN search ────────────────────────────────────────────────────────────
let _iftminRaw      = '';
let _iftminMatches  = [];
let _iftminCurrent  = 0;

function filterIftmin(q) {
    const box   = document.getElementById('iftmin-content');
    const clear = document.getElementById('iftmin-clear');
    const nav   = document.getElementById('iftmin-nav');
    const match = document.getElementById('iftmin-match');
    const prev  = document.getElementById('iftmin-prev');
    const next  = document.getElementById('iftmin-next');

    if (!box) return;

    // Cache the original plain text once
    if (!_iftminRaw) _iftminRaw = box.textContent;

    q = q.trim();

    if (!q) {
        box.textContent = _iftminRaw;
        if (clear) clear.style.display = 'none';
        if (nav)   nav.style.display   = 'none';
        _iftminMatches = []; _iftminCurrent = 0;
        return;
    }

    if (clear) clear.style.display = 'flex';

    // Build highlighted HTML
    const lower  = _iftminRaw.toLowerCase();
    const lq     = q.toLowerCase();
    _iftminMatches = [];
    let idx = lower.indexOf(lq, 0);
    while (idx !== -1) { _iftminMatches.push(idx); idx = lower.indexOf(lq, idx + 1); }

    if (_iftminMatches.length === 0) {
        box.innerHTML = escHtml(_iftminRaw);
        if (nav)   nav.style.display   = 'flex';
        if (match) match.textContent   = '0 résultat';
        if (prev)  prev.disabled = true;
        if (next)  next.disabled = true;
        return;
    }

    _iftminCurrent = 0;
    renderIftminHighlights(q);

    if (nav)  nav.style.display = 'flex';
    if (prev) prev.disabled = (_iftminMatches.length <= 1);
    if (next) next.disabled = (_iftminMatches.length <= 1);
}

function renderIftminHighlights(q) {
    const box   = document.getElementById('iftmin-content');
    const match = document.getElementById('iftmin-match');
    if (!box || !_iftminRaw) return;

    const lq    = q ? q.toLowerCase() : document.getElementById('iftmin-search').value.trim().toLowerCase();
    const lower = _iftminRaw.toLowerCase();
    let html = '', cursor = 0;

    _iftminMatches.forEach((pos, i) => {
        html += escHtml(_iftminRaw.slice(cursor, pos));
        const cls = (i === _iftminCurrent) ? ' style="background:#d97706;color:#fff;border-radius:2px;padding:0 1px;"' : '';
        html += '<mark id="iftmin-m' + i + '"' + cls + '>' + escHtml(_iftminRaw.slice(pos, pos + lq.length)) + '</mark>';
        cursor = pos + lq.length;
    });
    html += escHtml(_iftminRaw.slice(cursor));
    box.innerHTML = html;

    if (match) match.textContent = (_iftminCurrent + 1) + ' / ' + _iftminMatches.length;

    // Scroll active match into view
    const el = document.getElementById('iftmin-m' + _iftminCurrent);
    if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function iftminNav(dir) {
    if (!_iftminMatches.length) return;
    _iftminCurrent = (_iftminCurrent + dir + _iftminMatches.length) % _iftminMatches.length;
    const q = document.getElementById('iftmin-search').value.trim();
    renderIftminHighlights(q);
}

function clearIftminSearch() {
    const input = document.getElementById('iftmin-search');
    if (input) { input.value = ''; filterIftmin(''); input.focus(); }
}

// ── Utility ──────────────────────────────────────────────────────────────────
function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

</x-layouts::app>
