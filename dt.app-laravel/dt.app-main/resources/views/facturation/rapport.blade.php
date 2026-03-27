<x-layouts::app :title="__('Gestion des rapports')">
    @php
        $isAdmin = auth()->user()?->role?->name === 'ADMIN';
        $initialRapportsCollection = collect($initialRapports ?? []);
        $initialRapportsPage = $initialRapportsCollection->take(5);
        $initialStationnementsCollection = collect($initialStationnements ?? []);
        $initialStationnementsPage = $initialStationnementsCollection->take(5);
    @endphp

    <div class="rapport-page flex h-full w-full flex-1 flex-col gap-6 pb-8">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .rapport-page { overflow: visible; min-height: max-content; }
            .rapport-page .module-tabs { display:flex; justify-content:center; gap:6px; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; border-bottom:2px solid var(--dt-border); margin-bottom:22px; background:var(--dt-panel-bg); padding:0 8px 6px; scrollbar-width:thin; box-shadow:var(--dt-shadow); }
            .rapport-page .module-tab { flex:0 0 auto; border:none; background:transparent; color:var(--dt-muted-text); font-size:14px; font-weight:600; padding:12px 16px; border-bottom:3px solid transparent; margin-bottom:-2px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:color .2s; }
            .rapport-page .module-tab:hover { color:#4B49AC; }
            .rapport-page .module-tab.active { color:#4B49AC; border-bottom-color:#4B49AC; }
            .rapport-page .module-pane { display:none; opacity:0; transform:translateY(10px); }
            .rapport-page .module-pane.active { display:block; animation:tabPaneFade .25s ease forwards; }
            @keyframes tabPaneFade { from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)} }
            .rapport-page .r-card { background:var(--dt-panel-bg); color:var(--dt-page-text); border:1px solid var(--dt-border); border-radius:12px; box-shadow:var(--dt-shadow); padding:24px; max-width:1200px; margin:0 auto; }
            .rapport-page .r-card-fluid { max-width:none; width:100%; margin:0; }
            .rapport-page .r-card-title { font-size:20px; font-weight:700; color:var(--dt-page-text); display:flex; align-items:center; gap:10px; margin-bottom:16px; }
            .rapport-page .r-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
            .rapport-page .r-toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
            .rapport-page .r-search { width:260px; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:7px; padding:7px 12px; font-size:13px; outline:none; }
            .rapport-page .r-search:focus { border-color:#4B49AC; box-shadow:0 0 0 4px var(--dt-ring); }
            .rapport-page .r-filter-field { position:relative; min-width:130px; }
            .rapport-page .r-filter-icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--dt-soft-text); font-size:12px; pointer-events:none; }
            .rapport-page .r-column-search { width:100%; min-width:130px; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:8px 10px 8px 30px; font-size:12px; outline:none; }
            .rapport-page .r-column-search:focus { border-color:#4B49AC; box-shadow:0 0 0 4px var(--dt-ring); }
            .rapport-page .r-column-search::placeholder { color:var(--dt-soft-text); }
            .rapport-page .r-btn { border:none; border-radius:7px; padding:8px 14px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:7px; min-height:36px; text-decoration:none; transition:opacity .15s; }
            .rapport-page .r-btn:hover { opacity:.88; }
            .rapport-page .r-btn-primary { background:#4B49AC; color:#fff; }
            .rapport-page .r-btn-light { background:var(--dt-panel-alt-bg); color:#818cf8; border:1px solid var(--dt-border); }
            .rapport-page .r-btn-export { background:#28a745; color:#fff; }
            .rapport-page .r-file-input { border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:8px; font-size:13px; width:100%; margin-bottom:14px; }
            .rapport-page .r-admin-card { border:1px solid var(--dt-border); background:var(--dt-panel-alt-bg); border-radius:10px; padding:18px; }
            .rapport-page .r-status { margin:12px 0; padding:10px 14px; border-radius:8px; display:none; font-size:13px; }
            .rapport-page .r-status-ok  { background:var(--dt-success-bg); color:var(--dt-success-text); border:1px solid var(--dt-success-border); }
            .rapport-page .r-status-err { background:var(--dt-danger-bg);  color:var(--dt-danger-text);  border:1px solid var(--dt-danger-border); }
            .rapport-page .table-card { background:var(--dt-panel-bg); border:1px solid var(--dt-border); border-radius:12px; box-shadow:var(--dt-shadow); overflow:hidden; }
            .rapport-page .table-responsive { overflow:auto; }
            .rapport-page .table-card table { margin:0; width:100%; border-collapse:collapse; }
            .rapport-page .table-card thead th { background:var(--dt-table-head-bg); font-size:12px; font-weight:700; color:var(--dt-page-text); border-bottom:2px solid var(--dt-border); white-space:nowrap; text-align:left; padding:14px 16px; }
            .rapport-page .table-card tbody td { font-size:13px; vertical-align:middle; padding:14px 16px; border-top:1px solid var(--dt-border); color:var(--dt-page-text); }
            .rapport-page .empty-state { text-align:center; padding:48px; color:var(--dt-soft-text); }
            .rapport-page .pagination-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding:12px 16px 16px; font-size:13px; color:var(--dt-muted-text); }
            .rapport-page .pagination-pages { display:flex; gap:4px; align-items:center; }
            .rapport-page .page-btn { border:1px solid var(--dt-border); background:var(--dt-panel-alt-bg); border-radius:6px; padding:4px 10px; font-size:13px; cursor:pointer; color:var(--dt-page-text); }
            .rapport-page .page-btn:hover { background:var(--dt-table-head-bg); border-color:#4B49AC; color:#818cf8; }
            .rapport-page .page-btn.active { background:#4B49AC; color:#fff; border-color:#4B49AC; }
            .rapport-page .page-btn:disabled { opacity:.4; cursor:default; }
            .rapport-page .r-select { border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:8px 12px; font-size:13px; outline:none; width:100%; margin-bottom:14px; }
            .rapport-page .r-select:focus { border-color:#4B49AC; box-shadow:0 0 0 4px var(--dt-ring); }
            .rapport-page .r-month-select { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:7px 8px; font-size:12px; outline:none; cursor:pointer; }
            .rapport-page .r-month-select:focus { border-color:#4B49AC; box-shadow:0 0 0 4px var(--dt-ring); }
        </style>

        <div class="dt-page-header" style="text-align:center">
            <h1><i class="fas fa-boxes-stacked" style="color:#4B49AC"></i>Gestion des rapports</h1>
        </div>

        <div class="module-tabs">
            <button type="button" class="module-tab active" data-target="rapport-suivi"><i class="fas fa-list"></i> Suivi des vides</button>
            <button type="button" class="module-tab" data-target="rapport-stationnement"><i class="fas fa-square-parking"></i> Suivi stationnement</button>
            @if ($isAdmin)
                <button type="button" class="module-tab" data-target="rapport-admin"><i class="fas fa-cog"></i> Admin</button>
            @endif
        </div>

        {{-- ===== ONGLET SUIVI VIDES ===== --}}
        <div id="rapport-suivi" class="module-pane active">
            <div class="r-card r-card-fluid">
                <div class="r-toolbar">
                    <div class="r-toolbar-left">
                        <button type="button" class="r-btn r-btn-primary" id="rapport-refresh"><i class="fas fa-sync-alt"></i> Actualiser</button>
                    </div>
                    <a href="/facturation/api/rapports/export" class="r-btn r-btn-export"><i class="fas fa-file-excel"></i> Exporter Excel</a>
                </div>

                <div class="table-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Terminal</th>
                                    <th>Equipment Number</th>
                                    <th>Type / Size</th>
                                    <th>Event Code</th>
                                    <th>Event Name</th>
                                    <th>Event Family</th>
                                    <th>Event Date</th>
                                    <th>Booking Sec No</th>
                                </tr>
                                <tr>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="terminal" type="search" placeholder="Terminal" aria-label="Filtrer Terminal">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="equipmentNumber" type="search" placeholder="Equipment Number" aria-label="Filtrer Equipment Number">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="equipmentTypeSize" type="search" placeholder="Type / Size" aria-label="Filtrer Type / Size">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="eventCode" type="search" placeholder="Event Code" aria-label="Filtrer Event Code">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="eventName" type="search" placeholder="Event Name" aria-label="Filtrer Event Name">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="eventFamily" type="search" placeholder="Event Family" aria-label="Filtrer Event Family">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="eventDate" type="search" placeholder="ex: Avril 2025" aria-label="Filtrer Event Date">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search rapport-column-search" data-key="bookingSecNo" type="search" placeholder="Booking Sec No" aria-label="Filtrer Booking Sec No">
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="rapport-tbody">
                                @forelse ($initialRapportsPage as $rapport)
                                    <tr>
                                        <td>{{ $rapport['terminal'] ?: '-' }}</td>
                                        <td>{{ $rapport['equipmentNumber'] ?: '-' }}</td>
                                        <td>{{ $rapport['equipmentTypeSize'] ?: '-' }}</td>
                                        <td>{{ $rapport['eventCode'] ?: '-' }}</td>
                                        <td>{{ $rapport['eventName'] ?: '-' }}</td>
                                        <td>{{ $rapport['eventFamily'] ?: '-' }}</td>
                                        <td>{{ $rapport['eventDate'] ?: '-' }}</td>
                                        <td>{{ $rapport['bookingSecNo'] ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="empty-state"><i class="fas fa-inbox fa-2x" style="display:block;margin-bottom:10px;color:#ccc"></i>Aucune donnee disponible.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-bar" id="rapport-pagination-bar" style="{{ $initialRapportsCollection->count() > 5 ? 'display:flex' : 'display:none' }}">
                        <span id="rapport-count-info">
                            @if ($initialRapportsCollection->isNotEmpty())
                                1-{{ min(5, $initialRapportsCollection->count()) }} sur {{ $initialRapportsCollection->count() }}
                            @else
                                0 sur 0
                            @endif
                        </span>
                        <div class="pagination-pages" id="rapport-pagination-pages">
                            @if ($initialRapportsCollection->count() > 1)
                                <button class="page-btn" onclick="renderRapportPage(0)" disabled><i class="fas fa-chevron-left"></i></button>
                                <button class="page-btn active" onclick="renderRapportPage(1)">1</button>
                                @if ($initialRapportsCollection->count() > 5)
                                    <button class="page-btn" onclick="renderRapportPage(2)">2</button>
                                @endif
                                <button class="page-btn" onclick="renderRapportPage(2)" {{ $initialRapportsCollection->count() <= 5 ? 'disabled' : '' }}><i class="fas fa-chevron-right"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== ONGLET SUIVI STATIONNEMENT ===== --}}
        <div id="rapport-stationnement" class="module-pane">
            <div class="r-card r-card-fluid">
                <div class="r-toolbar">
                    <div class="r-toolbar-left">
                        <button type="button" class="r-btn r-btn-primary" id="stat-refresh"><i class="fas fa-sync-alt"></i> Actualiser</button>
                    </div>
                    <a href="/facturation/api/suivi-stationnements/export" class="r-btn r-btn-export"><i class="fas fa-file-excel"></i> Exporter Excel</a>
                </div>

                <div class="table-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Terminal</th>
                                    <th>Billing Date</th>
                                    <th>Shipowner</th>
                                    <th>BL Number</th>
                                    <th>Item Number</th>
                                    <th>Item Type</th>
                                    <th>Type</th>
                                    <th>Entry Date</th>
                                    <th>Exit Date</th>
                                    <th>Days Since In</th>
                                </tr>
                                <tr>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="terminal" type="search" placeholder="Terminal">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="billingDate" type="search" placeholder="ex: 01/2025" aria-label="Filtrer Billing Date">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="shipowner" type="search" placeholder="Shipowner">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="blNumber" type="search" placeholder="BL Number">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="itemNumber" type="search" placeholder="Item Number">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="itemType" type="search" placeholder="Item Type">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="type" type="search" placeholder="Type">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="entryDate" type="search" placeholder="ex: 01/2025" aria-label="Filtrer Entry Date">
                                        </div>
                                    </th>
                                    <th>
                                        <div class="r-filter-field">
                                            <i class="fas fa-filter r-filter-icon"></i>
                                            <input class="r-column-search stat-column-search" data-key="exitDate" type="search" placeholder="ex: 01/2025" aria-label="Filtrer Exit Date">
                                        </div>
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="stat-tbody">
                                @forelse ($initialStationnementsPage as $stat)
                                    <tr>
                                        <td>{{ $stat['terminal'] ?: '-' }}</td>
                                        <td>{{ $stat['billingDate'] ?: '-' }}</td>
                                        <td>{{ $stat['shipowner'] ?: '-' }}</td>
                                        <td>{{ $stat['blNumber'] ?: '-' }}</td>
                                        <td>{{ $stat['itemNumber'] ?: '-' }}</td>
                                        <td>{{ $stat['itemType'] ?: '-' }}</td>
                                        <td>{{ $stat['type'] ?: '-' }}</td>
                                        <td>{{ $stat['entryDate'] ?: '-' }}</td>
                                        <td>{{ $stat['exitDate'] ?: '-' }}</td>
                                        <td>{{ $stat['daysSinceIn'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="empty-state"><i class="fas fa-inbox fa-2x" style="display:block;margin-bottom:10px;color:#ccc"></i>Aucune donnee disponible.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-bar" id="stat-pagination-bar" style="{{ $initialStationnementsCollection->count() > 5 ? 'display:flex' : 'display:none' }}">
                        <span id="stat-count-info">
                            @if ($initialStationnementsCollection->isNotEmpty())
                                1-{{ min(5, $initialStationnementsCollection->count()) }} sur {{ $initialStationnementsCollection->count() }}
                            @else
                                0 sur 0
                            @endif
                        </span>
                        <div class="pagination-pages" id="stat-pagination-pages"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== ONGLET ADMIN ===== --}}
        @if ($isAdmin)
            <div id="rapport-admin" class="module-pane">
                <div class="r-card">
                    <div class="r-card-title"><i class="fas fa-cog" style="color:#4B49AC"></i> Administration</div>
                    <div id="admin-status" class="r-status"></div>
                    <div class="r-admin-card">
                        <h5 style="margin-bottom:8px;font-weight:700;color:var(--dt-page-text)">Importer un fichier</h5>
                        <p style="font-size:13px;color:var(--dt-muted-text);margin-bottom:14px">
                            Formats acceptes : <strong>XLSX, CSV</strong> (separateur , ou ;).
                        </p>
                        <label style="font-size:13px;font-weight:600;color:var(--dt-page-text);display:block;margin-bottom:6px">
                            Rapport a importer
                        </label>
                        <select id="admin-rapport-type" class="r-select">
                            <option value="suivi-vides">Suivi des vides</option>
                            <option value="suivi-stationnements">Suivi stationnement</option>
                        </select>
                        <input type="file" id="admin-import-file" accept=".csv,.xlsx" class="r-file-input">
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="button" id="admin-import-btn" class="r-btn r-btn-primary"><i class="fas fa-upload"></i> Importer</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <script>
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const PAGE_SIZE = 5;

            let rapportCurrentPage = 1;
            let rapportDebounce = null;
            let statCurrentPage = 1;
            let statDebounce = null;
            let statLoaded = false;

            function escHtml(str) {
                if (str == null) return '-';
                const s = String(str);
                return s === '' ? '-' : s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }

            function setStatus(el, message, success) {
                if (!el) return;
                el.textContent = message;
                el.className = 'r-status ' + (success ? 'r-status-ok' : 'r-status-err');
                el.style.display = 'block';
            }

            function buildPagination(currentPage, totalPages, goFn) {
                let html = `<button class="page-btn" onclick="${goFn}(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
                for (let p = 1; p <= totalPages; p++) {
                    if (totalPages <= 7 || p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1) {
                        html += `<button class="page-btn ${p === currentPage ? 'active' : ''}" onclick="${goFn}(${p})">${p}</button>`;
                    } else if (Math.abs(p - currentPage) === 2) {
                        html += '<span style="padding:4px 6px;color:var(--dt-muted-text)">...</span>';
                    }
                }
                html += `<button class="page-btn" onclick="${goFn}(${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
                return html;
            }

            // ===== SUIVI VIDES =====
            function getRapportParams(page) {
                const p = new URLSearchParams({ page: page - 1, size: PAGE_SIZE });
                document.querySelectorAll('.rapport-column-search').forEach(el => {
                    if (el.value.trim()) p.set(el.dataset.key, el.value.trim());
                });
                return p;
            }

            async function loadRapports(page) {
                rapportCurrentPage = page;
                const tbody = document.getElementById('rapport-tbody');
                if (!tbody) return;
                tbody.style.opacity = '0.5';
                tbody.style.pointerEvents = 'none';
                try {
                    const res = await fetch('/facturation/api/rapports?' + getRapportParams(page));
                    const data = await res.json();
                    const rows = data.content || [];
                    const total = data.totalElements ?? 0;
                    const totalPages = data.totalPages ?? 1;
                    const start = (rapportCurrentPage - 1) * PAGE_SIZE;

                    if (rows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><i class="fas fa-inbox fa-2x" style="display:block;margin-bottom:10px;color:#ccc"></i>Aucune donnee disponible.</td></tr>';
                    } else {
                        tbody.innerHTML = rows.map(r => `<tr>
                            <td>${escHtml(r.terminal)}</td>
                            <td>${escHtml(r.equipmentNumber)}</td>
                            <td>${escHtml(r.equipmentTypeSize)}</td>
                            <td>${escHtml(r.eventCode)}</td>
                            <td>${escHtml(r.eventName)}</td>
                            <td>${escHtml(r.eventFamily)}</td>
                            <td>${escHtml(r.eventDate)}</td>
                            <td>${escHtml(r.bookingSecNo)}</td>
                        </tr>`).join('');
                    }

                    const bar = document.getElementById('rapport-pagination-bar');
                    bar.style.display = total > PAGE_SIZE ? 'flex' : 'none';
                    document.getElementById('rapport-count-info').textContent =
                        total > 0 ? `${start + 1}-${Math.min(start + PAGE_SIZE, total)} sur ${total}` : '0 sur 0';
                    document.getElementById('rapport-pagination-pages').innerHTML =
                        total > PAGE_SIZE ? buildPagination(rapportCurrentPage, totalPages, 'loadRapports') : '';
                } catch {
                    tbody.innerHTML = '<tr><td colspan="8" class="empty-state">Erreur de chargement.</td></tr>';
                } finally {
                    tbody.style.opacity = '';
                    tbody.style.pointerEvents = '';
                }
            }

            // ===== SUIVI STATIONNEMENT =====
            function getStatParams(page) {
                const p = new URLSearchParams({ page: page - 1, size: PAGE_SIZE });
                document.querySelectorAll('.stat-column-search').forEach(el => {
                    if (el.value.trim()) p.set(el.dataset.key, el.value.trim());
                });
                return p;
            }

            async function loadStationnements(page) {
                statCurrentPage = page;
                statLoaded = true;
                const tbody = document.getElementById('stat-tbody');
                if (!tbody) return;
                tbody.style.opacity = '0.5';
                tbody.style.pointerEvents = 'none';
                try {
                    const res = await fetch('/facturation/api/suivi-stationnements?' + getStatParams(page));
                    const data = await res.json();
                    const rows = data.content || [];
                    const total = data.totalElements ?? 0;
                    const totalPages = data.totalPages ?? 1;
                    const start = (statCurrentPage - 1) * PAGE_SIZE;

                    if (rows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="10" class="empty-state"><i class="fas fa-inbox fa-2x" style="display:block;margin-bottom:10px;color:#ccc"></i>Aucune donnee disponible.</td></tr>';
                    } else {
                        tbody.innerHTML = rows.map(r => `<tr>
                            <td>${escHtml(r.terminal)}</td>
                            <td>${escHtml(r.billingDate)}</td>
                            <td>${escHtml(r.shipowner)}</td>
                            <td>${escHtml(r.blNumber)}</td>
                            <td>${escHtml(r.itemNumber)}</td>
                            <td>${escHtml(r.itemType)}</td>
                            <td>${escHtml(r.type)}</td>
                            <td>${escHtml(r.entryDate)}</td>
                            <td>${escHtml(r.exitDate)}</td>
                            <td>${escHtml(r.daysSinceIn)}</td>
                        </tr>`).join('');
                    }

                    const bar = document.getElementById('stat-pagination-bar');
                    bar.style.display = total > PAGE_SIZE ? 'flex' : 'none';
                    document.getElementById('stat-count-info').textContent =
                        total > 0 ? `${start + 1}-${Math.min(start + PAGE_SIZE, total)} sur ${total}` : '0 sur 0';
                    document.getElementById('stat-pagination-pages').innerHTML =
                        total > PAGE_SIZE ? buildPagination(statCurrentPage, totalPages, 'loadStationnements') : '';
                } catch {
                    tbody.innerHTML = '<tr><td colspan="10" class="empty-state">Erreur de chargement.</td></tr>';
                } finally {
                    tbody.style.opacity = '';
                    tbody.style.pointerEvents = '';
                }
            }

            // ===== INIT (réattache tous les listeners à chaque navigation) =====
            function initRapportPage() {
                if (!document.getElementById('rapport-tbody')) return;

                statLoaded = false;

                // Onglets
                document.querySelectorAll('.module-tab').forEach(tab => {
                    tab.onclick = () => {
                        document.querySelectorAll('.module-tab').forEach(t => t.classList.remove('active'));
                        document.querySelectorAll('.module-pane').forEach(p => p.classList.remove('active'));
                        tab.classList.add('active');
                        const target = document.getElementById(tab.dataset.target);
                        if (target) target.classList.add('active');
                        if (tab.dataset.target === 'rapport-stationnement' && !statLoaded) {
                            loadStationnements(1);
                        }
                    };
                });

                // Boutons actualiser
                const rapportRefresh = document.getElementById('rapport-refresh');
                if (rapportRefresh) rapportRefresh.onclick = () => loadRapports(1);
                const statRefresh = document.getElementById('stat-refresh');
                if (statRefresh) statRefresh.onclick = () => loadStationnements(1);

                // Filtres suivi vides
                document.querySelectorAll('.rapport-column-search').forEach(el => {
                    const handler = () => {
                        clearTimeout(rapportDebounce);
                        rapportDebounce = setTimeout(() => loadRapports(1), 300);
                    };
                    if (el.tagName === 'SELECT') { el.onchange = handler; }
                    else { el.oninput = handler; }
                });

                // Filtres stationnement
                document.querySelectorAll('.stat-column-search').forEach(el => {
                    const handler = () => {
                        clearTimeout(statDebounce);
                        statDebounce = setTimeout(() => loadStationnements(1), 300);
                    };
                    if (el.tagName === 'SELECT') { el.onchange = handler; }
                    else { el.oninput = handler; }
                });

                // Admin import
                const adminImportBtn = document.getElementById('admin-import-btn');
                if (adminImportBtn) {
                    adminImportBtn.onclick = async () => {
                        const fileInput = document.getElementById('admin-import-file');
                        const rapportType = document.getElementById('admin-rapport-type').value;
                        const adminStatus = document.getElementById('admin-status');

                        if (!fileInput.files.length) {
                            setStatus(adminStatus, 'Selectionnez un fichier a importer.', false);
                            return;
                        }

                        const endpoint = rapportType === 'suivi-stationnements'
                            ? '/facturation/api/suivi-stationnements/import'
                            : '/facturation/api/rapports/import';

                        adminImportBtn.disabled = true;
                        adminImportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Import en cours...';

                        const formData = new FormData();
                        formData.append('file', fileInput.files[0]);
                        formData.append('_token', csrfToken);

                        const resp = await fetch(endpoint, { method: 'POST', body: formData });
                        const msg = await resp.text();
                        setStatus(adminStatus, msg, resp.ok);

                        if (resp.ok) {
                            fileInput.value = '';
                            if (rapportType === 'suivi-stationnements') {
                                loadStationnements(1);
                            } else {
                                loadRapports(1);
                            }
                        }

                        adminImportBtn.disabled = false;
                        adminImportBtn.innerHTML = '<i class="fas fa-upload"></i> Importer';
                    };
                }

                // Chargement initial des données
                loadRapports(1);
            }

            // Eviter l'accumulation de listeners sur le document lors des navigations successives
            document.removeEventListener('livewire:navigated', initRapportPage);
            document.addEventListener('livewire:navigated', initRapportPage);
            initRapportPage();
        </script>
    </div>
</x-layouts::app>
