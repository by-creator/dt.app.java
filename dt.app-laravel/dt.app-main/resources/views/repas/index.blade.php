<x-layouts::app :title="__('Repas')">
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
            <h1><i class="fas fa-utensils" style="color:#4B49AC"></i>Repas</h1>
        </div>

        @if (session('repasSuccess'))
            <div class="ies-alert ies-alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('repasSuccess') }}</span>
            </div>
        @endif

        @if (session('repasError'))
            <div class="ies-alert ies-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('repasError') }}</span>
            </div>
        @endif

        <div class="module-tabs" role="tablist" aria-label="Onglets Repas">
            <button type="button" class="module-tab active" data-target="repas-menu-du-jour">
                <i class="fas fa-bowl-food"></i> Menu du jour
            </button>
        </div>

        <div id="repas-menu-du-jour" class="module-pane active">
            <div class="ies-card">
                <div class="ies-card-title"><i class="fas fa-utensils" style="font-size:20px;color:#4B49AC"></i> Menu du jour</div>
                <p class="ies-card-subtitle">Envoyez le menu du jour par email.</p>
                <form class="ies-form" method="post" action="{{ route('repas.menu-du-jour') }}">
                    @csrf
                    <div class="form-group">
                        <label for="repas_plat1">Plat 1</label>
                        <input id="repas_plat1" name="plat1" type="text" class="form-control" maxlength="255" required placeholder="ex: Thieboudienne" value="{{ old('plat1') }}">
                    </div>
                    <div class="form-group">
                        <label for="repas_plat2">Plat 2</label>
                        <input id="repas_plat2" name="plat2" type="text" class="form-control" maxlength="255" required placeholder="ex: Yassa poulet" value="{{ old('plat2') }}">
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
            })();
        </script>
    </div>
</x-layouts::app>
