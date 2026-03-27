<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()?->role?->name === 'FACTURATION') {
            return redirect()->route('facturation.dashboard');
        }

        if ($request->user()?->role?->name === 'DIRECTION_GENERALE') {
            return redirect()->route('direction.dashboard');
        }

        if ($request->user()?->role?->name === 'DIRECTION_FINANCIERE') {
            return redirect()->route('direction.financiere');
        }

        if ($request->user()?->role?->name === 'DIRECTION_EXPLOITATION') {
            return redirect()->route('direction.exploitation');
        }

        if ($request->user()?->role?->name === 'PLANIFICATION') {
            return redirect()->route('planification.dashboard');
        }

        $today = CarbonImmutable::today();
        $recentUsers = User::query()
            ->with('role')
            ->latest()
            ->take(5)
            ->get();

        $adminRoleId = Role::query()
            ->where('name', 'ADMIN')
            ->value('id');

        $auditTopRoutes = AuditLog::query()
            ->whereDate('created_at', $today)
            ->whereNotNull('route_name')
            ->selectRaw('route_name, COUNT(*) as total')
            ->groupBy('route_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $methodColors = [
            'GET'    => ['bg' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
            'POST'   => ['bg' => 'bg-blue-500',    'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'],
            'PUT'    => ['bg' => 'bg-amber-500',   'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'],
            'PATCH'  => ['bg' => 'bg-orange-500',  'badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300'],
            'DELETE' => ['bg' => 'bg-red-500',     'badge' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300'],
        ];

        $auditByMethod = AuditLog::query()
            ->whereDate('created_at', $today)
            ->selectRaw('method, COUNT(*) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($methodColors) {
                $row->bg    = $methodColors[$row->method]['bg']    ?? 'bg-slate-400';
                $row->badge = $methodColors[$row->method]['badge'] ?? 'bg-slate-100 text-slate-600';
                return $row;
            });

        return view('dashboard', [
            'todayLabel' => now()->locale('fr')->translatedFormat('l d F Y'),
            'stats' => [
                'total_users'  => User::query()->count(),
                'total_roles'  => Role::query()->count(),
                'admin_users'  => $adminRoleId
                    ? User::query()->where('role_id', $adminRoleId)->count()
                    : 0,
                'users_today'  => User::query()->whereDate('created_at', $today)->count(),
            ],
            'auditStats' => [
                'requests_today'      => AuditLog::query()->whereDate('created_at', $today)->count(),
                'active_users_today'  => AuditLog::query()->whereDate('created_at', $today)->distinct('user_id')->count('user_id'),
                'errors_today'        => AuditLog::query()->whereDate('created_at', $today)->where('response_status', '>=', 400)->count(),
            ],
            'auditTopRoutes' => $auditTopRoutes,
            'auditByMethod'  => $auditByMethod,
            'recentUsers'    => $recentUsers,
        ]);
    }
}
