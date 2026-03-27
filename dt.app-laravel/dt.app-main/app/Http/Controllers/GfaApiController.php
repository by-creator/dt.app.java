<?php

namespace App\Http\Controllers;

use App\Models\AgentGfa;
use App\Models\GuichetGfa;
use App\Models\ServiceGfa;
use App\Models\TicketGfa;
use App\Services\ScanTokenGfaService;
use App\Services\TicketGfaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class GfaApiController extends Controller
{
    public function __construct(
        protected TicketGfaService $ticketGfaService,
        protected ScanTokenGfaService $scanTokenGfaService,
    ) {}

    public function getServices()
    {
        return response()->json(
            ServiceGfa::query()
                ->orderBy('nom')
                ->get()
                ->map(fn (ServiceGfa $service) => [
                    'id' => $service->id,
                    'nom' => $service->nom,
                    'prefixe' => $service->code ?? '',
                ])
        );
    }

    public function createService(Request $request)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prefixe' => ['nullable', 'string', 'max:50'],
        ]);

        $service = ServiceGfa::query()->create([
            'nom' => strtoupper(trim($validated['nom'])),
            'code' => blank($validated['prefixe'] ?? null) ? null : strtoupper(trim($validated['prefixe'])),
            'actif' => true,
        ]);

        return response()->json([
            'id' => $service->id,
            'nom' => $service->nom,
            'prefixe' => $service->code ?? '',
        ]);
    }

    public function updateService(Request $request, ServiceGfa $service)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prefixe' => ['nullable', 'string', 'max:50'],
        ]);

        $service->update([
            'nom' => strtoupper(trim($validated['nom'])),
            'code' => blank($validated['prefixe'] ?? null) ? null : strtoupper(trim($validated['prefixe'])),
        ]);

        return response()->json([
            'id' => $service->id,
            'nom' => $service->nom,
            'prefixe' => $service->code ?? '',
        ]);
    }

    public function deleteService(Request $request, ServiceGfa $service)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);
        $service->delete();

        return response()->noContent();
    }

    public function getGuichets()
    {
        return response()->json(
            GuichetGfa::query()
                ->with('service')
                ->orderBy('numero')
                ->get()
                ->map(fn (GuichetGfa $guichet) => $this->buildGuichetMap($guichet))
        );
    }

    public function createGuichet(Request $request)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);

        $validated = $request->validate([
            'numero' => ['required', 'string', 'max:20'],
            'infos' => ['nullable', 'string', 'max:20'],
            'serviceId' => ['nullable', 'exists:services,id'],
        ]);

        $guichet = GuichetGfa::query()->create([
            'numero' => trim($validated['numero']),
            'infos' => trim((string) ($validated['infos'] ?? '')),
            'service_id' => $validated['serviceId'] ?? null,
            'actif' => true,
        ]);

        return response()->json($this->buildGuichetMap($guichet->load('service')));
    }

    public function updateGuichet(Request $request, GuichetGfa $guichet)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);

        $validated = $request->validate([
            'numero' => ['required', 'string', 'max:20'],
            'infos' => ['nullable', 'string', 'max:20'],
            'serviceId' => ['nullable', 'exists:services,id'],
        ]);

        $guichet->update([
            'numero' => trim($validated['numero']),
            'infos' => trim((string) ($validated['infos'] ?? '')),
            'service_id' => $validated['serviceId'] ?? null,
        ]);

        return response()->json($this->buildGuichetMap($guichet->fresh('service')));
    }

    public function deleteGuichet(Request $request, GuichetGfa $guichet)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);
        $guichet->delete();

        return response()->noContent();
    }

    public function getAgents()
    {
        return response()->json(
            AgentGfa::query()
                ->with(['service', 'guichet'])
                ->where('actif', true)
                ->orderBy('nom')
                ->get()
                ->map(fn (AgentGfa $agent) => $this->buildAgentMap($agent))
        );
    }

    public function createAgent(Request $request)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'serviceId' => ['required', 'exists:services,id'],
            'guichetId' => ['required', 'exists:guichets,id'],
        ]);

        $agent = AgentGfa::query()->create([
            'nom' => strtoupper(trim($validated['nom'])),
            'prenom' => trim((string) ($validated['prenom'] ?? '')),
            'service_id' => $validated['serviceId'],
            'guichet_id' => $validated['guichetId'],
            'actif' => true,
        ]);

        return response()->json($this->buildAgentMap($agent->load(['service', 'guichet'])));
    }

    public function updateAgent(Request $request, AgentGfa $agent)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['nullable', 'string', 'max:100'],
            'serviceId' => ['nullable', 'exists:services,id'],
            'guichetId' => ['nullable', 'exists:guichets,id'],
        ]);

        $agent->update([
            'nom' => strtoupper(trim($validated['nom'])),
            'prenom' => trim((string) ($validated['prenom'] ?? '')),
            'service_id' => $validated['serviceId'] ?? null,
            'guichet_id' => $validated['guichetId'] ?? null,
        ]);

        return response()->json($this->buildAgentMap($agent->fresh(['service', 'guichet'])));
    }

    public function deleteAgent(Request $request, AgentGfa $agent)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);
        $agent->update(['actif' => false]);

        return response()->noContent();
    }

    public function getGuichetInfo(int $guichetId)
    {
        $guichet = GuichetGfa::query()->with('service')->findOrFail($guichetId);

        return response()->json([
            'id' => $guichet->id,
            'numero' => $guichet->numero,
            'serviceId' => $guichet->service?->id,
            'serviceNom' => $guichet->service?->nom,
        ]);
    }

    public function getWaitingForGuichet(int $guichetId)
    {
        $guichet = GuichetGfa::query()->findOrFail($guichetId);

        return response()->json(
            $guichet->service_id ? $this->ticketGfaService->findWaitingByService($guichet->service_id) : []
        );
    }

    public function getCurrentForGuichet(int $guichetId)
    {
        $current = $this->ticketGfaService->getCurrentForGuichet($guichetId);

        return $current ? response()->json($current) : response()->noContent();
    }

    public function callNextForGuichet(Request $request)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U', 'FACTURATION']);

        $validated = $request->validate([
            'guichetId' => ['required', 'exists:guichets,id'],
        ]);

        try {
            return response()->json($this->ticketGfaService->callNextForGuichet((int) $validated['guichetId']));
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function recallTicket(Request $request)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U', 'FACTURATION']);

        $validated = $request->validate([
            'ticketId' => ['required', 'exists:tickets,id'],
        ]);

        try {
            return response()->json($this->ticketGfaService->recallTicket((int) $validated['ticketId']));
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function termineTicket(Request $request, int $id)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U', 'FACTURATION']);

        return response()->json($this->ticketGfaService->close($id));
    }

    public function incompletTicket(Request $request, int $id)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U', 'FACTURATION']);

        return response()->json($this->ticketGfaService->markIncomplet($id));
    }

    public function absentTicket(Request $request, int $id)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U', 'FACTURATION']);

        return response()->json($this->ticketGfaService->markAbsent($id));
    }

    public function getStats()
    {
        return response()->json($this->ticketGfaService->statsByService());
    }

    public function listTickets(Request $request)
    {
        return response()->json(
            $this->ticketGfaService->listTickets(
                guichetId: $request->integer('guichetId') ?: null,
                statut: $request->string('statut')->toString() ?: null,
                date: $request->string('date')->toString() ?: null,
            )
        );
    }

    public function exportTickets(Request $request)
    {
        $tickets = $this->ticketGfaService->listTickets(
            guichetId: $request->integer('guichetId') ?: null,
            statut: $request->string('statut')->toString() ?: null,
            date: $request->string('date')->toString() ?: null,
        );

        $header = ['ID', 'Numero', 'Service', 'Guichet', 'Agent', 'Statut', 'Date creation', 'Date appel', 'Date cloture', 'Temps attente (s)', 'Temps traitement (s)'];
        $rows = collect($tickets)->map(fn (array $ticket) => [
            $ticket['id'],
            $ticket['numero'],
            $ticket['serviceNom'],
            $ticket['guichetNumero'],
            $ticket['agentNom'],
            $ticket['statut'],
            $ticket['createdAt'],
            $ticket['calledAt'],
            $ticket['closedAt'],
            $ticket['waitingTime'],
            $ticket['processingTime'],
        ]);

        $csv = collect([$header])
            ->concat($rows)
            ->map(fn (array $line) => collect($line)->map(function ($value) {
                $value = (string) ($value ?? '');
                $value = str_replace('"', '""', $value);

                return '"'.$value.'"';
            })->implode(';'))
            ->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tickets-gfa-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function truncateTickets(Request $request)
    {
        $this->authorizeRoles($request, ['ADMIN', 'SUPER_U']);
        DB::table('tickets')->delete();

        return response()->noContent();
    }

    public function generateScanToken()
    {
        return response()->json([
            'token' => $this->scanTokenGfaService->generateToken(),
        ]);
    }

    public function createTicketPublic(Request $request)
    {
        $validated = $request->validate([
            'serviceId' => ['required', 'exists:services,id'],
            'scanToken' => ['nullable', 'string'],
            'nomClient' => ['nullable', 'string', 'max:150'],
            'motif' => ['nullable', 'string', 'max:255'],
        ]);

        $isAnonymous = $request->user() === null;

        if ($isAnonymous) {
            if (blank($validated['scanToken'] ?? null)) {
                return response()->json(['error' => 'Token requis pour prendre un ticket.'], 403);
            }

            if (! $this->scanTokenGfaService->useToken($validated['scanToken'])) {
                return response()->json(['error' => 'Token invalide ou expire.'], 403);
            }
        }

        return response()->json(
            $this->ticketGfaService->createTicket(
                serviceId: (int) $validated['serviceId'],
                nomClient: $validated['nomClient'] ?? null,
                motif: $validated['motif'] ?? null,
            )
        );
    }

    private function buildGuichetMap(GuichetGfa $guichet): array
    {
        return [
            'id' => $guichet->id,
            'numero' => $guichet->numero,
            'infos' => $guichet->infos ?? '',
            'serviceId' => $guichet->service?->id,
            'serviceNom' => $guichet->service?->nom ?? '',
        ];
    }

    private function buildAgentMap(AgentGfa $agent): array
    {
        return [
            'id' => $agent->id,
            'nom' => $agent->nom ?? '',
            'prenom' => $agent->prenom ?? '',
            'serviceId' => $agent->service?->id,
            'serviceNom' => $agent->service?->nom ?? '',
            'guichetId' => $agent->guichet?->id,
            'guichetNumero' => $agent->guichet?->numero ?? '',
        ];
    }

    private function authorizeRoles(Request $request, array $roles): void
    {
        abort_unless(in_array($request->user()?->role?->name, $roles, true), 403);
    }
}
