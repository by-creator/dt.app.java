<?php

namespace App\Http\Controllers;

use App\Models\GfaWifiSetting;
use App\Models\ServiceGfa;
use App\Services\ScanTokenGfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GfaDisplayController extends Controller
{
    private const DEFAULT_WIFI_SSID = 'DakarTerminal_WiFi';
    private const DEFAULT_WIFI_PASSWORD = '';

    public function __construct(
        protected ScanTokenGfaService $scanTokenGfaService,
    ) {}

    public function display(): View
    {
        $settings = GfaWifiSetting::query()->find(1) ?? new GfaWifiSetting([
            'ssid' => self::DEFAULT_WIFI_SSID,
            'password' => self::DEFAULT_WIFI_PASSWORD,
        ]);

        $wifiPayload = sprintf(
            'WIFI:T:%s;S:%s;P:%s;;',
            $settings->password !== '' ? 'WPA' : 'nopass',
            $this->escapeWifi($settings->ssid),
            $this->escapeWifi($settings->password),
        );

        return view('facturation.public-gfa-admin', [
            'wifiSsid' => $settings->ssid,
            'wifiQrData' => $wifiPayload,
        ]);
    }

    public function gfaAdmin(Request $request): View
    {
        abort_unless(in_array($request->user()?->role?->name, ['ADMIN', 'SUPER_U'], true), 403);

        return view('facturation.gfa-admin', [
            'wifiSettings' => GfaWifiSetting::query()->find(1) ?? new GfaWifiSetting([
                'ssid' => self::DEFAULT_WIFI_SSID,
                'password' => self::DEFAULT_WIFI_PASSWORD,
            ]),
        ]);
    }

    public function saveWifiSettings(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()?->role?->name, ['ADMIN', 'SUPER_U'], true), 403);

        $validated = $request->validate([
            'ssid' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:100'],
        ]);

        GfaWifiSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'ssid' => trim($validated['ssid']),
                'password' => trim((string) ($validated['password'] ?? '')),
            ],
        );

        return back()->with('successMessage', 'Parametres Wi-Fi mis a jour avec succes.');
    }

    public function ticketGo(): RedirectResponse
    {
        $token = $this->scanTokenGfaService->generateToken();

        return redirect()->route('gfa.ticket', ['token' => $token]);
    }

    public function ticket(Request $request): View
    {
        $token = $request->query('token');

        if (! $this->scanTokenGfaService->isValid($token)) {
            return view('facturation.gfa-ticket-invalid');
        }

        return view('facturation.gfa-ticket', [
            'token' => $token,
            'services' => ServiceGfa::query()->where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    private function escapeWifi(?string $value): string
    {
        return str($value ?? '')
            ->replace('\\', '\\\\')
            ->replace(';', '\;')
            ->replace(',', '\,')
            ->replace(':', '\:')
            ->replace('"', '\"')
            ->toString();
    }
}
