<x-layouts::app :title="__('Planification')">
    @php
        $allowedRoles = ['PLANIFICATION', 'ADMIN', 'SUPER_U'];
        $currentRole = auth()->user()?->role?->name;
        $canAccess = in_array($currentRole, $allowedRoles, true);
        $todayLabel = now()->locale('fr')->translatedFormat('l d F Y');
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-violet-50 p-5 shadow-[0_20px_45px_-28px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:from-slate-950 dark:via-slate-900 dark:to-violet-950/40 md:p-7">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-violet-700 dark:bg-violet-500/10 dark:text-violet-200">
                        <span class="inline-block h-2 w-2 rounded-full bg-violet-500"></span>
                        Module
                    </div>
                    <h1 class="flex items-center gap-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                            </svg>
                        </span>
                        Tableau de bord Planification
                    </h1>
                </div>

                <div class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Aujourd'hui</p>
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $todayLabel }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200/70 bg-white/95 shadow-[0_20px_45px_-28px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:bg-slate-900/95">
            @if ($canAccess)
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-[2rem] bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-11 w-11 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">Module Planification</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                        Bienvenue dans l'espace Planification de Dakar Terminal.
                    </p>
                </div>
            @else
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-[2rem] bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-11 w-11 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">Accès limité</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                        Cette section est réservée au rôle PLANIFICATION.
                    </p>
                </div>
            @endif
        </section>
    </div>
</x-layouts::app>
