<x-layouts::app :title="__('Direction Financière')">
    @php
        $allowedRoles = ['DIRECTION_FINANCIERE', 'ADMIN', 'SUPER_U'];
        $currentRole = auth()->user()?->role?->name;
        $canAccess = in_array($currentRole, $allowedRoles, true);
        $todayLabel = now()->locale('fr')->translatedFormat('l d F Y');
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-emerald-50 p-5 shadow-[0_20px_45px_-28px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/40 md:p-7">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Module
                    </div>
                    <h1 class="flex items-center gap-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </span>
                        Tableau de bord Direction Financière
                    </h1>
                </div>

                <div class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white">
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
                    <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-[2rem] bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-11 w-11 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">Module Direction Financière</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-slate-500 dark:text-slate-400">
                        Bienvenue dans l'espace Direction Financière de Dakar Terminal.
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
                        Cette section est réservée au rôle DIRECTION_FINANCIERE.
                    </p>
                </div>
            @endif
        </section>
    </div>
</x-layouts::app>
