<x-layouts::app :title="__('Audit')">
    <div class="audit-page flex h-full w-full flex-1 flex-col gap-6 pb-8">

        <style>
            .audit-page { overflow: visible; min-height: max-content; }

            .audit-badge {
                display: inline-flex; align-items: center; justify-content: center;
                border-radius: 6px; padding: 2px 8px; font-size: 11px;
                font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase;
            }
            .badge-get    { background: rgba(16,185,129,.12); color: #059669; }
            .badge-post   { background: rgba(59,130,246,.12); color: #2563eb; }
            .badge-put,
            .badge-patch  { background: rgba(245,158,11,.12); color: #d97706; }
            .badge-delete { background: rgba(239,68,68,.12);  color: #dc2626; }
            .badge-head   { background: rgba(139,92,246,.12); color: #7c3aed; }

            .badge-2xx { background: rgba(16,185,129,.12); color: #059669; }
            .badge-3xx { background: rgba(59,130,246,.12); color: #2563eb; }
            .badge-4xx { background: rgba(245,158,11,.12); color: #d97706; }
            .badge-5xx { background: rgba(239,68,68,.12);  color: #dc2626; }

            .audit-row:hover { background: rgba(75,73,172,.04); }
            .dark .audit-row:hover { background: rgba(121,120,233,.08); }

            .filter-bar {
                display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
                padding: 16px 20px;
                border-bottom: 1px solid var(--dt-border, rgba(148,163,184,.18));
                background: var(--dt-panel-bg, #fff);
            }
            .dark .filter-bar { background: rgba(15,23,42,.4); }

            .filter-input {
                border: 1px solid var(--dt-border, #e2e8f0);
                border-radius: 10px; padding: 7px 12px; font-size: 13px;
                background: transparent; color: inherit; outline: none; min-width: 140px;
            }
            .filter-input:focus { border-color: #4b49ac; box-shadow: 0 0 0 3px rgba(75,73,172,.12); }
            .filter-label { font-size: 11px; font-weight: 600; text-transform: uppercase;
                letter-spacing: .06em; color: #94a3b8; margin-bottom: 4px; display: block; }

            .export-btn {
                display: inline-flex; align-items: center; gap: 6px;
                border-radius: 10px; padding: 7px 14px; font-size: 13px;
                font-weight: 600; cursor: pointer; border: none; transition: opacity .15s;
            }
            .export-btn:hover { opacity: .85; }
            .btn-excel { background: #16a34a; color: #fff; }
            .btn-pdf   { background: #dc2626; color: #fff; }
        </style>

        {{-- Header --}}
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-indigo-50 p-5 shadow-[0_20px_45px_-28px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950/40 md:p-7">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200">
                        <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                        Administration
                    </div>
                    <h1 class="flex items-center gap-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </span>
                        Journal d'audit
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Traçabilité complète de toutes les activités de la plateforme.
                    </p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-sm text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Aujourd'hui</p>
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ now()->locale('fr')->translatedFormat('l d F Y') }}</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Table card --}}
        <section class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white/95 shadow-[0_20px_45px_-28px_rgba(15,23,42,0.45)] dark:border-slate-700/70 dark:bg-slate-900/95">

            {{-- Filters --}}
            <form method="GET" action="{{ route('audit.index') }}" class="filter-bar">
                <div>
                    <label class="filter-label">Utilisateur</label>
                    <input type="text" name="user" value="{{ request('user') }}" placeholder="Nom, email ou rôle…" class="filter-input">
                </div>
                <div>
                    <label class="filter-label">Méthode</label>
                    <select name="method" class="filter-input">
                        <option value="">Toutes</option>
                        @foreach (['GET','POST','PUT','PATCH','DELETE'] as $m)
                            <option value="{{ $m }}" @selected(request('method') === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="filter-label">Du</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input">
                </div>
                <div>
                    <label class="filter-label">Au</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input">
                </div>
                <div>
                    <label class="filter-label">Recherche (URL / route / IP)</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher…" class="filter-input" style="min-width:200px;">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="export-btn" style="background:#4b49ac;color:#fff;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Filtrer
                    </button>
                    @if (request()->hasAny(['user','method','date_from','date_to','search']))
                        <a href="{{ route('audit.index') }}" class="export-btn" style="background:#64748b;color:#fff;">✕ Réinitialiser</a>
                    @endif
                </div>

                {{-- Export buttons --}}
                <div class="ml-auto flex items-end gap-2">
                    <a href="{{ route('audit.export.csv', request()->query()) }}" class="export-btn btn-excel">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Excel / CSV
                    </a>
                    <a href="{{ route('audit.export.pdf', request()->query()) }}" target="_blank" class="export-btn btn-pdf">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF
                    </a>
                </div>
            </form>

            {{-- Summary bar --}}
            <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-3 dark:border-slate-700/70">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format($logs->total()) }}</span>
                    entrée{{ $logs->total() > 1 ? 's' : '' }} trouvée{{ $logs->total() > 1 ? 's' : '' }}
                    &mdash; page {{ $logs->currentPage() }}/{{ $logs->lastPage() }}
                </p>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50/85 dark:bg-slate-900/65">
                        <tr class="text-xs uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                            <th class="px-4 py-3 font-semibold">Date</th>
                            <th class="px-4 py-3 font-semibold">Utilisateur</th>
                            <th class="px-4 py-3 font-semibold">IP</th>
                            <th class="px-4 py-3 font-semibold">Méthode</th>
                            <th class="px-4 py-3 font-semibold">Route / URL</th>
                            <th class="px-4 py-3 font-semibold">Action</th>
                            <th class="px-4 py-3 font-semibold">Statut</th>
                            <th class="px-4 py-3 font-semibold">ms</th>
                            <th class="px-4 py-3 font-semibold">Détails</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60 dark:divide-slate-700/60">
                        @forelse ($logs as $log)
                            @php
                                $statusClass = match(true) {
                                    $log->response_status >= 500 => 'badge-5xx',
                                    $log->response_status >= 400 => 'badge-4xx',
                                    $log->response_status >= 300 => 'badge-3xx',
                                    default => 'badge-2xx',
                                };
                                $methodClass = 'badge-' . strtolower($log->method);
                            @endphp
                            <tr class="audit-row transition">
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $log->created_at?->format('d/m/Y') }}<br>
                                    <span class="font-mono text-[11px]">{{ $log->created_at?->format('H:i:s') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $log->user_name ?? '—' }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $log->user_email }}</p>
                                    @if ($log->user_role)
                                        <span class="audit-badge mt-1" style="background:rgba(75,73,172,.1);color:#4b49ac;font-size:10px;">{{ $log->user_role }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400">
                                    {{ $log->ip_address }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="audit-badge {{ $methodClass }}">{{ $log->method }}</span>
                                </td>
                                <td class="px-4 py-3 max-w-[220px]">
                                    @if ($log->route_name)
                                        <p class="font-semibold text-slate-800 dark:text-slate-100 truncate text-xs">{{ $log->route_name }}</p>
                                    @endif
                                    <p class="text-xs text-slate-400 truncate" title="{{ $log->url }}">{{ $log->url }}</p>
                                </td>
                                <td class="px-4 py-3 max-w-[180px]">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate" title="{{ $log->controller_action }}">
                                        {{ $log->controller_action ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="audit-badge {{ $statusClass }}">{{ $log->response_status ?? '—' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-indigo-100 hover:text-indigo-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-indigo-500/20 dark:hover:text-indigo-200 transition"
                                        onclick="showAuditDetail({{ $log->id }}, @js($log))"
                                    >
                                        Voir
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-16 text-center text-sm text-slate-500 dark:text-slate-400">
                                    Aucune entrée d'audit trouvée pour ces critères.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div class="border-t border-slate-200/70 px-5 py-4 dark:border-slate-700/70">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>

        <script>
            function showAuditDetail(id, log) {
                const fmt = (val) => val !== null && val !== undefined && val !== '' ? val : '—';

                let payloadHtml = '—';
                if (log.payload && Object.keys(log.payload).length > 0) {
                    payloadHtml = '<pre style="text-align:left;background:rgba(0,0,0,.06);padding:10px 14px;border-radius:8px;font-size:11px;max-height:120px;overflow:auto;white-space:pre-wrap;">' + JSON.stringify(log.payload, null, 2) + '</pre>';
                }

                let uaShort = fmt(log.user_agent);
                if (uaShort.length > 80) uaShort = uaShort.substring(0, 80) + '…';

                const theme = {
                    background: getComputedStyle(document.documentElement).getPropertyValue('--dt-panel-bg').trim() || '#ffffff',
                    color: getComputedStyle(document.documentElement).getPropertyValue('--dt-page-text').trim() || '#1e293b',
                };

                Swal.fire({
                    ...theme,
                    title: `<span style="font-size:15px">Entrée #${id}</span>`,
                    html: `
                        <table style="width:100%;text-align:left;font-size:13px;border-collapse:collapse;">
                            <tr><td style="padding:6px 8px;color:#94a3b8;width:40%">Date</td><td style="padding:6px 8px;font-weight:600">${fmt(log.created_at)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Utilisateur</td><td style="padding:6px 8px;font-weight:600">${fmt(log.user_name)} <span style="color:#94a3b8">(${fmt(log.user_email)})</span></td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Rôle</td><td style="padding:6px 8px">${fmt(log.user_role)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Adresse IP</td><td style="padding:6px 8px;font-family:monospace">${fmt(log.ip_address)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Session</td><td style="padding:6px 8px;font-family:monospace;font-size:11px">${fmt(log.session_id)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Méthode</td><td style="padding:6px 8px;font-weight:600">${fmt(log.method)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">URL</td><td style="padding:6px 8px;font-size:11px;word-break:break-all">${fmt(log.url)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Route</td><td style="padding:6px 8px">${fmt(log.route_name)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Contrôleur</td><td style="padding:6px 8px;font-size:11px">${fmt(log.controller_action)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Statut HTTP</td><td style="padding:6px 8px;font-weight:600">${fmt(log.response_status)}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">Durée</td><td style="padding:6px 8px">${log.duration_ms !== null ? log.duration_ms + ' ms' : '—'}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8">User Agent</td><td style="padding:6px 8px;font-size:11px">${uaShort}</td></tr>
                            <tr><td style="padding:6px 8px;color:#94a3b8;vertical-align:top">Payload</td><td style="padding:6px 8px">${payloadHtml}</td></tr>
                        </table>
                    `,
                    width: 680,
                    confirmButtonText: 'Fermer',
                    confirmButtonColor: '#4B49AC',
                    showClass: { popup: '' },
                });
            }
        </script>
    </div>
</x-layouts::app>
