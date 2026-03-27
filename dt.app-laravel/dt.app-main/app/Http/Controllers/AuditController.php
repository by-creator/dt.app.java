<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $query = AuditLog::query()->latest();

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($method = $request->input('method')) {
            $query->where('method', strtoupper($method));
        }

        if ($user = $request->input('user')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_name', 'like', "%{$user}%")
                  ->orWhere('user_email', 'like', "%{$user}%")
                  ->orWhere('user_role', 'like', "%{$user}%");
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('route_name', 'like', "%{$search}%")
                  ->orWhere('controller_action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(5)->withQueryString();

        return view('audit.index', compact('logs'));
    }

    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeAdmin($request);

        $query = $this->buildFilteredQuery($request)->latest();

        $filename = 'audit-' . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for proper Excel rendering
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID', 'Date', 'Utilisateur', 'Email', 'Rôle',
                'IP', 'Méthode', 'URL', 'Route', 'Action contrôleur',
                'Statut HTTP', 'Durée (ms)', 'Session', 'User Agent',
            ], ';');

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at?->format('d/m/Y H:i:s'),
                        $log->user_name,
                        $log->user_email,
                        $log->user_role,
                        $log->ip_address,
                        $log->method,
                        $log->url,
                        $log->route_name,
                        $log->controller_action,
                        $log->response_status,
                        $log->duration_ms,
                        $log->session_id,
                        $log->user_agent,
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store',
        ]);
    }

    public function exportPdf(Request $request): View
    {
        $this->authorizeAdmin($request);

        $logs = $this->buildFilteredQuery($request)->latest()->limit(1000)->get();

        return view('audit.print', compact('logs'));
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = AuditLog::query();

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($method = $request->input('method')) {
            $query->where('method', strtoupper($method));
        }

        if ($user = $request->input('user')) {
            $query->where(function ($q) use ($user) {
                $q->where('user_name', 'like', "%{$user}%")
                  ->orWhere('user_email', 'like', "%{$user}%")
                  ->orWhere('user_role', 'like', "%{$user}%");
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('route_name', 'like', "%{$search}%")
                  ->orWhere('controller_action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role?->name === 'ADMIN', 403);
    }
}
