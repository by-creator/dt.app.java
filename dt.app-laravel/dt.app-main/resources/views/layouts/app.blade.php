<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="dt-main">
        <div class="dt-main-inner">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
