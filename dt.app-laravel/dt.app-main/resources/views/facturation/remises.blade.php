<x-layouts::app :title="__('Gestion de remises')">
    @php
        $roleName = auth()->user()?->role?->name;
        $isFacturation = $roleName === 'FACTURATION';
        $isDirection = in_array($roleName, ['DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION'], true);
        $isAdmin = in_array($roleName, ['ADMIN', 'SUPER_U'], true);
        $initialRemisesCollection = collect($initialRemises ?? []);
        $initialRemisesPage = $initialRemisesCollection->take(10);
        $directionBadge = match ($roleName) {
            'DIRECTION_GENERALE' => 'Direction Generale',
            'DIRECTION_FINANCIERE' => 'Direction Financiere',
            'DIRECTION_EXPLOITATION' => 'Direction Exploitation',
            default => 'Direction',
        };
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .toolbar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:18px; }
            .toolbar select { border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:7px; padding:7px 12px; font-size:13px; }
            .search-wrap { position:relative; flex:1; min-width:200px; max-width:340px; }
            .search-wrap input { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:7px; padding:7px 12px 7px 34px; font-size:13px; outline:none; }
            .search-wrap input:focus { border-color:#4B49AC; box-shadow:0 0 0 4px var(--dt-ring); }
            .search-wrap .search-icon { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--dt-soft-text); font-size:13px; }
            .badge-status { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.4px; white-space:nowrap; }
            .badge-en_attente_facturation { background:#fff3cd; color:#856404; }
            .badge-en_attente_direction { background:#cce5ff; color:#004085; }
            .badge-valide { background:#d4edda; color:#155724; }
            .badge-rejete { background:#f8d7da; color:#721c24; }
            .btn-valider { background:#28a745; color:#fff; border:none; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; }
            .btn-valider:hover { background:#218838; }
            .btn-rejeter { background:#dc3545; color:#fff; border:none; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer; margin-left:4px; }
            .btn-rejeter:hover { background:#c82333; }
            .btn-refresh { background:#4B49AC; color:#fff; border:none; border-radius:7px; font-size:13px; padding:8px 12px; cursor:pointer; }
            .btn-refresh:hover { background:#3e3d99; }
            .table-card { background:var(--dt-panel-bg); border:1px solid var(--dt-border); border-radius:12px; box-shadow:var(--dt-shadow); overflow:hidden; }
            .table-card table { margin:0; width:100%; border-collapse:collapse; }
            .table-card thead th { background:var(--dt-table-head-bg); font-size:12px; font-weight:700; color:var(--dt-page-text); border-bottom:2px solid var(--dt-border); white-space:nowrap; text-align:left; padding:14px 16px; }
            .table-card tbody td { font-size:13px; vertical-align:middle; padding:14px 16px; border-top:1px solid var(--dt-border); color:var(--dt-page-text); }
            .table-responsive { overflow:auto; }
            .empty-state { text-align:center; padding:48px; color:var(--dt-soft-text); }
            .modal-overlay { display:none; position:fixed; inset:0; background:var(--dt-overlay); z-index:9000; align-items:center; justify-content:center; }
            .modal-overlay.open { display:flex; }
            .modal-box { background:var(--dt-panel-bg); color:var(--dt-page-text); border:1px solid var(--dt-border); border-radius:12px; padding:28px; width:100%; max-width:440px; box-shadow:var(--dt-shadow); }
            .modal-box h5 { font-weight:700; margin-bottom:14px; }
            .modal-box textarea, .modal-box input[type=number] { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:10px 12px; font-size:13px; }
            .modal-box textarea { resize:vertical; min-height:90px; }
            .modal-box input[type=number] { margin-top:8px; }
            .modal-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:16px; }
            .btn-cancel { background:#6c757d; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-size:13px; cursor:pointer; }
            .btn-confirm { background:#28a745; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer; }
            .btn-confirm-reject { background:#dc3545; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer; }
            .pagination-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding:12px 16px 4px; font-size:13px; color:var(--dt-muted-text); }
            .pagination-pages { display:flex; gap:4px; align-items:center; }
            .page-btn { border:1px solid var(--dt-border); background:var(--dt-panel-alt-bg); border-radius:6px; padding:4px 10px; font-size:13px; cursor:pointer; color:var(--dt-page-text); }
            .page-btn:hover { background:var(--dt-table-head-bg); border-color:#4B49AC; color:#818cf8; }
            .page-btn.active { background:#4B49AC; color:#fff; border-color:#4B49AC; }
            .page-btn:disabled { opacity:.4; cursor:default; }
            .role-badge { display:inline-block; background:var(--dt-table-head-bg); color:#818cf8; border:1px solid var(--dt-border); border-radius:20px; padding:2px 10px; font-size:11px; font-weight:700; margin-left:8px; }
            .page-header h1 { display:flex; align-items:center; gap:10px; margin:0; flex-wrap:wrap; color:var(--dt-page-text); }

            @media (max-width: 768px) {
                .toolbar { align-items:stretch; }
                .search-wrap, .toolbar select, .btn-refresh { max-width:none; width:100%; }
            }
        </style>

        <div class="page-header">
            <h1>
                <i class="fas fa-percent" style="color:#4B49AC"></i>Gestion de remises
                @if ($isFacturation)
                    <span class="role-badge">Facturation</span>
                @endif
                @if ($isDirection)
                    <span class="role-badge">{{ $directionBadge }}</span>
                @endif
            </h1>
        </div>

        @if ($isFacturation)
            <div class="alert dt-theme-info" style="border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px">
                <i class="fas fa-info-circle"></i>
                Vous voyez les demandes en attente de votre validation. En validant, la demande sera transmise a la Direction Generale.
            </div>
        @endif

        @if ($isDirection)
            <div class="alert dt-theme-info" style="border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px">
                <i class="fas fa-info-circle"></i>
                Vous voyez les demandes validees par la Facturation, en attente de validation direction. Indiquez un pourcentage de remise lors de la validation finale.
            </div>
        @endif

        <div class="toolbar">
            <label style="font-size:13px;font-weight:600;color:var(--dt-page-text);margin:0">Filtrer :</label>
            <select id="filter-statut" onchange="loadRemises()">
                <option value="" @selected(($initialRemiseStatut ?? '') === '')>Tous les statuts</option>
                <option value="EN_ATTENTE_VALIDATION_FACTURATION" @selected(($initialRemiseStatut ?? '') === 'EN_ATTENTE_VALIDATION_FACTURATION')>En attente (Facturation)</option>
                <option value="EN_ATTENTE_VALIDATION_DIRECTION" @selected(($initialRemiseStatut ?? '') === 'EN_ATTENTE_VALIDATION_DIRECTION')>En attente (Direction)</option>
                <option value="VALIDE">Valide</option>
                <option value="REJETE">Rejete</option>
            </select>
            <div class="search-wrap">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" placeholder="Rechercher nom, email, Ndeg BL..." oninput="renderPage(1)">
            </div>
            <button class="btn-refresh" onclick="loadRemises()">
                <i class="fas fa-sync-alt"></i> Actualiser
            </button>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th><th>Nom</th><th>Prenom</th><th>Email</th>
                            <th>Ndeg BL</th><th>Maison de transit</th><th>Statut</th><th>Motif rejet / %</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="remises-tbody">
                        @forelse ($initialRemisesPage as $remise)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($remise['createdAt'])->format('d/m/Y H:i') }}</td>
                                <td>{{ $remise['nom'] ?: '-' }}</td>
                                <td>{{ $remise['prenom'] ?: '-' }}</td>
                                <td>{{ $remise['email'] ?: '-' }}</td>
                                <td>{{ $remise['bl'] ?: '-' }}</td>
                                <td>{{ $remise['maisonTransit'] ?: '-' }}</td>
                                <td>
                                    @php
                                        [$badgeClass, $badgeLabel] = match ($remise['statut']) {
                                            'EN_ATTENTE_VALIDATION_FACTURATION' => ['badge-en_attente_facturation', 'En attente (Facturation)'],
                                            'EN_ATTENTE_VALIDATION_DIRECTION' => ['badge-en_attente_direction', 'En attente (Direction)'],
                                            'VALIDE' => ['badge-valide', 'Valide'],
                                            'REJETE' => ['badge-rejete', 'Rejete'],
                                            default => ['badge-en_attente_facturation', $remise['statut']],
                                        };
                                    @endphp
                                    <span class="badge-status {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                </td>
                                <td style="max-width:180px;word-break:break-word">
                                    @if ($remise['statut'] === 'REJETE' && $remise['motifRejet'])
                                        <span style="color:#dc3545">{{ $remise['motifRejet'] }}</span>
                                    @elseif ($remise['pourcentage'] !== null)
                                        <strong style="color:#4B49AC">{{ $remise['pourcentage'] }} %</strong>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    @if (($isFacturation || $isAdmin) && $remise['statut'] === 'EN_ATTENTE_VALIDATION_FACTURATION')
                                        <button class="btn-valider" onclick="validerFacturation({{ $remise['id'] }})"><i class="fas fa-check"></i> Valider</button>
                                        <button class="btn-rejeter" onclick="openRejectModal({{ $remise['id'] }}, '{{ $remise['statut'] }}')"><i class="fas fa-times"></i> Rejeter</button>
                                    @elseif (($isDirection || $isAdmin) && $remise['statut'] === 'EN_ATTENTE_VALIDATION_DIRECTION')
                                        <button class="btn-valider" onclick="openDirectionModal({{ $remise['id'] }})"><i class="fas fa-check-double"></i> Valider avec %</button>
                                        <button class="btn-rejeter" onclick="openRejectModal({{ $remise['id'] }}, '{{ $remise['statut'] }}')"><i class="fas fa-times"></i> Rejeter</button>
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
            <div class="pagination-bar" id="pagination-bar" style="{{ $initialRemisesCollection->count() > 10 ? 'display:flex' : 'display:none' }}">
                <span id="pagination-info">
                    @if ($initialRemisesCollection->isNotEmpty())
                        1-{{ min(10, $initialRemisesCollection->count()) }} sur {{ $initialRemisesCollection->count() }}
                    @else
                        0 sur 0
                    @endif
                </span>
                <div class="pagination-pages" id="pagination-pages">
                    @if ($initialRemisesCollection->count() > 1)
                        <button class="page-btn" onclick="renderPage(0)" disabled><i class="fas fa-chevron-left"></i></button>
                        <button class="page-btn active" onclick="renderPage(1)">1</button>
                        @if ($initialRemisesCollection->count() > 10)
                            <button class="page-btn" onclick="renderPage(2)">2</button>
                        @endif
                        <button class="page-btn" onclick="renderPage(2)" {{ $initialRemisesCollection->count() <= 10 ? 'disabled' : '' }}><i class="fas fa-chevron-right"></i></button>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="reject-modal">
            <div class="modal-box">
                <h5 style="color:#dc3545"><i class="fas fa-times-circle"></i> Motif du rejet</h5>
                <p style="font-size:13px;color:var(--dt-muted-text);margin-bottom:10px">Veuillez preciser le motif du rejet :</p>
                <select id="motif-select" style="display:none;width:100%;border:1px solid var(--dt-input-border);background:var(--dt-input-bg);color:var(--dt-page-text);border-radius:8px;padding:10px 12px;font-size:13px;outline:none">
                    <option value="">-- Choisir un motif --</option>
                    <option value="Dossier incomplet">Dossier incomplet</option>
                    <option value="Non paiement de la facture d'acconage">Non paiement de la facture d'acconage</option>
                    <option value="Frais de stationnement inferieurs au seuil requis">Frais de stationnement inferieurs au seuil requis</option>
                    <option value="Dossier en cours de traitement">Dossier en cours de traitement</option>
                    <option value="Dossier deja traite">Dossier deja traite</option>
                </select>
                <textarea id="motif-input" style="display:none" placeholder="Saisissez le motif du rejet..."></textarea>
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeModal('reject-modal')">Annuler</button>
                    <button class="btn-confirm-reject" onclick="confirmReject()">Confirmer le rejet</button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="direction-modal">
            <div class="modal-box">
                <h5 style="color:#28a745"><i class="fas fa-check-circle"></i> Validation Direction</h5>
                <p style="font-size:13px;color:var(--dt-muted-text);margin-bottom:10px">Saisissez le pourcentage de remise accorde :</p>
                <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
                    <input type="number" id="pourcentage-input" min="0" max="100" step="0.01" placeholder="Ex: 15" style="max-width:140px">
                    <span style="font-size:16px;font-weight:700;color:#818cf8">%</span>
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeModal('direction-modal')">Annuler</button>
                    <button class="btn-confirm" onclick="confirmDirectionValidation()">Valider avec ce taux</button>
                </div>
            </div>
        </div>

        <script>
            const PAGE_SIZE = 10;
            let allData = @json($initialRemisesCollection->values());
            let rejectTargetId = null;
            let directionTargetId = null;
            let currentPage = 1;

            const isFacturation = @json($isFacturation);
            const isDirection = @json($isDirection);
            const isAdmin = @json($isAdmin);

            const swalTheme = {
                background: getComputedStyle(document.documentElement).getPropertyValue('--dt-panel-bg').trim() || '#0f172a',
                color: getComputedStyle(document.documentElement).getPropertyValue('--dt-page-text').trim() || '#e5eefb',
                confirmButtonColor: '#4B49AC',
                cancelButtonColor: '#64748b',
            };

            function showError(message) {
                return Swal.fire({
                    ...swalTheme,
                    icon: 'error',
                    title: 'Erreur',
                    text: message,
                });
            }

            function showSuccess(message) {
                return Swal.fire({
                    ...swalTheme,
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

            function applyDefaultRemiseFilter() {
                const sel = document.getElementById('filter-statut');
                if (!sel) {
                    return;
                }
                if (isFacturation && !isAdmin) {
                    sel.value = 'EN_ATTENTE_VALIDATION_FACTURATION';
                } else if (isDirection && !isAdmin) {
                    sel.value = 'EN_ATTENTE_VALIDATION_DIRECTION';
                }
            }

            function fmtDate(iso) {
                if (!iso) return '-';
                const d = new Date(iso);
                return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
            }

            function badgeHtml(statut) {
                const map = {
                    'EN_ATTENTE_VALIDATION_FACTURATION': ['badge-en_attente_facturation', 'En attente (Facturation)'],
                    'EN_ATTENTE_VALIDATION_DIRECTION': ['badge-en_attente_direction', 'En attente (Direction)'],
                    'VALIDE': ['badge-valide', 'Valide'],
                    'REJETE': ['badge-rejete', 'Rejete']
                };
                const [cls, label] = map[statut] || ['badge-en_attente_facturation', statut];
                return `<span class="badge-status ${cls}">${label}</span>`;
            }

            function actionsHtml(r) {
                if ((isFacturation || isAdmin) && r.statut === 'EN_ATTENTE_VALIDATION_FACTURATION') {
                    return `<button class="btn-valider" onclick="validerFacturation(${r.id})"><i class="fas fa-check"></i> Valider</button>`
                        + `<button class="btn-rejeter" onclick="openRejectModal(${r.id}, '${r.statut}')"><i class="fas fa-times"></i> Rejeter</button>`;
                }
                if ((isDirection || isAdmin) && r.statut === 'EN_ATTENTE_VALIDATION_DIRECTION') {
                    return `<button class="btn-valider" onclick="openDirectionModal(${r.id})"><i class="fas fa-check-double"></i> Valider avec %</button>`
                        + `<button class="btn-rejeter" onclick="openRejectModal(${r.id}, '${r.statut}')"><i class="fas fa-times"></i> Rejeter</button>`;
                }
                return '-';
            }

            function infoCol(r) {
                if (r.statut === 'REJETE' && r.motifRejet) return `<span style="color:#dc3545">${r.motifRejet}</span>`;
                if (r.pourcentage != null) return `<strong style="color:#4B49AC">${r.pourcentage} %</strong>`;
                return '-';
            }

            function filtered() {
                const q = (document.getElementById('search-input').value || '').toLowerCase().trim();
                if (!q) return allData;
                return allData.filter(r =>
                    (r.nom || '').toLowerCase().includes(q) ||
                    (r.prenom || '').toLowerCase().includes(q) ||
                    (r.email || '').toLowerCase().includes(q) ||
                    (r.bl || '').toLowerCase().includes(q) ||
                    (r.maisonTransit || '').toLowerCase().includes(q)
                );
            }

            function renderPage(page) {
                currentPage = page;
                const rows = filtered();
                const total = rows.length;
                const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
                if (currentPage > pages) currentPage = pages;
                const start = (currentPage - 1) * PAGE_SIZE;
                const slice = rows.slice(start, start + PAGE_SIZE);
                const tbody = document.getElementById('remises-tbody');

                if (total === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="empty-state"><i class="fas fa-inbox fa-2x mb-3" style="display:block;color:#ccc"></i>Aucune demande trouvee.</td></tr>';
                } else {
                    tbody.innerHTML = slice.map(r =>
                        `<tr><td>${fmtDate(r.createdAt)}</td><td>${r.nom || '-'}</td><td>${r.prenom || '-'}</td><td>${r.email || '-'}</td><td>${r.bl || '-'}</td><td>${r.maisonTransit || '-'}</td><td>${badgeHtml(r.statut)}</td><td style="max-width:180px;word-break:break-word">${infoCol(r)}</td><td style="white-space:nowrap">${actionsHtml(r)}</td></tr>`
                    ).join('');
                }

                const bar = document.getElementById('pagination-bar');
                bar.style.display = total > PAGE_SIZE ? 'flex' : 'none';
                document.getElementById('pagination-info').textContent = `${start + 1}-${Math.min(start + PAGE_SIZE, total)} sur ${total}`;

                let ph = `<button class="page-btn" onclick="renderPage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;
                for (let p = 1; p <= pages; p++) {
                    if (pages <= 7 || p === 1 || p === pages || Math.abs(p - currentPage) <= 1) {
                        ph += `<button class="page-btn ${p === currentPage ? 'active' : ''}" onclick="renderPage(${p})">${p}</button>`;
                    } else if (Math.abs(p - currentPage) === 2) {
                        ph += '<span style="padding:4px 6px;color:#aaa">...</span>';
                    }
                }
                ph += `<button class="page-btn" onclick="renderPage(${currentPage + 1})" ${currentPage >= pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;
                document.getElementById('pagination-pages').innerHTML = ph;
            }

            async function loadRemises() {
                const statut = document.getElementById('filter-statut').value;
                const url = '/facturation/api/remises' + (statut ? '?statut=' + encodeURIComponent(statut) : '');
                try {
                    const res = await fetch(url, { cache: 'no-store' });
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    const data = await res.json();
                    allData = data.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
                    renderPage(1);
                } catch (e) {
                    document.getElementById('remises-tbody').innerHTML =
                        '<tr><td colspan="9" class="empty-state text-danger"><i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement.</td></tr>';
                }
            }

            async function validerFacturation(id) {
                const result = await Swal.fire({
                    ...swalTheme,
                    icon: 'question',
                    title: 'Confirmer la validation',
                    text: 'Voulez-vous confirmer la validation de ce dossier ? La demande sera transmise a la Direction Generale.',
                    showCancelButton: true,
                    confirmButtonText: 'Valider',
                    cancelButtonText: 'Annuler',
                });
                if (!result.isConfirmed) return;
                try {
                    const res = await fetch(`/facturation/api/remises/${id}/valider`, {
                        method: 'PATCH',
                        headers: csrfHeaders({ 'Content-Type':'application/json' }),
                        body: '{}'
                    });
                    if (res.ok) {
                        await showSuccess('La demande a ete transmise a la Direction Generale.');
                        loadRemises();
                    } else {
                        showError('Erreur lors de la validation.');
                    }
                } catch (e) {
                    showError('Erreur de connexion.');
                }
            }

            function openDirectionModal(id) {
                directionTargetId = id;
                document.getElementById('pourcentage-input').value = '';
                document.getElementById('direction-modal').classList.add('open');
                document.getElementById('pourcentage-input').focus();
            }

            async function confirmDirectionValidation() {
                const pct = document.getElementById('pourcentage-input').value.trim();
                if (!pct || isNaN(pct) || Number(pct) < 0 || Number(pct) > 100) {
                    showError('Veuillez saisir un pourcentage valide entre 0 et 100.');
                    return;
                }
                try {
                    const res = await fetch(`/facturation/api/remises/${directionTargetId}/valider`, {
                        method: 'PATCH',
                        headers: csrfHeaders({ 'Content-Type':'application/json' }),
                        body: JSON.stringify({ pourcentage: pct })
                    });
                    if (res.ok) {
                        closeModal('direction-modal');
                        await showSuccess('La demande a ete validee avec succes.');
                        loadRemises();
                    } else {
                        showError('Erreur lors de la validation.');
                    }
                } catch (e) {
                    showError('Erreur de connexion.');
                }
            }

            function openRejectModal(id, statut) {
                rejectTargetId = id;
                const select = document.getElementById('motif-select');
                const textarea = document.getElementById('motif-input');
                const useSelect = statut === 'EN_ATTENTE_VALIDATION_FACTURATION';
                select.style.display = useSelect ? 'block' : 'none';
                textarea.style.display = useSelect ? 'none' : 'block';
                select.value = '';
                textarea.value = '';
                document.getElementById('reject-modal').classList.add('open');
                (useSelect ? select : textarea).focus();
            }

            async function confirmReject() {
                const select = document.getElementById('motif-select');
                const textarea = document.getElementById('motif-input');
                const motif = select.style.display !== 'none' ? select.value.trim() : textarea.value.trim();
                if (!motif) {
                    showError('Veuillez preciser le motif du rejet.');
                    return;
                }
                try {
                    const res = await fetch(`/facturation/api/remises/${rejectTargetId}/rejeter`, {
                        method: 'PATCH',
                        headers: csrfHeaders({ 'Content-Type':'application/json' }),
                        body: JSON.stringify({ motif })
                    });
                    if (res.ok) {
                        closeModal('reject-modal');
                        await showSuccess('La demande a ete rejetee.');
                        loadRemises();
                    } else {
                        showError('Erreur lors du rejet.');
                    }
                } catch (e) {
                    showError('Erreur de connexion.');
                }
            }

            function closeModal(id) {
                document.getElementById(id).classList.remove('open');
                rejectTargetId = null;
                directionTargetId = null;
            }

            function initRemisesPage() {
                const tbody = document.getElementById('remises-tbody');

                if (!tbody) {
                    return;
                }

                applyDefaultRemiseFilter();

                document.querySelectorAll('.modal-overlay').forEach(m => {
                    if (m.dataset.bound === 'true') {
                        return;
                    }

                    m.addEventListener('click', e => {
                        if (e.target === m) closeModal(m.id);
                    });
                    m.dataset.bound = 'true';
                });

                loadRemises();
            }

            initRemisesPage();
            document.addEventListener('livewire:navigated', initRemisesPage);
        </script>
    </div>
</x-layouts::app>
