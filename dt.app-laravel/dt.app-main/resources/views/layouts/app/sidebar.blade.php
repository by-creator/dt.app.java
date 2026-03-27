<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="dt-app-shell min-h-screen bg-white dark:bg-zinc-800">
        @php
            $roleName = auth()->user()?->role?->name;

            $dashboardRouteName = match ($roleName) {
                'DIRECTION_GENERALE' => 'direction.dashboard',
                'DIRECTION_FINANCIERE' => 'direction.financiere',
                'DIRECTION_EXPLOITATION' => 'direction.exploitation',
                'FACTURATION' => 'facturation.dashboard',
                'PLANIFICATION' => 'planification.dashboard',
                default => 'dashboard',
            };

            $dashboardPatterns = match ($roleName) {
                'DIRECTION_GENERALE' => ['direction.dashboard'],
                'DIRECTION_FINANCIERE' => ['direction.financiere'],
                'DIRECTION_EXPLOITATION' => ['direction.exploitation'],
                'FACTURATION' => ['facturation.dashboard'],
                'PLANIFICATION' => ['planification.dashboard'],
                default => ['dashboard'],
            };

            $showMenuLink = in_array($roleName, [
                'ADMIN',
                'DIRECTION_GENERALE',
                'DIRECTION_FINANCIERE',
                'DIRECTION_EXPLOITATION',
                'FACTURATION',
                'PLANIFICATION',
            ], true);

            $dashboardCurrent = request()->routeIs(...$dashboardPatterns);
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="dt-sidebar border-e">
            <flux:sidebar.header class="px-4 pt-4">
                <div class="dt-brand w-full">
                    <a href="{{ route($dashboardRouteName) }}" class="dt-brand-link" wire:navigate>
                        <div class="dt-brand-mark">
                            <img src="{{ asset('img/image.png') }}" alt="Logo" class="dt-brand-image">
                        </div>
                    </a>
                </div>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="px-3 pb-2 pt-3">
                <flux:sidebar.group class="grid gap-2">
                    <flux:sidebar.item
                        icon="home"
                        :href="route($dashboardRouteName)"
                        :current="$dashboardCurrent"
                        wire:navigate
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @if ($showMenuLink)
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('menu.index')"
                            :current="request()->routeIs('menu.*')"
                            wire:navigate
                        >
                            {{ __('Menu') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="dt-user-panel hidden px-3 pb-4 lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="dt-topbar lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end" class="dt-user-menu">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
