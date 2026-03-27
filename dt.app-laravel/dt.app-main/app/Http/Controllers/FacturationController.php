<?php

namespace App\Http\Controllers;

use App\Models\RattachementBl;
use App\Models\SuiviStationnement;
use App\Models\SuiviVide;
use App\Services\DematEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FacturationController extends Controller
{
    public function __construct(
        protected DematEmailService $dematEmailService,
    ) {}

    public function ies(): View
    {
        return view('facturation.ies');
    }

    public function validations(Request $request): View
    {
        $this->authorizeFacturationAccess($request);

        $items = RattachementBl::query()
            ->where('type', 'VALIDATION')
            ->latest()
            ->get();

        $mapped = $items->map(fn (RattachementBl $item) => $this->mapRattachement($item))->values();

        return view('facturation.validations', [
            'initialDemandes' => $mapped,
        ]);
    }

    public function remises(Request $request): View
    {
        $this->authorizeFacturationAccess($request);

        $roleName = $request->user()?->role?->name;
        $initialStatut = '';

        if ($roleName === 'FACTURATION') {
            $initialStatut = 'EN_ATTENTE_VALIDATION_FACTURATION';
        } elseif (in_array($roleName, ['DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION'], true)) {
            $initialStatut = 'EN_ATTENTE_VALIDATION_DIRECTION';
        }

        $items = RattachementBl::query()
            ->where('type', 'REMISE')
            ->when(
                $initialStatut !== '',
                fn ($builder) => $builder->where('statut', $initialStatut)
            )
            ->latest()
            ->get();

        $items = $this->filterRemisesForRole($roleName, $items);
        $mapped = $items->map(fn (RattachementBl $item) => $this->mapRattachement($item))->values();

        return view('facturation.remises', [
            'initialRemises' => $mapped,
            'initialRemiseStatut' => $initialStatut,
        ]);
    }

    public function rapport(): View
    {
        $rapports = SuiviVide::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (SuiviVide $r) => [
                'id' => $r->id,
                'terminal' => $r->terminal,
                'equipmentNumber' => $r->equipment_number,
                'equipmentTypeSize' => $r->equipment_type_size,
                'eventCode' => $r->event_code,
                'eventName' => $r->event_name,
                'eventFamily' => $r->event_family,
                'eventDate' => $r->event_date,
                'bookingSecNo' => $r->booking_sec_no,
            ])
            ->values();

        $stationnements = SuiviStationnement::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (SuiviStationnement $r) => [
                'id'          => $r->id,
                'terminal'    => $r->terminal,
                'billingDate' => $r->billing_date,
                'shipowner'   => $r->shipowner,
                'blNumber'    => $r->bl_number,
                'itemNumber'  => $r->item_number,
                'itemType'    => $r->item_type,
                'type'        => $r->type,
                'entryDate'   => $r->entry_date,
                'exitDate'    => $r->exit_date,
                'daysSinceIn' => $r->days_since_in,
            ])
            ->values();

        return view('facturation.rapport', [
            'initialRapports'       => $rapports,
            'initialStationnements' => $stationnements,
        ]);
    }

    public function sendIesAccessLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
        ]);

        try {
            $this->dematEmailService->sendIesAccessLinkEmail(
                $validated['email'],
                url('/demat'),
            );

            return redirect()
                ->route('facturation.ies', ['tab' => 'lien-acces'])
                ->with('iesSuccess', "Lien d'acces envoye avec succes a {$validated['email']}.");
        } catch (\Throwable) {
            return redirect()
                ->route('facturation.ies', ['tab' => 'lien-acces'])
                ->with('iesError', "Erreur lors de l'envoi du mail.");
        }
    }

    public function sendIesAccountCreated(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->dematEmailService->sendIesAccountCreatedEmail(
                $validated['email'],
                $validated['password'],
                url('/demat'),
            );

            return redirect()
                ->route('facturation.ies', ['tab' => 'creation-compte'])
                ->with('iesSuccess', "Email de creation de compte envoye a {$validated['email']}.");
        } catch (\Throwable) {
            return redirect()
                ->route('facturation.ies', ['tab' => 'creation-compte'])
                ->with('iesError', "Erreur lors de l'envoi du mail.");
        }
    }

    public function sendIesPasswordReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        try {
            $this->dematEmailService->sendIesPasswordResetEmail(
                $validated['email'],
                $validated['password'],
                url('/demat'),
            );

            return redirect()
                ->route('facturation.ies', ['tab' => 'reset-password'])
                ->with('iesSuccess', "Email de reinitialisation envoye a {$validated['email']}.");
        } catch (\Throwable) {
            return redirect()
                ->route('facturation.ies', ['tab' => 'reset-password'])
                ->with('iesError', "Erreur lors de l'envoi du mail.");
        }
    }

    private function authorizeFacturationAccess(Request $request): void
    {
        abort_unless(
            in_array($request->user()?->role?->name, ['FACTURATION', 'DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION', 'ADMIN', 'SUPER_U'], true),
            403
        );
    }

    private function filterRemisesForRole(?string $roleName, Collection $items): Collection
    {
        if ($roleName === 'FACTURATION') {
            return $items->where('statut', 'EN_ATTENTE_VALIDATION_FACTURATION');
        }

        if (in_array($roleName, ['DIRECTION_GENERALE', 'DIRECTION_FINANCIERE', 'DIRECTION_EXPLOITATION'], true)) {
            return $items->where('statut', 'EN_ATTENTE_VALIDATION_DIRECTION');
        }

        return $items;
    }

    private function mapRattachement(RattachementBl $item): array
    {
        return [
            'id' => $item->id,
            'nom' => $item->nom,
            'prenom' => $item->prenom,
            'email' => $item->email,
            'bl' => $item->bl,
            'maisonTransit' => $item->maison,
            'type' => $item->type,
            'statut' => $item->statut,
            'motifRejet' => $item->motif_rejet,
            'pourcentage' => $item->pourcentage,
            'createdAt' => $item->created_at?->toIso8601String(),
            'updatedAt' => $item->updated_at?->toIso8601String(),
        ];
    }
}
