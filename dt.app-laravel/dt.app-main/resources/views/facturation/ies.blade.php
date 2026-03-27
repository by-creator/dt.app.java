<x-layouts::app :title="__('Gestion IES')">
    @php
        $activeTab = request()->query('tab', 'lien-acces');
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            .module-tabs {
                display:flex;
                justify-content:center;
                gap:6px;
                flex-wrap:wrap;
                border-bottom:2px solid #e8e8f0;
                margin-bottom:22px;
                background:#fff;
                padding:0 8px 6px;
            }
            .module-tab { border:none; background:transparent; color:#6c757d; font-size:14px; font-weight:600; padding:12px 16px; border-bottom:3px solid transparent; margin-bottom:-2px; display:inline-flex; align-items:center; gap:8px; cursor:pointer; transition:color .2s; }
            .module-tab:hover { color:#4B49AC; }
            .module-tab.active { color:#4B49AC; border-bottom-color:#4B49AC; }
            .module-pane { display:none; opacity:0; transform:translateY(10px); }
            .module-pane.active { display:block; animation:tabPaneFade .25s ease forwards; }
            @keyframes tabPaneFade { from { opacity:0; transform:translateY(10px);} to { opacity:1; transform:translateY(0);} }
            .ies-card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); padding:28px 24px; max-width:1040px; margin:0 auto; text-align:center; }
            .ies-card-title { font-size:20px; font-weight:700; color:#191C24; margin-bottom:6px; display:flex; align-items:center; gap:10px; justify-content:center; }
            .ies-card-subtitle { font-size:13px; color:#6c757d; margin-bottom:24px; }
            .ies-form { max-width:560px; margin:0 auto; text-align:left; }
            .ies-form .form-group { margin-bottom:0; }
            .ies-form .form-group + .form-group { margin-top:16px; }
            .ies-form .form-group label { font-size:13px; font-weight:600; color:#444; display:block; margin-bottom:6px; }
            .ies-form .form-control { border-radius:8px; font-size:13px; height:42px; border:1px solid #dee2e6; padding:9px 13px; width:100%; color:#343a40; outline:none; transition:border-color .2s; }
            .ies-form .form-control:focus { border-color:#4B49AC; box-shadow:none; }
            .ies-form .password-toggle-group { position:relative; }
            .ies-form .password-toggle-group .form-control { padding-right:5.6rem; }
            .ies-form .password-toggle { position:absolute; top:50%; right:12px; transform:translateY(-50%); border:none; background:transparent; color:#4B49AC; font-size:12px; font-weight:700; cursor:pointer; padding:4px; }
            .ies-form .password-toggle:hover { opacity:.85; }
            .ies-form-footer { text-align:center; width:100%; }
            .btn-ies-save { background:#4B49AC; color:#fff; border:none; border-radius:8px; padding:10px 18px; font-size:13px; font-weight:600; cursor:pointer; margin-top:22px; display:inline-flex; align-items:center; gap:6px; }
            .btn-ies-save:hover { background:#3e3d99; }
            .ies-alert { border-radius:8px; padding:12px 16px; font-size:13px; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:8px; justify-content:center; }
            .ies-alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
            .ies-alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
            .page-header { text-align:center; margin-bottom:20px; }
            .page-header h1 { margin:0; display:flex; align-items:center; justify-content:center; gap:10px; }
        </style>

        <div class="page-header">
            <h1><i class="fas fa-users" style="color:#4B49AC"></i>Gestion IES</h1>
        </div>

        @if (session('iesSuccess'))
            <div class="ies-alert ies-alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('iesSuccess') }}</span>
            </div>
        @endif

        @if (session('iesError'))
            <div class="ies-alert ies-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('iesError') }}</span>
            </div>
        @endif

        <div class="module-tabs" role="tablist" aria-label="Onglets Gestion IES">
            <button type="button" class="module-tab {{ $activeTab === 'lien-acces' ? 'active' : '' }}" data-target="ies-lien-acces">
                <i class="fas fa-link"></i> Lien d'acces
            </button>
            <button type="button" class="module-tab {{ $activeTab === 'creation-compte' ? 'active' : '' }}" data-target="ies-creation-compte">
                <i class="fas fa-user-plus"></i> Creation de compte
            </button>
            <button type="button" class="module-tab {{ $activeTab === 'reset-password' ? 'active' : '' }}" data-target="ies-reset-password">
                <i class="fas fa-key"></i> Reinitialisation de mot de passe
            </button>
        </div>

        <div id="ies-lien-acces" class="module-pane {{ $activeTab === 'lien-acces' ? 'active' : '' }}">
            <div class="ies-card">
                <div class="ies-card-title"><i class="fas fa-link" style="font-size:20px;color:#4B49AC"></i> Lien d'acces</div>
                <p class="ies-card-subtitle">Acces et redirection vers la plateforme IES.</p>
                <form class="ies-form" method="post" action="/facturation/ies/lien-acces">
                    @csrf
                    <div class="form-group">
                        <label for="link_ies_email">Email</label>
                        <input id="link_ies_email" name="email" type="email" class="form-control" maxlength="100" required placeholder="ex: utilisateur@ies.sn">
                    </div>
                    <div class="ies-form-footer">
                        <button type="submit" class="btn-ies-save"><i class="fas fa-paper-plane"></i> Envoyer</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="ies-creation-compte" class="module-pane {{ $activeTab === 'creation-compte' ? 'active' : '' }}">
            <div class="ies-card">
                <div class="ies-card-title"><i class="fas fa-user-plus" style="font-size:20px;color:#4B49AC"></i> Creation de compte</div>
                <p class="ies-card-subtitle">Procedure de creation et d'activation d'un compte IES.</p>
                <form class="ies-form" method="post" action="/facturation/ies/creation-compte">
                    @csrf
                    <div class="form-group">
                        <label for="create_ies_email">Email</label>
                        <input id="create_ies_email" name="email" type="email" class="form-control" maxlength="100" required placeholder="ex: utilisateur@ies.sn">
                    </div>
                    <div class="form-group">
                        <label for="create_ies_password">Mot de passe</label>
                        <div class="password-toggle-group">
                            <input id="create_ies_password" name="password" type="password" class="form-control" maxlength="100" required placeholder="........">
                            <button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="create_ies_password" aria-label="Afficher le mot de passe">Voir</button>
                        </div>
                    </div>
                    <div class="ies-form-footer">
                        <button type="submit" class="btn-ies-save"><i class="fas fa-paper-plane"></i> Envoyer</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="ies-reset-password" class="module-pane {{ $activeTab === 'reset-password' ? 'active' : '' }}">
            <div class="ies-card">
                <div class="ies-card-title"><i class="fas fa-key" style="font-size:20px;color:#4B49AC"></i> Reinitialisation de mot de passe</div>
                <p class="ies-card-subtitle">Assistance et etapes pour reinitialiser le mot de passe IES.</p>
                <form class="ies-form" method="post" action="/facturation/ies/reset-password">
                    @csrf
                    <div class="form-group">
                        <label for="reset_ies_email">Email</label>
                        <input id="reset_ies_email" name="email" type="email" class="form-control" maxlength="100" required placeholder="ex: utilisateur@ies.sn">
                    </div>
                    <div class="form-group">
                        <label for="reset_ies_password">Nouveau mot de passe</label>
                        <div class="password-toggle-group">
                            <input id="reset_ies_password" name="password" type="password" class="form-control" maxlength="100" required placeholder="........">
                            <button type="button" class="password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="reset_ies_password" aria-label="Afficher le mot de passe">Voir</button>
                        </div>
                    </div>
                    <div class="ies-form-footer">
                        <button type="submit" class="btn-ies-save"><i class="fas fa-paper-plane"></i> Envoyer</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function () {
                const tabs = document.querySelectorAll('.module-tab');
                const panes = document.querySelectorAll('.module-pane');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function () {
                        tabs.forEach(t => t.classList.remove('active'));
                        panes.forEach(p => p.classList.remove('active'));
                        this.classList.add('active');
                        const pane = document.getElementById(this.dataset.target);
                        if (pane) pane.classList.add('active');
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
            })();
        </script>
    </div>
</x-layouts::app>
