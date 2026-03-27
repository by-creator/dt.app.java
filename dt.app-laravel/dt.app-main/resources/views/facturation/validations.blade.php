<x-layouts::app :title="__('Gestion des validations')">
    @php
        $initialDemandesCollection = collect($initialDemandes ?? []);
        $initialDemandesPage = $initialDemandesCollection->take(10);
    @endphp
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .validation-shell { display:flex; flex-direction:column; gap:18px; }
            .toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:18px; }
            .toolbar select { border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:7px; padding:7px 12px; font-size:13px; }
            .search-wrap { position:relative; flex:1; min-width:200px; max-width:340px; }
            .search-wrap input { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:7px; padding:7px 12px 7px 34px; font-size:13px; outline:none; }
            .search-wrap input:focus { border-color:#4B49AC; box-shadow:0 0 0 4px var(--dt-ring); }
            .search-wrap .search-icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--dt-soft-text); font-size:13px; }
            .badge-status { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.4px; }
            .badge-en_attente { background:#fff3cd; color:#856404; }
            .badge-valide { background:#d4edda; color:#155724; }
            .badge-rejete { background:#f8d7da; color:#721c24; }
            .btn-valider { background:#28a745; color:#fff; border:none; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; }
            .btn-valider:hover { background:#218838; }
            .btn-rejeter { background:#dc3545; color:#fff; border:none; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; margin-left:4px; }
            .btn-rejeter:hover { background:#c82333; }
            .table-card { background:var(--dt-panel-bg); border:1px solid var(--dt-border); border-radius:12px; box-shadow:var(--dt-shadow); overflow:hidden; }
            .table-card table { margin:0; width:100%; border-collapse:collapse; }
            .table-card thead th { background:var(--dt-table-head-bg); font-size:12px; font-weight:700; color:var(--dt-page-text); border-bottom:2px solid var(--dt-border); white-space:nowrap; text-align:left; padding:14px 16px; }
            .table-card tbody td { font-size:13px; vertical-align:middle; padding:14px 16px; border-top:1px solid var(--dt-border); color:var(--dt-page-text); }
            .empty-state { text-align:center; padding:48px; color:var(--dt-soft-text); }
            .modal-overlay { display:none; position:fixed; inset:0; background:var(--dt-overlay); z-index:9000; align-items:center; justify-content:center; }
            .modal-overlay.open { display:flex; }
            .modal-box { background:var(--dt-panel-bg); color:var(--dt-page-text); border:1px solid var(--dt-border); border-radius:12px; padding:28px; width:100%; max-width:440px; box-shadow:var(--dt-shadow); }
            .modal-box h5 { font-weight:700; margin-bottom:14px; color:#dc3545; }
            .modal-box textarea { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:10px 12px; font-size:13px; resize:vertical; min-height:90px; }
            .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:16px; }
            .btn-cancel { background:#6c757d; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-size:13px; cursor:pointer; }
            .btn-confirm-reject { background:#dc3545; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer; }
            .pagination-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding:12px 16px 16px; font-size:13px; color:var(--dt-muted-text); }
            .pagination-pages { display:flex; gap:4px; align-items:center; }
            .page-btn { border:1px solid var(--dt-border); background:var(--dt-panel-alt-bg); border-radius:6px; padding:4px 10px; font-size:13px; cursor:pointer; color:var(--dt-page-text); }
            .page-btn:hover { background:var(--dt-table-head-bg); border-color:#4B49AC; color:#818cf8; }
            .page-btn.active { background:#4B49AC; color:#fff; border-color:#4B49AC; }
            .page-btn:disabled { opacity:.4; cursor:default; }
            .page-header h1 { display:flex; align-items:center; gap:10px; margin:0; color:var(--dt-page-text); }
            .btn-refresh { background:#4B49AC; color:#fff; border:none; border-radius:7px; font-size:13px; padding:8px 12px; cursor:pointer; }
            .btn-refresh:hover { background:#3e3d99; }
            .table-responsive { overflow:auto; }

            @media (max-width: 768px) {
                .toolbar { align-items:stretch; }
                .search-wrap, .toolbar select, .btn-refresh { max-width:none; width:100%; }
                .pagination-bar { align-items:flex-start; }
            }
        </style>

        <div class="validation-shell">
            <div class="page-header">
                <h1><i class="fas fa-check-double" style="color:#4B49AC"></i>Gestion des validations</h1>
            </div>

            <div class="toolbar">
                <label style="font-size:13px;font-weight:600;color:var(--dt-page-text);margin:0">Filtrer :</label>
                <select id="filter-statut" onchange="loadDemandes()">
                    <option value="">Tous les statuts</option>
                    <option value="EN_ATTENTE">En attente</option>
                    <option value="VALIDE">Valide</option>
                    <option value="REJETE">Rejete</option>
                </select>
                <div class="search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search-input" placeholder="Rechercher nom, email, Ndeg BL..." oninput="renderPage(1)">
                </div>
                <button class="btn-refresh" onclick="loadDemandes()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th><th>Nom</th><th>Prenom</th><th>Email</th>
                                <th>Ndeg BL</th><th>Maison de transit</th><th>Statut</th><th>Motif rejet</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="demandes-tbody">
                            @forelse ($initialDemandesPage as $demande)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($demande['createdAt'])->format('d/m/Y H:i') }}</td>
                                    <td>{{ $demande['nom'] ?: '-' }}</td>
                                    <td>{{ $demande['prenom'] ?: '-' }}</td>
                                    <td>{{ $demande['email'] ?: '-' }}</td>
                                    <td>{{ $demande['bl'] ?: '-' }}</td>
                                    <td>{{ $demande['maisonTransit'] ?: '-' }}</td>
                                    <td>
                                        @php
                                            $badgeClass = match ($demande['statut']) {
                                                'VALIDE' => 'badge-valide',
                                                'REJETE' => 'badge-rejete',
                                                default => 'badge-en_attente',
                                            };
                                            $badgeLabel = match ($demande['statut']) {
                                                'VALIDE' => 'Valide',
                                                'REJETE' => 'Rejete',
                                                default => 'En attente',
                                            };
                                        @endphp
                                        <span class="badge-status {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    </td>
                                    <td style="max-width:180px;word-break:break-word">{{ $demande['motifRejet'] ?: '-' }}</td>
                                    <td style="white-space:nowrap">
                                        @if ($demande['statut'] === 'EN_ATTENTE')
                                            <button class="btn-valider" onclick="valider({{ $demande['id'] }})"><i class="fas fa-check"></i> Valider</button>
                                            <button class="btn-rejeter" onclick="openRejectModal({{ $demande['id'] }})"><i class="fas fa-times"></i> Rejeter</button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="empty-state"><i class="fas fa-inbox fa-2x mb-3" style="display:block;color:#ccc"></i>Aucune demande trouvee.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination-bar" id="pagination-bar" style="{{ $initialDemandesCollection->count() > 10 ? 'display:flex' : 'display:none' }}">
                    <span id="pagination-info">
                        @if ($initialDemandesCollection->isNotEmpty())
                            1-{{ min(10, $initialDemandesCollection->count()) }} sur {{ $initialDemandesCollection->count() }}
                        @else
                            0 sur 0
                        @endif
                    </span>
                    <div class="pagination-pages" id="pagination-pages">
                        @if ($initialDemandesCollection->count() > 1)
                            <button class="page-btn" onclick="renderPage(0)" disabled><i class="fas fa-chevron-left"></i></button>
                            <button class="page-btn active" onclick="renderPage(1)">1</button>
                            @if ($initialDemandesCollection->count() > 10)
                                <button class="page-btn" onclick="renderPage(2)">2</button>
                            @endif
                            <button class="page-btn" onclick="renderPage(2)" {{ $initialDemandesCollection->count() <= 10 ? 'disabled' : '' }}><i class="fas fa-chevron-right"></i></button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="reject-modal">
            <div class="modal-box">
                <h5><i class="fas fa-times-circle"></i> Motif du rejet</h5>
                <p style="font-size:13px;color:var(--dt-muted-text);margin-bottom:10px">Veuillez preciser le motif du rejet :</p>
                <textarea id="motif-input" placeholder="Saisissez le motif du rejet..."></textarea>
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeRejectModal()">Annuler</button>
                    <button class="btn-confirm-reject" onclick="confirmReject()">Confirmer le rejet</button>
                </div>
            </div>
        </div>

        <script>
            var validationsPageSize = 10;
            var validationsAllData = @json($initialDemandesCollection->values());
            var validationsRejectTargetId = null;
            var validationsCurrentPage = 1;

            var validationsSwalTheme = {
                background: getComputedStyle(document.documentElement).getPropertyValue('--dt-panel-bg').trim() || '#0f172a',
                color: getComputedStyle(document.documentElement).getPropertyValue('--dt-page-text').trim() || '#e5eefb',
                confirmButtonColor: '#4B49AC',
                cancelButtonColor: '#64748b',
            };

            function showError(message) {
                return Swal.fire({
                    ...validationsSwalTheme,
                    icon: 'error',
                    title: 'Erreur',
                    text: message,
                });
            }

            function showSuccess(message) {
                return Swal.fire({
                    ...validationsSwalTheme,
                    icon: 'success',
                    title: 'Succes',
                    text: message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            function csrfHeaders(extra = {}) {
                return {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    ...extra,
                };
            }

            function fmtDate(iso) {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
            }

            function badgeHtml(statut) {
                const map = {
                    'EN_ATTENTE':['badge-en_attente','En attente'],
                    'VALIDE':['badge-valide','Valide'],
                    'REJETE':['badge-rejete','Rejete']
                };
                const [cls, label] = map[statut] || ['badge-en_attente', statut];
                return `<span class="badge-status ${cls}">${label}</span>`;
            }

            function filtered() {
                const q = (document.getElementById('search-input').value || '').toLowerCase().trim();
                if (!q) return validationsAllData;
                return validationsAllData.filter(r =>
                    (r.nom || '').toLowerCase().includes(q) ||
                    (r.prenom || '').toLowerCase().includes(q) ||
                    (r.email || '').toLowerCase().includes(q) ||
                    (r.bl || '').toLowerCase().includes(q) ||
                    (r.maisonTransit || '').toLowerCase().includes(q)
                );
            }

            function renderPage(page) {
                validationsCurrentPage = page;
                const rows = filtered();
                const total = rows.length;
                const pages = Math.max(1, Math.ceil(total / validationsPageSize));
                if (validationsCurrentPage > pages) validationsCurrentPage = pages;
                const start = (validationsCurrentPage - 1) * validationsPageSize;
                const slice = rows.slice(start, start + validationsPageSize);
                const tbody = document.getElementById('demandes-tbody');

                if (total === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="empty-state"><i class="fas fa-inbox fa-2x mb-3" style="display:block;color:#ccc"></i>Aucune demande trouvee.</td></tr>';
                } else {
                    tbody.innerHTML = slice.map(r => {
                        const isPending = r.statut === 'EN_ATTENTE';
                        const actions = isPending
                            ? `<button class="btn-valider" onclick="valider(${r.id})"><i class="fas fa-check"></i> Valider</button><button class="btn-rejeter" onclick="openRejectModal(${r.id})"><i class="fas fa-times"></i> Rejeter</button>`
                            : '-';

                        return `<tr><td>${fmtDate(r.createdAt)}</td><td>${r.nom || '-'}</td><td>${r.prenom || '-'}</td><td>${r.email || '-'}</td><td>${r.bl || '-'}</td><td>${r.maisonTransit || '-'}</td><td>${badgeHtml(r.statut)}</td><td style="max-width:180px;word-break:break-word">${r.motifRejet || '-'}</td><td style="white-space:nowrap">${actions}</td></tr>`;
                    }).join('');
                }

                const bar = document.getElementById('pagination-bar');
                bar.style.display = total > validationsPageSize ? 'flex' : 'none';
                document.getElementById('pagination-info').textContent = `${start + 1}-${Math.min(start + validationsPageSize, total)} sur ${total}`;

                let pagesHtml = `<button class="page-btn" onclick="renderPage(${validationsCurrentPage - 1})" ${validationsCurrentPage <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
                for (let p = 1; p <= pages; p++) {
                    if (pages <= 7 || p === 1 || p === pages || Math.abs(p - validationsCurrentPage) <= 1) {
                        pagesHtml += `<button class="page-btn ${p === validationsCurrentPage ? 'active' : ''}" onclick="renderPage(${p})">${p}</button>`;
                    } else if (Math.abs(p - validationsCurrentPage) === 2) {
                        pagesHtml += '<span style="padding:4px 6px;color:#aaa">...</span>';
                    }
                }
                pagesHtml += `<button class="page-btn" onclick="renderPage(${validationsCurrentPage + 1})" ${validationsCurrentPage >= pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
                document.getElementById('pagination-pages').innerHTML = pagesHtml;
            }

            async function loadDemandes() {
                const statut = document.getElementById('filter-statut').value;
                const url = '/facturation/api/rattachements' + (statut ? '?statut=' + encodeURIComponent(statut) : '');
                document.getElementById('demandes-tbody').innerHTML =
                    '<tr><td colspan="9" class="empty-state"><i class="fas fa-spinner fa-spin fa-2x mb-3" style="display:block;color:#ccc"></i>Chargement...</td></tr>';
                try {
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });
                    if (!res.ok) {
                        throw new Error(`HTTP ${res.status}`);
                    }
                    const data = await res.json();
                    validationsAllData = data.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
                    renderPage(1);
                } catch (e) {
                    document.getElementById('demandes-tbody').innerHTML =
                        '<tr><td colspan="9" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement.</td></tr>';
                }
            }

            async function valider(id) {
                const result = await Swal.fire({
                    ...validationsSwalTheme,
                    icon: 'question',
                    title: 'Confirmer la validation',
                    text: 'Voulez-vous confirmer la validation de ce dossier ?',
                    showCancelButton: true,
                    confirmButtonText: 'Valider',
                    cancelButtonText: 'Annuler',
                });
                if (!result.isConfirmed) return;
                try {
                    const res = await fetch(`/facturation/api/rattachements/${id}/valider`, {
                        method: 'PATCH',
                        headers: csrfHeaders(),
                    });
                    if (res.ok) {
                        await showSuccess('La demande a ete validee.');
                        loadDemandes();
                    } else {
                        showError('Erreur lors de la validation.');
                    }
                } catch (e) {
                    showError('Erreur de connexion.');
                }
            }

            function openRejectModal(id) {
                validationsRejectTargetId = id;
                document.getElementById('motif-input').value = '';
                document.getElementById('reject-modal').classList.add('open');
                document.getElementById('motif-input').focus();
            }

            function closeRejectModal() {
                validationsRejectTargetId = null;
                document.getElementById('reject-modal').classList.remove('open');
            }

            async function confirmReject() {
                const motif = document.getElementById('motif-input').value.trim();
                if (!motif) {
                    showError('Veuillez saisir un motif de rejet.');
                    return;
                }
                try {
                    const res = await fetch(`/facturation/api/rattachements/${validationsRejectTargetId}/rejeter`, {
                        method: 'PATCH',
                        headers: csrfHeaders({ 'Content-Type':'application/json' }),
                        body: JSON.stringify({ motif })
                    });
                    if (res.ok) {
                        closeRejectModal();
                        await showSuccess('La demande a ete rejetee.');
                        loadDemandes();
                    } else {
                        showError('Erreur lors du rejet.');
                    }
                } catch (e) {
                    showError('Erreur de connexion.');
                }
            }

            function initValidationsPage() {
                const tbody = document.getElementById('demandes-tbody');
                const rejectModal = document.getElementById('reject-modal');

                if (!tbody || !rejectModal) {
                    return;
                }

                if (rejectModal.dataset.bound !== 'true') {
                    rejectModal.addEventListener('click', function (e) {
                        if (e.target === this) closeRejectModal();
                    });
                    rejectModal.dataset.bound = 'true';
                }

                renderPage(1);
            }

            initValidationsPage();
            document.addEventListener('livewire:navigated', initValidationsPage);
        </script>
    </div>
</x-layouts::app>
