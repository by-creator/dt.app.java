<?php

namespace App\Services;

use App\Models\AgentGfa;
use App\Models\GuichetGfa;
use App\Models\ServiceGfa;
use App\Models\TicketGfa;
use Illuminate\Support\Facades\DB;

class TicketGfaService
{
    public function __construct(
        protected PusherNotifier $pusherNotifier,
    ) {}

    public function createTicket(int $serviceId, ?string $nomClient = null, ?string $motif = null): array
    {
        $service = ServiceGfa::query()
            ->where('actif', true)
            ->findOrFail($serviceId);

        $ticket = TicketGfa::query()->create([
            'service_id' => $service->id,
            'statut' => 'EN_ATTENTE',
            'numero' => $this->generateTicketNumber($service),
        ]);

        $payload = $this->toArray($ticket->load('service'));
        $this->publishTicketCreated($payload);
        $this->publishQueueUpdate($service->id);

        return $payload;
    }

    public function findWaitingByService(int $serviceId): array
    {
        return TicketGfa::query()
            ->with(['service', 'agent', 'guichet'])
            ->where('service_id', $serviceId)
            ->where('statut', 'EN_ATTENTE')
            ->orderBy('created_at')
            ->get()
            ->map(fn (TicketGfa $ticket) => $this->toArray($ticket))
            ->all();
    }

    public function getCurrentForGuichet(int $guichetId): ?array
    {
        $ticket = TicketGfa::query()
            ->with(['service', 'agent', 'guichet'])
            ->where('guichet_id', $guichetId)
            ->where('statut', 'EN_COURS')
            ->latest('called_at')
            ->first();

        return $ticket ? $this->toArray($ticket) : null;
    }

    public function callNextForGuichet(int $guichetId): array
    {
        return DB::transaction(function () use ($guichetId): array {
            $guichet = GuichetGfa::query()->with('service')->findOrFail($guichetId);

            if (! $guichet->service_id) {
                throw new \RuntimeException('Ce guichet n\'est rattache a aucun service.');
            }

            $current = TicketGfa::query()
                ->where('guichet_id', $guichetId)
                ->where('statut', 'EN_COURS')
                ->first();

            if ($current) {
                throw new \RuntimeException('Un ticket est deja en cours sur ce guichet.');
            }

            $ticket = TicketGfa::query()
                ->with(['service', 'agent', 'guichet'])
                ->where('service_id', $guichet->service_id)
                ->where('statut', 'EN_ATTENTE')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw new \RuntimeException('Aucun ticket en attente pour ce service.');
            }

            $agent = AgentGfa::query()
                ->where('guichet_id', $guichetId)
                ->where('actif', true)
                ->orderBy('id')
                ->first();

            $ticket->forceFill([
                'guichet_id' => $guichetId,
                'agent_id' => $agent?->id,
                'statut' => 'EN_COURS',
                'waiting_time' => now(),
                'called_at' => now(),
            ])->save();

            $payload = $this->toArray($ticket->fresh(['service', 'agent', 'guichet']));
            $this->publishTicketCalled($payload);
            $this->publishQueueUpdate((int) $guichet->service_id);
            $this->publishCurrentTicketUpdate($guichetId, $payload);

            return $payload;
        });
    }

    public function recallTicket(int $ticketId): array
    {
        $ticket = TicketGfa::query()->with(['service', 'agent', 'guichet'])->findOrFail($ticketId);

        if ($ticket->statut !== 'EN_COURS') {
            throw new \RuntimeException('Seul un ticket en cours peut etre rappele.');
        }

        $payload = $this->toArray($ticket);
        $this->publishTicketCalled($payload, true);
        $this->publishCurrentTicketUpdate((int) $ticket->guichet_id, $payload);

        return $payload;
    }

    public function close(int $ticketId): array
    {
        return $this->closeWithStatus($ticketId, 'TERMINE');
    }

    public function markIncomplet(int $ticketId): array
    {
        return $this->closeWithStatus($ticketId, 'INCOMPLET');
    }

    public function markAbsent(int $ticketId): array
    {
        return $this->closeWithStatus($ticketId, 'ABSENT');
    }

    public function listTickets(?int $guichetId = null, ?string $statut = null, ?string $date = null): array
    {
        $query = TicketGfa::query()->with(['service', 'agent', 'guichet'])->latest();

        if ($guichetId) {
            $query->where('guichet_id', $guichetId);
        }

        if ($statut) {
            $query->where('statut', $statut);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query->get()->map(fn (TicketGfa $ticket) => $this->toArray($ticket))->all();
    }

    public function statsByService(): array
    {
        return ServiceGfa::query()
            ->get()
            ->map(function (ServiceGfa $service): array {
                return [
                    'serviceId' => $service->id,
                    'serviceNom' => $service->nom,
                    'enAttente' => TicketGfa::query()->where('service_id', $service->id)->where('statut', 'EN_ATTENTE')->count(),
                    'enCours' => TicketGfa::query()->where('service_id', $service->id)->where('statut', 'EN_COURS')->count(),
                    'termine' => TicketGfa::query()->where('service_id', $service->id)->where('statut', 'TERMINE')->count(),
                    'incomplet' => TicketGfa::query()->where('service_id', $service->id)->where('statut', 'INCOMPLET')->count(),
                    'absent' => TicketGfa::query()->where('service_id', $service->id)->where('statut', 'ABSENT')->count(),
                ];
            })
            ->all();
    }

    public function toArray(TicketGfa $ticket): array
    {
        $service = $ticket->service;
        $agent = $ticket->agent;
        $guichet = $ticket->guichet;

        return [
            'id' => $ticket->id,
            'numero' => $ticket->numero,
            'nomClient' => $ticket->nom_client,
            'motif' => $ticket->motif,
            'statut' => $ticket->statut,
            'serviceId' => $service?->id,
            'serviceNom' => $service?->nom,
            'guichetId' => $guichet?->id,
            'guichetNumero' => $guichet?->numero,
            'agentId' => $agent?->id,
            'agentNom' => $agent ? trim($agent->nom.' '.$agent->prenom) : null,
            'createdAt' => $ticket->created_at?->toIso8601String(),
            'calledAt' => $ticket->called_at?->toIso8601String(),
            'closedAt' => $ticket->closed_at?->toIso8601String(),
            'waitingTime' => $ticket->waiting_time && $ticket->created_at
                ? abs($ticket->waiting_time->getTimestamp() - $ticket->created_at->getTimestamp())
                : null,
            'waitingTimeAt' => $ticket->waiting_time?->toIso8601String(),
            'processingTime' => $ticket->processing_time !== null ? abs((int) $ticket->processing_time) : null,
        ];
    }

    private function closeWithStatus(int $ticketId, string $status): array
    {
        $ticket = TicketGfa::query()->with(['service', 'agent', 'guichet'])->findOrFail($ticketId);

        $closedAt = now();
        $processingTime = $ticket->called_at
            ? abs($closedAt->getTimestamp() - $ticket->called_at->getTimestamp())
            : null;

        $ticket->forceFill([
            'statut' => $status,
            'closed_at' => $closedAt,
            'processing_time' => $processingTime,
        ])->save();

        $payload = $this->toArray($ticket->fresh(['service', 'agent', 'guichet']));
        $this->publishTicketClosed($payload);
        $this->publishQueueUpdate((int) $ticket->service_id);
        $this->publishCurrentTicketUpdate((int) $ticket->guichet_id, null);

        return $payload;
    }

    private function generateTicketNumber(ServiceGfa $service): string
    {
        $prefix = strtoupper(trim($service->code ?: substr(preg_replace('/[^A-Za-z0-9]/', '', $service->nom), 0, 3) ?: 'TCK'));

        $countToday = TicketGfa::query()
            ->where('service_id', $service->id)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%03d', $prefix, $countToday);
    }

    private function publishTicketCreated(array $ticket): void
    {
        $this->pusherNotifier->trigger('gfa-display', 'ticket.created', $ticket);
    }

    private function publishTicketCalled(array $ticket, bool $recalled = false): void
    {
        $this->pusherNotifier->trigger('gfa-display', 'ticket.called', [
            ...$ticket,
            'recalled' => $recalled,
        ]);
    }

    private function publishTicketClosed(array $ticket): void
    {
        $this->pusherNotifier->trigger('gfa-display', 'ticket.closed', $ticket);
    }

    private function publishQueueUpdate(int $serviceId): void
    {
        if ($serviceId <= 0) {
            return;
        }

        $this->pusherNotifier->trigger('gfa-service.'.$serviceId, 'queue.updated', [
            'serviceId' => $serviceId,
            'queue' => $this->findWaitingByService($serviceId),
        ]);
    }

    private function publishCurrentTicketUpdate(int $guichetId, ?array $ticket): void
    {
        if ($guichetId <= 0) {
            return;
        }

        $this->pusherNotifier->trigger('gfa-guichet.'.$guichetId, 'ticket.current', [
            'guichetId' => $guichetId,
            'ticket' => $ticket,
        ]);
    }
}
