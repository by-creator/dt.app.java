<x-layouts::app :title="__('Administration')">
    <div class="admin-page flex h-full w-full flex-1 flex-col gap-6 pb-8">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .admin-page { overflow: visible; min-height: max-content; }
            .admin-page .page-header { text-align:center; margin-bottom:20px; }
            .admin-page .page-header h1 { margin:0; display:flex; align-items:center; justify-content:center; gap:10px; color:var(--dt-page-text); }
            .admin-page .module-tabs { display:flex; justify-content:center; gap:6px; flex-wrap:nowrap; overflow-x:auto; overflow-y:hidden; border-bottom:2px solid var(--dt-border); margin-bottom:22px; background:var(--dt-panel-bg); padding:0 8px 6px; scrollbar-width:thin; box-shadow:var(--dt-shadow); }
            .admin-page .module-tab { flex:0 0 auto; border:none; background:transparent; color:var(--dt-muted-text); font-size:14px; font-weight:600; padding:12px 16px; border-bottom:3px solid transparent; margin-bottom:-2px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:color .2s; }
            .admin-page .module-tab:hover { color:#4B49AC; }
            .admin-page .module-tab.active { color:#4B49AC; border-bottom-color:#4B49AC; }
            .admin-page .module-pane { display:none; opacity:0; transform:translateY(10px); }
            .admin-page .module-pane.active { display:block; animation:tabPaneFade .25s ease forwards; }
            @keyframes tabPaneFade { from { opacity:0; transform:translateY(10px);} to { opacity:1; transform:translateY(0);} }
            .admin-page .simple-card { background:var(--dt-panel-bg); color:var(--dt-page-text); border:1px solid var(--dt-border); border-radius:12px; box-shadow:var(--dt-shadow); padding:24px; max-width:1100px; margin:0 auto; }
            .admin-page .unify-section-title { font-size:20px; font-weight:700; color:var(--dt-page-text); display:flex; align-items:center; gap:10px; margin-bottom:12px; }
            .admin-page .muted { color:var(--dt-muted-text); }
            .admin-page .status-box { margin:12px auto; padding:10px 14px; border-radius:8px; max-width:1100px; }
            .admin-page .status-success { background:var(--dt-success-bg); color:var(--dt-success-text); border:1px solid var(--dt-success-border); }
            .admin-page .status-error { background:var(--dt-danger-bg); color:var(--dt-danger-text); border:1px solid var(--dt-danger-border); }
            .admin-page .status-warning { background:var(--dt-warning-bg); color:var(--dt-warning-text); border:1px solid var(--dt-warning-border); }
            .admin-page .admin-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
            .admin-page .admin-card { border:1px solid var(--dt-border); border-radius:10px; padding:16px; background:var(--dt-panel-alt-bg); }
            .admin-page .split-grid { display:grid; grid-template-columns:minmax(240px, 1fr) minmax(0, 2fr); gap:16px; align-items:start; }
            .admin-page .form-grid-layout { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px 24px; align-items:start; }
            .admin-page .form-group-custom label { font-size:13px; font-weight:600; color:var(--dt-page-text); display:block; margin-bottom:8px; }
            .admin-page .form-control-custom { width:100%; border:1px solid var(--dt-input-border); background:var(--dt-input-bg); color:var(--dt-page-text); border-radius:8px; padding:9px 13px; font-size:13px; min-height:42px; box-sizing:border-box; min-width:0; }
            .admin-page .form-control-custom:focus { border-color:#4B49AC; outline:none; box-shadow:0 0 0 4px var(--dt-ring); }
            .admin-page .btn-gfa { border:none; border-radius:7px; padding:10px 16px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
            .admin-page .btn-primary-gfa { background:#4B49AC; color:#fff; }
            .admin-page .btn-light-gfa { background:#f2f2f7; color:#555; }
            .admin-page .btn-danger-gfa { background:#dc3545; color:#fff; }
            .admin-page .table-card { background:var(--dt-panel-bg); border:1px solid var(--dt-border); border-radius:12px; box-shadow:var(--dt-shadow); overflow:hidden; }
            .admin-page .table-responsive { overflow:auto; }
            .admin-page .table-unify { width:100%; border-collapse:collapse; font-size:13px; margin:0; }
            .admin-page .table-unify th { background:var(--dt-table-head-bg); font-size:12px; font-weight:700; color:var(--dt-page-text); border-bottom:2px solid var(--dt-border); white-space:nowrap; text-align:left; padding:14px 16px; }
            .admin-page .table-unify td { font-size:13px; vertical-align:middle; padding:14px 16px; border-top:1px solid var(--dt-border); text-align:left; color:var(--dt-page-text); }
            .admin-page .empty-state { text-align:center; padding:48px; color:var(--dt-soft-text); }
            .admin-page .actions { display:flex; flex-wrap:wrap; gap:8px; }
            .admin-page .stack { display:grid; gap:10px; }
            .admin-page .list-card { min-height:100%; }
            .admin-page .list-scroll { overflow-x:auto; }
            .admin-page .search-toolbar { margin:18px 0 14px; }
            .admin-page .field-span-2 { grid-column:1 / -1; }
            .admin-page .icon-btn { justify-content:center; min-width:44px; padding:10px 12px; }
            .admin-page .icon-btn i { font-size:14px; }
            .admin-page .inline-user-actions { display:flex; gap:8px; flex-wrap:nowrap; align-items:center; }
            .admin-page .password-toggle-group { position:relative; }
            .admin-page .password-toggle-group .form-control-custom { padding-right:5.6rem; }
            .admin-page .password-toggle { position:absolute; top:50%; right:12px; transform:translateY(-50%); border:none; background:transparent; color:#818cf8; font-size:12px; font-weight:700; cursor:pointer; padding:4px; }
            .admin-page .password-toggle:hover { opacity:.85; }

            @media (max-width: 768px) {
                .admin-page .admin-grid,
                .admin-page .form-grid-layout,
                .admin-page .split-grid { grid-template-columns:1fr; }
                .admin-page .actions { flex-direction:column; }
                .admin-page .inline-user-actions { flex-direction:column; }
            }
        </style>

        <div class="page-header">
            <h1><i class="fas fa-user-shield" style="color:#4B49AC"></i>Administration</h1>
        </div>

        <div class="module-tabs">
            <button type="button" class="module-tab {{ ($activeTab ?? 'admin-roles') === 'admin-roles' ? 'active' : '' }}" data-target="admin-roles"><i class="fas fa-shield-alt"></i> Roles</button>
            <button type="button" class="module-tab {{ ($activeTab ?? 'admin-roles') === 'admin-users' ? 'active' : '' }}" data-target="admin-users"><i class="fas fa-users-cog"></i> Utilisateurs</button>
        </div>

        <div id="admin-roles" class="module-pane {{ ($activeTab ?? 'admin-roles') === 'admin-roles' ? 'active' : '' }}">
            <div class="split-grid">
                <div class="simple-card">
                    <h3 class="unify-section-title"><i class="fas fa-plus-circle" style="color:#4B49AC"></i> Enregistrement role</h3>
                    <p class="muted" style="margin-bottom:16px;">Ajoutez un nouveau role dans le meme esprit que les ecrans Unify.</p>

                    <div class="admin-card">
                        <h5 style="margin-bottom:14px;color:var(--dt-page-text);">Ajouter un role</h5>
                        <form method="POST" action="{{ route('administration.roles.store') }}" class="stack">
                            @csrf
                            <input type="hidden" name="tab" value="admin-roles">
                            <div class="form-group-custom">
                                <label for="role_name">Nom du role *</label>
                                <input id="role_name" name="name" class="form-control-custom" placeholder="Ex: FACTURATION" required>
                            </div>
                            <button type="submit" class="btn-gfa btn-primary-gfa">Ajouter</button>
                        </form>
                    </div>
                </div>

                <div class="simple-card list-card">
                    <h3 class="unify-section-title"><i class="fas fa-list" style="color:#4B49AC"></i> Liste des roles</h3>
                    <p class="muted" style="margin-bottom:16px;">Consultez et mettez a jour les roles existants.</p>

                    <form method="GET" action="{{ route('administration.index') }}" class="search-toolbar">
                        <input type="hidden" name="tab" value="admin-roles">
                        <input type="search" name="role_search" value="{{ request('role_search') }}" class="form-control-custom" placeholder="Rechercher un role...">
                    </form>

                    <div class="list-scroll" style="margin-top:18px;">
                        <div class="table-card">
                            <div class="table-responsive">
                                <table class="table-unify">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Utilisateurs</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($roles as $role)
                                            <tr>
                                                <td>
                                                    <form method="POST" action="{{ route('administration.roles.update', $role) }}" class="stack js-confirm-save" data-confirm-title="Modifier ce role ?">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="tab" value="admin-roles">
                                                        <input name="name" value="{{ $role->name }}" class="form-control-custom" required>
                                                </td>
                                                <td>{{ $role->users_count }}</td>
                                                <td>
                                                        <div class="actions" style="flex-wrap:nowrap">
                                                            <button type="submit" class="btn-gfa btn-primary-gfa icon-btn" title="Modifier"><i class="fas fa-pen"></i></button>
                                                    </form>
                                                            <form method="POST" action="{{ route('administration.roles.destroy', $role) }}" class="js-confirm-delete" data-confirm-title="Supprimer ce role ?" data-confirm-text="Cette action est irreversible.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="tab" value="admin-roles">
                                                                <button type="submit" class="btn-gfa btn-danger-gfa icon-btn" title="Supprimer"><i class="fas fa-trash"></i></button>
                                                            </form>
                                                        </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="empty-state"><i class="fas fa-inbox fa-2x mb-3" style="display:block;color:#ccc"></i>Aucun role disponible.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:18px;">
                        {{ $roles->appends(['tab' => 'admin-roles', 'role_search' => request('role_search')])->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div id="admin-users" class="module-pane {{ ($activeTab ?? 'admin-roles') === 'admin-users' ? 'active' : '' }}">
            <div class="split-grid">
                <div class="simple-card">
                    <h3 class="unify-section-title"><i class="fas fa-user-plus" style="color:#4B49AC"></i> Enregistrement utilisateur</h3>
                    <p class="muted" style="margin-bottom:16px;">Associez un compte a un role des sa creation.</p>

                    <div class="admin-card">
                        <h5 style="margin-bottom:14px;color:var(--dt-page-text);">Ajouter un utilisateur</h5>
                        <form method="POST" action="{{ route('administration.users.store') }}">
                            @csrf
                            <input type="hidden" name="tab" value="admin-users">
                            <div class="stack">
                                <div class="form-group-custom">
                                    <label for="user_role">Role *</label>
                                    <select id="user_role" name="role_id" class="form-control-custom" required>
                                        @foreach ($rolesForSelect as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group-custom">
                                    <label for="user_name">Nom *</label>
                                    <input id="user_name" name="name" class="form-control-custom" required>
                                </div>
                                <div class="form-group-custom">
                                    <label for="user_email">Email *</label>
                                    <input id="user_email" name="email" type="email" class="form-control-custom" required>
                                </div>
                                <div class="form-group-custom">
                                    <label for="user_password">Mot de passe *</label>
                                    <div class="password-toggle-group">
                                        <input id="user_password" name="password" type="password" class="form-control-custom" required>
                                        <button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="user_password" aria-label="Afficher le mot de passe">Voir</button>
                                    </div>
                                </div>
                                <div class="form-group-custom">
                                    <label for="user_password_confirmation">Confirmation *</label>
                                    <div class="password-toggle-group">
                                        <input id="user_password_confirmation" name="password_confirmation" type="password" class="form-control-custom" required>
                                        <button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="user_password_confirmation" aria-label="Afficher le mot de passe">Voir</button>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top:14px;">
                                <button type="submit" class="btn-gfa btn-primary-gfa">Creer l'utilisateur</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="simple-card list-card">
                    <h3 class="unify-section-title"><i class="fas fa-users-cog" style="color:#4B49AC"></i> Liste des utilisateurs</h3>
                    <p class="muted" style="margin-bottom:16px;">Mettez a jour les comptes existants et leurs permissions.</p>

                    <form method="GET" action="{{ route('administration.index') }}" class="search-toolbar">
                        <input type="hidden" name="tab" value="admin-users">
                        <input type="search" name="user_search" value="{{ request('user_search') }}" class="form-control-custom" placeholder="Rechercher un utilisateur, email ou role...">
                    </form>

                    <div class="list-scroll">
                        <div class="table-card">
                            <div class="table-responsive">
                                <table class="table-unify" style="table-layout:fixed;width:100%">
                                    <colgroup>
                                        <col style="width:13%">
                                        <col style="width:22%">
                                        <col style="width:16%">
                                        <col style="width:18%">
                                        <col style="width:18%">
                                        <col style="width:13%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Mot de passe</th>
                                            <th>Confirmation</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($users as $user)
                                            <tr>
                                                <td>
                                                    <input form="user-update-{{ $user->id }}" name="name" value="{{ $user->name }}" class="form-control-custom" required>
                                                </td>
                                                <td>
                                                    <input form="user-update-{{ $user->id }}" name="email" type="email" value="{{ $user->email }}" class="form-control-custom" required>
                                                </td>
                                                <td>
                                                    <select form="user-update-{{ $user->id }}" name="role_id" class="form-control-custom" required>
                                                        @foreach ($rolesForSelect as $role)
                                                            <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="password-toggle-group">
                                                        <input id="user-password-{{ $user->id }}" form="user-update-{{ $user->id }}" name="password" type="password" class="form-control-custom" placeholder="Laisser vide pour conserver">
                                                        <button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="user-password-{{ $user->id }}" aria-label="Afficher le mot de passe">Voir</button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="password-toggle-group">
                                                        <input id="user-password-confirmation-{{ $user->id }}" form="user-update-{{ $user->id }}" name="password_confirmation" type="password" class="form-control-custom" placeholder="Confirmation">
                                                        <button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="user-password-confirmation-{{ $user->id }}" aria-label="Afficher le mot de passe">Voir</button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="inline-user-actions">
                                                        <form id="user-update-{{ $user->id }}" method="POST" action="{{ route('administration.users.update', $user) }}" class="js-confirm-save" data-confirm-title="Modifier cet utilisateur ?">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="tab" value="admin-users">
                                                            <button type="submit" class="btn-gfa btn-primary-gfa icon-btn" aria-label="Modifier" title="Modifier">
                                                                <i class="fas fa-pen"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('administration.users.destroy', $user) }}" class="js-confirm-delete" data-confirm-title="Supprimer cet utilisateur ?" data-confirm-text="Cette action est irreversible.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="tab" value="admin-users">
                                                            <button type="submit" class="btn-gfa btn-danger-gfa icon-btn" aria-label="Supprimer" title="Supprimer">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="empty-state"><i class="fas fa-inbox fa-2x mb-3" style="display:block;color:#ccc"></i>Aucun utilisateur disponible.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:18px;">
                        {{ $users->appends(['tab' => 'admin-users', 'user_search' => request('user_search')])->links() }}
                    </div>
                </div>
            </div>
        </div>

        <script>
            const adminTabs = document.querySelectorAll('.admin-page .module-tab');
            const adminPanes = document.querySelectorAll('.admin-page .module-pane');

            adminTabs.forEach(tab => tab.addEventListener('click', () => {
                adminTabs.forEach(t => t.classList.remove('active'));
                adminPanes.forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                const target = document.getElementById(tab.dataset.target);
                if (target) target.classList.add('active');
            }));

            function swalAdminTheme() {
                return {
                    background: getComputedStyle(document.documentElement).getPropertyValue('--dt-panel-bg').trim() || '#ffffff',
                    color: getComputedStyle(document.documentElement).getPropertyValue('--dt-page-text').trim() || '#1e293b',
                    cancelButtonColor: '#64748b',
                };
            }

            document.querySelectorAll('.js-confirm-save').forEach(form => {
                form.addEventListener('submit', async event => {
                    event.preventDefault();
                    const result = await Swal.fire({
                        ...swalAdminTheme(),
                        icon: 'question',
                        title: form.dataset.confirmTitle || 'Enregistrer les modifications ?',
                        showCancelButton: true,
                        confirmButtonText: 'Enregistrer',
                        cancelButtonText: 'Annuler',
                        confirmButtonColor: '#4B49AC',
                    });
                    if (result.isConfirmed) form.submit();
                });
            });

            document.querySelectorAll('.js-confirm-delete').forEach(form => {
                form.addEventListener('submit', async event => {
                    event.preventDefault();

                    const result = await Swal.fire({
                        ...swalAdminTheme(),
                        icon: 'warning',
                        title: form.dataset.confirmTitle || 'Confirmer la suppression ?',
                        text: form.dataset.confirmText || 'Cette action est irreversible.',
                        showCancelButton: true,
                        confirmButtonText: 'Supprimer',
                        cancelButtonText: 'Annuler',
                        confirmButtonColor: '#dc3545',
                    });

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            document.querySelectorAll('[data-password-toggle]').forEach(button => {
                button.addEventListener('click', () => {
                    const input = document.getElementById(button.getAttribute('aria-controls'));
                    if (!input) return;

                    const shouldShow = input.type === 'password';
                    input.type = shouldShow ? 'text' : 'password';
                    button.textContent = shouldShow ? button.dataset.hideLabel : button.dataset.showLabel;
                    button.setAttribute('aria-label', shouldShow ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                });
            });

            @if (session('admin_success'))
                Swal.fire({ ...swalAdminTheme(), icon: 'success', title: 'Succes', text: @json(session('admin_success')), confirmButtonColor: '#4B49AC' });
            @endif

            @if (session('admin_error'))
                Swal.fire({ ...swalAdminTheme(), icon: 'error', title: 'Erreur', text: @json(session('admin_error')), confirmButtonColor: '#dc3545' });
            @endif

            @if ($errors->any())
                Swal.fire({ ...swalAdminTheme(), icon: 'warning', title: 'Validation', html: @json(implode('<br>', $errors->all())), confirmButtonColor: '#4B49AC' });
            @endif
        </script>
    </div>
</x-layouts::app>
