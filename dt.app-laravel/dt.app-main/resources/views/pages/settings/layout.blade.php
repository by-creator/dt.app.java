<style>
    .settings-shell {
        display: flex;
        align-items: flex-start;
        gap: 28px;
    }

    .settings-sidebar {
        width: 100%;
        max-width: 240px;
        flex-shrink: 0;
    }

    .settings-sidebar-card,
    .settings-content-card {
        background: var(--dt-panel-bg);
        border: 1px solid var(--dt-border);
        border-radius: 24px;
        box-shadow: var(--dt-shadow);
        color: var(--dt-page-text);
    }

    .settings-sidebar-card {
        padding: 14px;
    }

    .settings-content {
        flex: 1;
        min-width: 0;
    }

    .settings-content-card {
        padding: 28px;
    }

    .settings-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--dt-page-text);
        margin: 0;
    }

    .settings-subtitle {
        margin: 8px 0 0;
        color: var(--dt-muted-text);
        font-size: 14px;
    }

    .settings-card-head {
        margin-bottom: 22px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--dt-border);
    }

    .settings-form-wrap {
        width: 100%;
        max-width: 760px;
    }

    .settings-content-card form + form,
    .settings-content-card form + div,
    .settings-content-card div + form {
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid var(--dt-border);
    }

    .settings-content-card [data-flux-field],
    .settings-content-card .space-y-6,
    .settings-content-card .space-y-8 {
        width: 100%;
    }

    .settings-sidebar-card [data-flux-navlist] {
        display: grid;
        gap: 8px;
    }

    .settings-sidebar-card [data-flux-navlist-item] {
        border: 1px solid transparent;
        border-radius: 14px;
        padding: 12px 14px;
        color: var(--dt-muted-text);
        background: transparent;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
    }

    .settings-sidebar-card [data-flux-navlist-item]:hover {
        background: rgba(99, 102, 241, 0.08);
        border-color: rgba(129, 140, 248, 0.18);
        color: var(--dt-page-text);
        transform: translateX(2px);
    }

    .settings-sidebar-card [data-flux-navlist-item][data-current],
    .settings-sidebar-card [data-flux-navlist-item][aria-current="page"] {
        background: rgba(99, 102, 241, 0.14);
        border-color: rgba(129, 140, 248, 0.28);
        color: #818cf8;
        box-shadow: inset 3px 0 0 rgba(99, 102, 241, 0.78);
    }

    .settings-content-card [data-flux-control],
    .settings-content-card input,
    .settings-content-card textarea,
    .settings-content-card select {
        background: var(--dt-input-bg) !important;
        color: var(--dt-page-text) !important;
        border-color: var(--dt-input-border) !important;
    }

    .settings-content-card [data-flux-control]::placeholder,
    .settings-content-card input::placeholder,
    .settings-content-card textarea::placeholder {
        color: var(--dt-soft-text) !important;
    }

    .settings-content-card [data-flux-description],
    .settings-content-card [data-flux-text],
    .settings-content-card p,
    .settings-content-card .text-zinc-500,
    .settings-content-card .text-zinc-600 {
        color: var(--dt-muted-text) !important;
    }

    .settings-content-card [data-flux-label],
    .settings-content-card [data-flux-heading],
    .settings-content-card h1,
    .settings-content-card h2,
    .settings-content-card h3,
    .settings-content-card label {
        color: var(--dt-page-text) !important;
    }

    .settings-content-card button,
    .settings-content-card [data-flux-button] {
        border-radius: 12px;
    }

    @media (max-width: 960px) {
        .settings-shell {
            flex-direction: column;
        }

        .settings-sidebar {
            max-width: none;
        }

        .settings-content-card {
            padding: 22px;
        }
    }
</style>

<div class="settings-shell">
    <div class="settings-sidebar">
        <div class="settings-sidebar-card">
            <flux:navlist aria-label="{{ __('Settings') }}">
                <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
                <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
                <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            </flux:navlist>
        </div>
    </div>

    <div class="settings-content">
        <div class="settings-content-card">
            <div class="settings-card-head">
                <h2 class="settings-title">{{ $heading ?? '' }}</h2>
                <p class="settings-subtitle">{{ $subheading ?? '' }}</p>
            </div>

            <div class="settings-form-wrap">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
