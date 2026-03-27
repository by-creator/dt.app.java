<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased text-slate-900 dark:text-slate-100">
        <div class="min-h-svh bg-[radial-gradient(circle_at_top_left,_rgba(121,120,233,0.12),_transparent_28%),linear-gradient(180deg,_#f7f8ff_0%,_#eef2ff_100%)] px-4 py-8 dark:bg-[radial-gradient(circle_at_top_left,_rgba(121,120,233,0.18),_transparent_28%),linear-gradient(180deg,_#020617_0%,_#0f172a_100%)] md:px-8 md:py-12">
            <div class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-3xl items-center justify-center">
                <div class="w-full">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
