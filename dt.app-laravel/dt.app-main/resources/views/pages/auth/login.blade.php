<x-layouts::auth :title="__('Connexion')">
    <style>
        :root {
            color-scheme: light;
            --auth-card-bg: rgba(255,255,255,.96);
            --auth-card-border: rgba(108,117,255,.12);
            --auth-card-shadow: 0 28px 80px rgba(54,63,136,.16);
            --auth-title: #1c2240;
            --auth-subtitle: #68708f;
            --auth-label: #4d5577;
            --auth-input-bg: #fff;
            --auth-input-border: #d7dcf4;
            --auth-input-text: #1f2547;
            --auth-placeholder: #b0b6cc;
            --auth-link: #252b53;
            --auth-footer: #6a7292;
        }

        .dark {
            color-scheme: dark;
            --auth-card-bg: rgba(15,23,42,.84);
            --auth-card-border: rgba(129,140,248,.18);
            --auth-card-shadow: 0 28px 80px rgba(2,6,23,.5);
            --auth-title: #e5eefb;
            --auth-subtitle: #94a3b8;
            --auth-label: #cbd5e1;
            --auth-input-bg: rgba(15,23,42,.82);
            --auth-input-border: rgba(148,163,184,.22);
            --auth-input-text: #e2e8f0;
            --auth-placeholder: #64748b;
            --auth-link: #c7d2fe;
            --auth-footer: #94a3b8;
        }

        html, body { height: 100%; overflow: hidden; }
        .dt-auth-shell { width: min(100%, 38rem); margin: 0 auto; }
        .dt-auth-card { background: var(--auth-card-bg); border: 1px solid var(--auth-card-border); border-radius: 1.75rem; box-shadow: var(--auth-card-shadow); overflow: hidden; backdrop-filter: blur(14px); }
        .dt-auth-body { padding: 3rem 3rem 2.75rem; }
        .dt-auth-logo { display: flex; align-items: center; justify-content: flex-start; width: 100%; margin-bottom: 1.75rem; }
        .dt-auth-logo img { max-width: 15rem; width: 100%; height: auto; object-fit: contain; }
        .dt-auth-title { margin: 0; font-size: clamp(1.9rem, 3vw, 2.4rem); line-height: 1.05; color: var(--auth-title); font-weight: 800; }
        .dt-auth-subtitle { margin: .75rem 0 2rem; color: var(--auth-subtitle); font-size: 1rem; }
        .dt-auth-alert { padding: .95rem 1rem; border-radius: .95rem; margin-bottom: 1rem; font-size: .92rem; }
        .dt-auth-alert-danger { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }
        .dt-auth-alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
        .dt-auth-form { display: grid; gap: 1.15rem; }
        .dt-auth-field { display: grid; gap: .45rem; }
        .dt-auth-field label { font-size: .82rem; font-weight: 700; color: var(--auth-label); text-transform: uppercase; letter-spacing: .08em; }
        .dt-auth-input { width: 100%; border: 1px solid var(--auth-input-border); border-radius: .95rem; padding: 1rem 1.05rem; font-size: 1rem; color: var(--auth-input-text); background: var(--auth-input-bg); transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
        .dt-auth-input::placeholder { color: var(--auth-placeholder); }
        .dt-auth-input:focus { outline: none; border-color: #6965df; box-shadow: 0 0 0 4px rgba(105,101,223,.12); transform: translateY(-1px); }
        .dt-password-toggle-group { position: relative; }
        .dt-password-toggle-group .dt-auth-input { padding-right: 6rem; }
        .dt-password-toggle { position: absolute; top: 50%; right: .85rem; transform: translateY(-50%); border: 0; background: transparent; color: var(--auth-link); font-size: .86rem; font-weight: 700; cursor: pointer; padding: .25rem; }
        .dt-password-toggle:hover { opacity: .86; }
        .dt-auth-submit { margin-top: .35rem; width: 100%; border: 0; border-radius: 999px; padding: 1rem 1.2rem; font-size: 1rem; font-weight: 800; letter-spacing: .03em; color: #fff; background: linear-gradient(135deg, #4b49ac, #6965df); cursor: pointer; transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease; box-shadow: 0 18px 34px rgba(75,73,172,.24); }
        .dt-auth-submit:hover { opacity: .96; transform: translateY(-1px); }
        .dt-auth-meta { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: .25rem; flex-wrap: wrap; }
        .dt-auth-check { display: inline-flex; align-items: center; gap: .65rem; color: var(--auth-subtitle); font-size: .95rem; }
        .dt-auth-check input { width: 1rem; height: 1rem; accent-color: #4b49ac; }
        .dt-auth-link { color: var(--auth-link); font-weight: 600; text-decoration: underline; text-underline-offset: .18em; }
        .dt-auth-footer { margin-top: 2rem; text-align: center; color: var(--auth-footer); font-size: .95rem; }
        .dt-auth-footer a { color: #5c58d4; font-weight: 700; text-decoration: none; }
        .dt-auth-footer a:hover { text-decoration: underline; }
        @media (max-width: 640px) { .dt-auth-body { padding: 2rem 1.4rem 2.1rem; } }
    </style>

    <div class="dt-auth-shell">
        <div class="dt-auth-card">
            <div class="dt-auth-body">
                <div class="dt-auth-logo">
                    <img src="{{ asset('img/image.png') }}" alt="Dakar Terminal">
                </div>

                <h1 class="dt-auth-title">{{ __('Bonjour, commençons') }}</h1>
                <p class="dt-auth-subtitle">{{ __('Connectez-vous pour accéder à la plateforme Dakar Terminal.') }}</p>

                @if ($errors->any())
                    <div class="dt-auth-alert dt-auth-alert-danger">{{ $errors->first() }}</div>
                @endif

                @if (session('status'))
                    <div class="dt-auth-alert dt-auth-alert-info">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="dt-auth-form">
                    @csrf

                    <div class="dt-auth-field">
                        <label for="email">{{ __('Adresse email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="Entrez votre adresse mail" class="dt-auth-input">
                    </div>

                    <div class="dt-auth-field">
                        <label for="password">{{ __('Mot de passe') }}</label>
                        <div class="dt-password-toggle-group">
                            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Entrez votre mot de passe" class="dt-auth-input">
                            <button type="button" class="dt-password-toggle" data-password-toggle data-show-label="Voir" data-hide-label="Masquer" aria-controls="password" aria-label="Afficher le mot de passe">Voir</button>
                        </div>
                    </div>

                    <button type="submit" class="dt-auth-submit" data-test="login-button">{{ __('SE CONNECTER') }}</button>

                    <div class="dt-auth-meta">
                        <label class="dt-auth-check" for="remember">
                            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            <span>{{ __('Se souvenir de moi') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="dt-auth-link" wire:navigate>{{ __('Mot de passe oublié ?') }}</a>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
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
    </script>
</x-layouts::auth>
