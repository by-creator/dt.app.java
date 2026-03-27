<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogger
{
    private const EXCLUDED_PATH_FRAGMENTS = [
        'livewire',
        'audit/export',
        '/up',
        'gfa/api/guichet',
        'gfa/api/stats',
        'gfa/api/scan-token',
        'gfa/api/services',
        'gfa/api/guichets',
        'gfa/api/agents',
    ];

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'two_factor_code',
        'two_factor_recovery_code',
        '_token',
        'token',
        'secret',
        'remember',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        $response = $next($request);

        try {
            $this->record($request, $response, $start);
        } catch (\Throwable) {
            // Never break the application due to audit failure
        }

        return $response;
    }

    private function record(Request $request, Response $response, int $start): void
    {
        if (! $request->user()) {
            return;
        }

        $path = $request->path();
        foreach (self::EXCLUDED_PATH_FRAGMENTS as $fragment) {
            if (str_contains($path, ltrim($fragment, '/'))) {
                return;
            }
        }

        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

        $payload = null;
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            $filtered = $request->except(self::SENSITIVE_KEYS);
            $payload = count($filtered) > 0 ? $filtered : null;
        }

        $routeAction = $request->route()?->getActionName();
        $controllerAction = ($routeAction && str_contains($routeAction, '@')) ? $routeAction : null;

        $queryParams = $request->query();

        AuditLog::create([
            'user_id'           => $request->user()->id,
            'user_name'         => $request->user()->name,
            'user_email'        => $request->user()->email,
            'user_role'         => $request->user()->role?->name,
            'method'            => $request->method(),
            'url'               => $request->fullUrl(),
            'route_name'        => $request->route()?->getName(),
            'controller_action' => $controllerAction,
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent(),
            'payload'           => $payload,
            'query_params'      => count($queryParams) > 0 ? $queryParams : null,
            'session_id'        => $request->hasSession() ? $request->session()->getId() : null,
            'response_status'   => $response->getStatusCode(),
            'duration_ms'       => $durationMs,
        ]);
    }
}
