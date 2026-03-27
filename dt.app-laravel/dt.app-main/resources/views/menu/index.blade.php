<x-layouts::app :title="__('Menu')">
    @php
        $roleName = auth()->user()?->role?->name;
        $userEmail = strtolower((string) auth()->user()?->email);
        $menuSection = $menuSection ?? null;
        $canAccessFacturationRemisesMenu = $roleName === 'ADMIN' || in_array($userEmail, [
            'aliounebadara.sy@dakar-terminal.com',
            'charles.sarr@dakar-terminal.com',
        ], true);

        $rootMenuByRole = [
            'ADMIN' => [
                [
                    'title' => 'Direction Generale',
                    'description' => 'Accedez au sous-menu de la Direction Generale.',
                    'route' => route('menu.direction-generale'),
                    'icon' => 'building-office-2',
                    'keywords' => 'direction generale dashboard dg remises',
                ],
                [
                    'title' => 'Direction Financiere',
                    'description' => 'Accedez au sous-menu de la Direction Financiere.',
                    'route' => route('menu.direction-financiere'),
                    'icon' => 'banknotes',
                    'keywords' => 'direction financiere dashboard df remises',
                ],
                [
                    'title' => 'Direction Exploitation',
                    'description' => 'Accedez au sous-menu de la Direction Exploitation.',
                    'route' => route('menu.direction-exploitation'),
                    'icon' => 'truck',
                    'keywords' => 'direction exploitation dashboard de remises',
                ],
                [
                    'title' => 'Planification',
                    'description' => 'Accedez au sous-menu du module Planification.',
                    'route' => route('menu.planification'),
                    'icon' => 'calendar-days',
                    'keywords' => 'planification dashboard codification',
                ],
                [
                    'title' => 'Facturation',
                    'description' => 'Accedez au sous-menu du module Facturation.',
                    'route' => route('menu.facturation'),
                    'icon' => 'book-open-text',
                    'keywords' => 'facturation validations remises rapports unify ies gfa',
                ],
                [
                    'title' => 'Administration',
                    'description' => 'Gestion des utilisateurs, roles et permissions.',
                    'route' => route('administration.index'),
                    'icon' => 'shield-check',
                    'keywords' => 'administration utilisateurs roles permissions',
                ],
                [
                    'title' => 'Audit',
                    'description' => 'Consultation des journaux et traces d activite.',
                    'route' => route('audit.index'),
                    'icon' => 'clipboard-document-list',
                    'keywords' => 'audit journaux traces activite',
                ],
                [
                    'title' => 'Repas',
                    'description' => 'Envoyez le menu du jour par email.',
                    'route' => route('repas.index'),
                    'icon' => 'cake',
                    'keywords' => 'repas menu du jour plats',
                ],
            ],
            'DIRECTION_GENERALE' => [
                [
                    'title' => 'Gestion des remises',
                    'description' => 'Validez les demandes de remises en attente cote direction.',
                    'route' => route('facturation.remises'),
                    'icon' => 'percent-badge',
                    'keywords' => 'remises direction generale',
                ],
            ],
            'DIRECTION_FINANCIERE' => [
                [
                    'title' => 'Gestion des remises',
                    'description' => 'Consultez et validez les demandes de remises cote direction financiere.',
                    'route' => route('facturation.remises'),
                    'icon' => 'percent-badge',
                    'keywords' => 'remises direction financiere',
                ],
            ],
            'DIRECTION_EXPLOITATION' => [
                [
                    'title' => 'Gestion des remises',
                    'description' => 'Consultez et validez les demandes de remises cote direction exploitation.',
                    'route' => route('facturation.remises'),
                    'icon' => 'percent-badge',
                    'keywords' => 'remises direction exploitation',
                ],
            ],
            'FACTURATION' => [
                [
                    'title' => 'Guichet GFA',
                    'description' => 'Acces au guichet public GFA.',
                    'route' => route('facturation.guichet-gfa.public'),
                    'icon' => 'computer-desktop',
                    'keywords' => 'guichet gfa public',
                ],
                [
                    'title' => 'Gestion des validations',
                    'description' => 'Consultez et traitez les validations en attente.',
                    'route' => route('facturation.validations'),
                    'icon' => 'check-badge',
                    'keywords' => 'validations facturation',
                ],
                [
                    'title' => 'Gestion des remises',
                    'description' => 'Traitez les demandes de remises cote facturation.',
                    'route' => route('facturation.remises'),
                    'icon' => 'percent-badge',
                    'keywords' => 'remises facturation',
                    'visible' => $canAccessFacturationRemisesMenu,
                ],
                [
                    'title' => 'Gestion des rapports',
                    'description' => 'Consultez et administrez les rapports de suivi.',
                    'route' => route('facturation.rapport'),
                    'icon' => 'document-text',
                    'keywords' => 'rapports suivi',
                ],
                [
                    'title' => 'Gestion Unify',
                    'description' => 'Gerez les tiers et imports du module Unify.',
                    'route' => route('facturation.unify'),
                    'icon' => 'clipboard-document-list',
                    'keywords' => 'unify tiers',
                    'navigate' => false,
                ],
                [
                    'title' => 'Gestion IES',
                    'description' => 'Acces aux operations IES.',
                    'route' => route('facturation.ies'),
                    'icon' => 'users',
                    'keywords' => 'ies comptes',
                ],
            ],
            'PLANIFICATION' => [
                [
                    'title' => 'Codification',
                    'description' => 'Importez et gerez les manifestes du module Planification.',
                    'route' => route('planification.upload-manifest'),
                    'icon' => 'document-arrow-up',
                    'keywords' => 'codification manifest upload planification',
                ],
            ],
        ];

        $adminSubmenus = [
            'direction-generale' => [
                'title' => 'Menu Direction Generale',
                'description' => 'Retrouvez les acces de la Direction Generale.',
                'links' => [
                    [
                        'title' => 'Gestion des remises',
                        'description' => 'Validez les demandes de remises en attente cote direction.',
                        'route' => route('facturation.remises'),
                        'icon' => 'percent-badge',
                        'keywords' => 'remises direction generale',
                    ],
                ],
            ],
            'direction-financiere' => [
                'title' => 'Menu Direction Financiere',
                'description' => 'Retrouvez les acces de la Direction Financiere.',
                'links' => [
                    [
                        'title' => 'Gestion des remises',
                        'description' => 'Consultez et validez les demandes de remises cote direction financiere.',
                        'route' => route('facturation.remises'),
                        'icon' => 'percent-badge',
                        'keywords' => 'remises direction financiere',
                    ],
                ],
            ],
            'direction-exploitation' => [
                'title' => 'Menu Direction Exploitation',
                'description' => 'Retrouvez les acces de la Direction Exploitation.',
                'links' => [
                    [
                        'title' => 'Gestion des remises',
                        'description' => 'Consultez et validez les demandes de remises cote direction exploitation.',
                        'route' => route('facturation.remises'),
                        'icon' => 'percent-badge',
                        'keywords' => 'remises direction exploitation',
                    ],
                ],
            ],
            'facturation' => [
                'title' => 'Menu Facturation',
                'description' => 'Retrouvez les principaux acces du module Facturation.',
                'links' => [
                    [
                        'title' => 'Guichet GFA',
                        'description' => 'Acces au guichet public GFA.',
                        'route' => route('facturation.guichet-gfa.public'),
                        'icon' => 'computer-desktop',
                        'keywords' => 'guichet gfa public',
                        'target' => '_blank',
                        'rel' => 'noopener',
                    ],
                    [
                        'title' => 'Gestion des validations',
                        'description' => 'Consultez et traitez les validations en attente.',
                        'route' => route('facturation.validations'),
                        'icon' => 'check-badge',
                        'keywords' => 'validations facturation',
                    ],
                    [
                        'title' => 'Gestion des remises',
                        'description' => 'Traitez les demandes de remises cote facturation.',
                        'route' => route('facturation.remises'),
                        'icon' => 'percent-badge',
                        'keywords' => 'remises facturation',
                        'visible' => $canAccessFacturationRemisesMenu,
                    ],
                    [
                        'title' => 'Gestion des rapports',
                        'description' => 'Consultez et administrez les rapports de suivi.',
                        'route' => route('facturation.rapport'),
                        'icon' => 'document-text',
                        'keywords' => 'rapports suivi',
                    ],
                    [
                        'title' => 'Gestion Unify',
                        'description' => 'Gerez les tiers et imports du module Unify.',
                        'route' => route('facturation.unify'),
                        'icon' => 'clipboard-document-list',
                        'keywords' => 'unify tiers',
                        'navigate' => false,
                    ],
                    [
                        'title' => 'Gestion IES',
                        'description' => 'Acces aux operations IES.',
                        'route' => route('facturation.ies'),
                        'icon' => 'users',
                        'keywords' => 'ies comptes',
                    ],
                    [
                        'title' => 'Gfa Admin',
                        'description' => 'Administration complete du module GFA.',
                        'route' => route('facturation.gfa-admin'),
                        'icon' => 'server-stack',
                        'keywords' => 'gfa admin facturation',
                        'navigate' => false,
                    ],
                ],
            ],
            'planification' => [
                'title' => 'Menu Planification',
                'description' => 'Retrouvez les acces du module Planification.',
                'links' => [
                    [
                        'title' => 'Codification',
                        'description' => 'Importez et gerez les manifestes du module Planification.',
                        'route' => route('planification.upload-manifest'),
                        'icon' => 'document-arrow-up',
                        'keywords' => 'codification manifest upload planification',
                    ],
                ],
            ],
        ];

        $submenu = $roleName === 'ADMIN' && $menuSection ? ($adminSubmenus[$menuSection] ?? null) : null;
        $menuLinks = collect($submenu['links'] ?? ($rootMenuByRole[$roleName] ?? []))
            ->filter(fn (array $link) => $link['visible'] ?? true)
            ->values();

        $pageTitle = $submenu['title'] ?? match ($roleName) {
            'DIRECTION_GENERALE' => 'Menu Direction Generale',
            'DIRECTION_FINANCIERE' => 'Menu Direction Financiere',
            'DIRECTION_EXPLOITATION' => 'Menu Direction Exploitation',
            'FACTURATION' => 'Menu Facturation',
            'PLANIFICATION' => 'Menu Planification',
            default => 'Menu administrateur',
        };

        $pageDescription = $submenu['description'] ?? 'Recherchez rapidement un module et accedez aux espaces disponibles pour votre role.';
    @endphp

    <div class="admin-menu-page flex h-full w-full flex-1 flex-col gap-6 pb-8">
        <style>
            .admin-menu-page {
                --am-border: rgba(148, 163, 184, 0.18);
                --am-surface: rgba(255,255,255,0.92);
                --am-shadow: 0 20px 45px -28px rgba(15, 23, 42, 0.45);
            }

            .dark .admin-menu-page {
                --am-border: rgba(71, 85, 105, 0.45);
                --am-surface: rgba(15, 23, 42, 0.92);
                --am-shadow: 0 24px 50px -30px rgba(2, 6, 23, 0.85);
            }

            .admin-menu-hero {
                border: 1px solid var(--am-border);
                border-radius: 1.75rem;
                background:
                    radial-gradient(circle at top left, rgba(121,120,233,.16), transparent 28%),
                    linear-gradient(180deg, rgba(248,250,252,.96), rgba(241,245,249,.9));
                box-shadow: var(--am-shadow);
            }

            .dark .admin-menu-hero {
                background:
                    radial-gradient(circle at top left, rgba(121,120,233,.20), transparent 28%),
                    linear-gradient(180deg, rgba(15,23,42,.96), rgba(15,23,42,.88));
            }

            .admin-menu-search {
                width: 100%;
                border: 1px solid var(--dt-input-border);
                background: var(--dt-input-bg);
                color: var(--dt-page-text);
                border-radius: 1rem;
                padding: 0.95rem 1rem 0.95rem 2.8rem;
                font-size: 14px;
                outline: none;
            }

            .admin-menu-search:focus {
                border-color: #4B49AC;
                box-shadow: 0 0 0 4px var(--dt-ring);
            }

            .admin-menu-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 16px;
                align-items: stretch;
            }

            .admin-menu-card {
                display: flex;
                flex-direction: column;
                gap: 12px;
                width: 252px;
                max-width: 252px;
                min-height: 190px;
                border: 1px solid var(--am-border);
                border-radius: 1.4rem;
                background: var(--am-surface);
                box-shadow: var(--am-shadow);
                padding: 20px;
                transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
            }

            .admin-menu-card:hover {
                transform: translateY(-3px);
                border-color: rgba(75, 73, 172, 0.28);
                box-shadow: 0 22px 46px -30px rgba(75, 73, 172, 0.35);
            }

            .admin-menu-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 52px;
                height: 52px;
                border-radius: 1rem;
                background: linear-gradient(135deg, #4b49ac, #7978e9);
                color: #fff;
                box-shadow: 0 16px 30px -24px rgba(75, 73, 172, 0.8);
            }

            .admin-menu-empty {
                display: none;
                border: 1px dashed var(--am-border);
                border-radius: 1.25rem;
                background: var(--am-surface);
                color: var(--dt-muted-text);
                text-align: center;
                padding: 28px;
            }
        </style>

        @if ($menuLinks->isEmpty())
            <section class="rounded-[1.5rem] border border-slate-200/70 bg-white/95 px-6 py-14 text-center shadow-[0_20px_45px_-28px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:bg-slate-900/95">
                <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">Aucun menu disponible</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                    Aucun acces rapide n'est defini pour votre role.
                </p>
            </section>
        @else
            <section class="admin-menu-hero p-5 md:p-7">
                <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200">
                            <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                            Navigation rapide
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $pageTitle }}</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                            {{ $pageDescription }}
                        </p>
                    </div>

                    <div class="flex w-full max-w-md flex-col gap-3">
                        @if ($submenu)
                            <a href="{{ route('menu.index') }}"
                               class="inline-flex items-center gap-2 self-start rounded-full bg-white/85 px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-slate-200/70 transition hover:bg-white dark:bg-slate-900/70 dark:text-indigo-200 dark:ring-slate-700/70"
                               wire:navigate>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                </svg>
                                Retour au menu principal
                            </a>
                        @endif

                        <div class="relative">
                            <label class="sr-only" for="admin-menu-search">Rechercher un menu</label>
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                            <input id="admin-menu-search" class="admin-menu-search" type="search" placeholder="Rechercher un menu...">
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <div class="admin-menu-grid" id="admin-menu-grid">
                    @foreach ($menuLinks as $link)
                        <a href="{{ $link['route'] }}"
                           class="admin-menu-card"
                           data-menu-card
                           data-keywords="{{ strtolower($link['title'].' '.$link['description'].' '.$link['keywords']) }}"
                           @if (!empty($link['target'])) target="{{ $link['target'] }}" @endif
                           @if (!empty($link['rel'])) rel="{{ $link['rel'] }}" @endif
                           @if (($link['navigate'] ?? true) === true) wire:navigate @endif>
                            <span class="admin-menu-icon">
                                <flux:icon :name="$link['icon']" class="h-6 w-6" />
                            </span>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $link['title'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $link['description'] }}</p>
                            </div>
                            <span class="mt-auto inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 dark:text-indigo-200">
                                Ouvrir
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 4.5H19.5V10.5M19.5 4.5L10.5 13.5M6 7.5V18A1.5 1.5 0 0 0 7.5 19.5H18" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div id="admin-menu-empty" class="admin-menu-empty mt-4">
                    Aucun menu ne correspond a votre recherche.
                </div>
            </section>

            <script>
                function normalizeMenuText(value) {
                    return (value || '')
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .trim();
                }

                function initAdminMenuSearch() {
                    const adminMenuSearch = document.getElementById('admin-menu-search');
                    const adminMenuEmpty = document.getElementById('admin-menu-empty');

                    if (!adminMenuSearch || adminMenuSearch.dataset.bound === 'true') {
                        return;
                    }

                    adminMenuSearch.dataset.bound = 'true';

                    const filterCards = () => {
                        const query = normalizeMenuText(adminMenuSearch.value);
                        const adminMenuCards = [...document.querySelectorAll('[data-menu-card]')];
                        let visibleCount = 0;

                        adminMenuCards.forEach(card => {
                            const keywords = normalizeMenuText(card.dataset.keywords || '');
                            const match = !query || keywords.includes(query);
                            card.style.display = match ? '' : 'none';
                            if (match) visibleCount++;
                        });

                        if (adminMenuEmpty) {
                            adminMenuEmpty.style.display = visibleCount === 0 ? 'block' : 'none';
                        }
                    };

                    adminMenuSearch.addEventListener('input', filterCards);
                    filterCards();
                }

                initAdminMenuSearch();
                document.addEventListener('livewire:navigated', initAdminMenuSearch);
            </script>
        @endif
    </div>
</x-layouts::app>
